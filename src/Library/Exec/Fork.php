<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Core\ParseCrontab;
use WolfansSm\Library\Http\Server;
use WolfansSm\Library\Log\Log;
use WolfansSm\Library\Schedule\Register;
use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Fork {
    public function __construct() {
    }

    public function run() {
        //异步wait，同步fork
        $this->waitSigChild();//回收孩子
        $this->waitSigAlarm();//异步fork子进程
        $this->addSubTask();//http子进程
        (new Crontab())->policy();
        $this->forkTick();//添加计时器死循环
    }

    protected function forkTick() {
        \Swoole\Timer::tick(5000, function () {
        });
    }

    /**
     * 子进程
     */
    protected function fork() {
        foreach (Table::getShareSchedule() as $options) {
            $taskId    = isset($options['task_id']) ? $options['task_id'] : '';
            $routeId   = isset($options['route_id']) ? $options['route_id'] : '';
            $canRunSec = isset($options['can_run_sec']) ? $options['can_run_sec'] : 0;
            if (!$taskId || !$routeId || !$canRunSec) {
                continue;
            }
            $params = Route::getParamStr($taskId, $routeId, $options);
            //生成进程
            for (; Table::getCountByRouteId($routeId) < Table::getMaxCountByRouteId($routeId);) {
                if ($routeId == 'wolfans_crontab_server') {
                    $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($taskId, $routeId, $params) {
                        $childProcess->name('wolfans-worker-' . $routeId);
                        (new Crontab())->run();
                    });
                    $process->start();
                    Table::addCountByPid($process->pid, $routeId);
                } elseif ($routeId == 'wolfans_https_server') {
                    $httpIp   = Register::getListenHttpIp();
                    $httpPort = Register::getListenHttpPort();
                    $ipList   = Register::getHttpIpList();
                    $portList = Register::getHttpPortList();
                    if (is_numeric($httpPort) && $httpPort > 0) {
                        $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($taskId, $routeId, $params, $httpIp, $httpPort, $portList, $ipList) {
                            $childProcess->name('wolfans-worker-' . $routeId);
                            (new Server())->run($httpIp, $httpPort, $portList, $ipList);
                        });
                        $process->start();
                        Table::addCountByPid($process->pid, $routeId);
                    }
                } else {
                    $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($taskId, $routeId, $params, $options) {
                        if (defined('WOLFANS_PHP_ROOT')) {
                            array_unshift($params, WOLFANS_DIR_RUNPHP);
                            $childProcess->exec(WOLFANS_PHP_ROOT, $params); // exec 系统调用
                        } else {
                            $childProcess->name('wolfans-worker-' . $routeId);
                            (new Task())->run($taskId, $routeId, $options);
                        }
                    });
                    $process->start();
                    Table::addCountByPid($process->pid, $routeId);
                }
                $process->pid && Log::info($process->pid . "\t" . $routeId . "\t" . Table::getCountByRouteId($routeId));
            }
            Table::subRunList($routeId);
        }
    }

    /**
     * 闹钟：定期fork
     */
    protected function waitSigAlarm() {
        \Swoole\Process::signal(SIGALRM, function () {
            $this->fork();
        });
        //100ms
        \Swoole\Process::alarm(2000 * 1000);
    }

    /**
     * 子进程退出
     */
    protected function waitSigChild() {
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            while ($ret = \Swoole\Process::wait(false)) {
                $pid     = $ret['pid'];
                $code    = (int)$ret['code'];
                $routeId = Table::subByPid($pid);
                $code > 0 && $routeId && Log::error($pid . "\t" . $routeId . "\t" . $code);
            }
        });
    }

    /**
     * http
     */
    protected function addSubTask() {
        Table::addSchedule('9999999', 'wolfans_https_server', ['min_pnum' => 1, 'max_pnum' => 1, 'loopnum' => 1, 'loopsleepms' => 10000, 'crontab' => '*/10 * * * * *']);
        Table::addSchedule('9999998', 'wolfans_crontab_server', ['min_pnum' => 1, 'max_pnum' => 1, 'loopnum' => 1, 'loopsleepms' => 10, 'crontab' => '* * * * * *']);
    }
}
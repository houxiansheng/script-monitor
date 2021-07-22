<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Core\ParseCrontab;
use WolfansSm\Library\Http\Server;
use WolfansSm\Library\Schedule\Register;
use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Fork {
    public function __construct() {
    }

    public function run() {
        //异步wait，同步fork
        $this->waitSigchild();//回收孩子
        $this->waitSIGAlarm();//异步fork子进程
        $this->http();//http子进程
        $this->crontab();//发起任务
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
                if ($routeId == 'wolfans_https_server') {
                    $httpIp   = Register::getListenHttpIp();
                    $httpPort = Register::getListenHttpPort();
                    $ipList   = Register::getHttpIpList();
                    $portList = Register::getHttpPortList();
                    if (is_numeric($httpPort) && $httpPort > 0) {
                        $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($httpIp, $httpPort, $portList, $ipList) {
                            (new Server())->run($httpIp, $httpPort, $portList, $ipList);
                        });
                        $process->start();
                        Table::addCountByPid($process->pid, $routeId);
                    }
                } else {
                    $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($taskId, $routeId, $params) {
                        if (defined('WOLFANS_PHP_ROOT')) {
                            array_unshift($params, WOLFANS_DIR_RUNPHP);
                            $childProcess->exec(WOLFANS_PHP_ROOT, $params); // exec 系统调用
                        } else {
                            $childProcess->name('wolfans-worker-' . $routeId);
                            (new Task())->run($taskId, $routeId);
                        }
                    });
                    $process->start();
                    Table::addCountByPid($process->pid, $routeId);
                }
                Table::addCountByPid($process->pid, $routeId);
            }
            Table::subRunList($routeId);
        }
    }

    protected function policy() {
        $now    = time();
        $second = (int)date('s', $now);
        foreach (Table::getShareSchedule() as $options) {
            $routeId = isset($options['route_id']) ? $options['route_id'] : '';
            $crontab = isset($options['crontab']) ? $options['crontab'] : '';
            $secList = ParseCrontab::parse($crontab);
            if ($secList && is_array($secList) && isset($secList[$second])) {
                Table::addRunList($routeId, $now);
            }
        }
    }

    /**
     * 闹钟：定期fork
     */
    protected function waitSIGAlarm() {
        \Swoole\Process::signal(SIGALRM, function () {
            $this->fork();
        });
        //100ms
        \Swoole\Process::alarm(2000 * 1000);
    }

    protected function crontab() {
        \Swoole\Timer::tick(1000, function () {
            $this->policy();
        });
    }

    protected function waitSigchild() {
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            while ($ret = \Swoole\Process::wait(false)) {
                $pid = $ret['pid'];
                Table::subByPid($pid);
            }
        });
    }

    /**
     * http
     */
    protected function http() {
        Table::addSchedule(1, 'wolfans_https_server', ['min_pnum' => 1, 'max_pnum' => 1, 'loopnum' => 1, 'loopsleepms' => 10000, 'crontab' => '* * * * * *']);
        $httpIp   = Register::getListenHttpIp();
        $httpPort = Register::getListenHttpPort();
        $ipList   = Register::getHttpIpList();
        $portList = Register::getHttpPortList();
        if (is_numeric($httpPort) && $httpPort > 0) {
            $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($httpIp, $httpPort, $portList, $ipList) {
                (new Server())->run($httpIp, $httpPort, $portList, $ipList);
            });
            $process->start();
        }
    }
}
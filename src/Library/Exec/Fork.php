<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Http\Server;
use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Fork {
    public function __construct() {
    }

    public function run() {
        //异步wait，同步fork
        $this->waitSIGCHLD();
        $this->waitSIGAlarm();
        $this->http();
        $this->forkTick();
    }

    protected function forkTick() {
        \Swoole\Timer::tick(5000, function () {
            $count = Table::getShareCount();
        });
    }

    /**
     * 子进程
     */
    protected function fork() {
        foreach (Table::getShareSchedule() as $options) {
            $taskId  = isset($options['task_id']) ? $options['task_id'] : '';
            $routeId = isset($options['route_id']) ? $options['route_id'] : '';
            if (!$taskId || !$routeId) {
                continue;
            }
            $params = Route::getParamStr($taskId, $routeId, $options);
            //生成进程
            for (; Table::getCountByRouteId($routeId) < Table::getMaxCountByRouteId($routeId);) {
                $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($taskId, $routeId, $params) {
                    if (defined('WOLFANS_PHP_ROOT')) {
                        array_unshift($params, WOLFANS_DIR_RUNPHP);
                        $childProcess->exec(WOLFANS_PHP_ROOT, $params); // exec 系统调用
                    } else {
                        $childProcess->name('workrun-'.$routeId);
                        (new Task())->run($taskId, $routeId);
                    }
                });
                $process->start();
                Table::addCountByPid($process->pid, $routeId);
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

    protected function waitSIGCHLD() {
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            while ($ret = \Swoole\Process::wait(false)) {
                $pid = $ret['pid'];
                Table::subByPid($pid);
            }
        });
    }

    protected function http() {
        $process = new \Swoole\Process(function (\Swoole\Process $childProcess) {
            (new Server())->run();
        });
        $process->start();
    }
}
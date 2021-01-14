<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Fork {
    public $waits = true;

    public function __construct() {
    }

    public function run() {
        $this->waitSIGCHLD();
        $this->waitSIGAlarm();
        $this->forkTick();
    }

    protected function forkTick() {
        \Swoole\Timer::tick(5000, function () {
            $count = Table::getShareCount();
        });
    }

    protected function fork() {
        $count      = 0;
        $forkStatus = false;
        foreach (Table::getShareSchedule() as $options) {
            $routeId = isset($options['route_id']) ? $options['route_id'] : '';
            if (!$routeId) {
                continue;
            }
            $params = Route::getParamStr($routeId, $options);
            array_unshift($params, WOLFANS_DIR_RUNPHP);
            //生成进程
            for (; Table::getCountByRouteId($routeId) < Table::getMaxCountByRouteId($routeId);) {
                $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($routeId, $params) {
                    $childProcess->exec(WOLFANS_PHP_ROOT, $params); // exec 系统调用
                });
                $process->start();
                Table::addCountByPid($process->pid, $routeId);
                $count++;
                $forkStatus = true;
            }
        }
        var_dump('fork--' . $count);
        return $forkStatus;
    }

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
}
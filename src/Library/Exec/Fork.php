<?php
namespace WolfansSm\Library\Command;

use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Fork {

    public function __construct() {
    }

    public function run() {
        //异步wait，同步fork
        $this->wait();
        $this->fork();
    }

    protected function fork() {
        foreach (Table::getShareSchedule() as $options) {
            $routeId = isset($options['route_id']) ? $options['route_id'] : '';
            if (!$routeId) {
                continue;
            }
            $params = Route::getParamStr($routeId);
            array_unshift($params, WOLFANS_DIR_RUNPHP);
            //生成进程
            for (; Table::getCountByRouteId($routeId) < Table::getMaxCountByRouteId($routeId);) {
                $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($routeId, $params) {
                    $childProcess->exec(WOLFANS_PHP_ROOT, $params); // exec 系统调用
                });
                $process->start();
                Table::addCountByPid($process->pid, $routeId);
            }
        }
    }

    protected function wait() {
        while ($ret = \Swoole\Process::wait(false)) {
            $pid = $ret['pid'];
            Table::subByPid($pid);
        }
        //        \Swoole\Process::signal(SIGCHLD, function ($sig) {
        //            while ($ret = \Swoole\Process::wait(false)) {
        //                $pid = $ret['pid'];
        //                Table::subByPid($pid);
        //            }
        //        });
    }
}
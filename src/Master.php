<?php
namespace Wolfans;

class Master {
    /**
     * 执行任务
     */
    public function run($taskId) {
        $Command   = new Command();
        $routeList = $Command->getRoute($taskId);
        $monitor   = new Monitor();
        $monitor->setRouteList($routeList);
        $monitor->run();
    }
}
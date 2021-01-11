<?php
namespace Wolfans\Schedule;

class Task {
    protected $class        = [];
    protected $scheduleList = [];

    public function register($routeId) {
        $command      = new Command();
        $routeId      = $command->decodeRouteId($routeId);
        $scheduleList = $command->getSchedule($routeId);
        //实例化
        foreach ($scheduleList as $schedule) {
            $this->scheduleList[] = new $schedule();
        }
        return $scheduleList ? true : false;
    }

    /**
     * run
     */
    public function run() {
        foreach ($this->scheduleList as $schedule) {
            $schedule->setChildProc();
            $schedule->run();
        }
    }
}
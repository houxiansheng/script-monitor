<?php
/**
 * 注册要启动的任务
 */
namespace Wolfans\Schedule;

class Register {
    protected  $command = [];

    public static function setCommand(Command $command) {
        $taskId = $command->getTaskId();
        if (!$taskId) {

        }
        if (isset(self::$command[$taskId])) {

        }
        foreach ($command->getRouteList() as $routeId => $options) {
            $schedule = new Schedule($taskId, $routeId);
            //配置参数
            foreach ($options as $key => $val) {
                $schedule->setOptions($key, $val);
            }
            //配置任务
            foreach ($command->getScheduleList($routeId) as $classList) {
                foreach ($classList as $class) {
                    $schedule->setTask($class);
                }
            }
            self::$command[$taskId][] = $schedule;
        }
    }

    /**
     * @param $taskId
     */
    /**
     * @param $taskId
     *
     * @return Command
     */
    public static function getCommand($taskId) {
        if (!$taskId) {

        }
        if (!isset(self::$command[$taskId])) {

        }
        return self::$command[$taskId];
    }
}

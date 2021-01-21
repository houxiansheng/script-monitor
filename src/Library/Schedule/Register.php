<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Schedule;

use WolfansSm\Library\Share\Table;

class Register {
    protected static $command = [];

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
            foreach ($command->getScheduleList($routeId) as $class) {
                $schedule->setTask($class);
            }
            self::$command[$taskId][$routeId] = $schedule;
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

    /**
     * 获取子任务
     *
     * @param $taskId
     * @param $routeId
     *
     * @return array|mixed
     */
    public static function getSchedules($taskId, $routeId) {
        if (isset(self::$command[$taskId][$routeId])) {
            return self::$command[$taskId][$routeId];
        } else {
            return [];
        }
    }

    public static function setCommandShareTable($taskId) {
        $commandList = self::getCommand($taskId);
        Table::init();
        foreach ($commandList as $routeId => $schedule) {
            Table::addSchedule($taskId, $routeId, $schedule->getOptions());
        }
    }
}

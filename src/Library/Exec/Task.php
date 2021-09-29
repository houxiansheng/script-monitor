<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Http\Server;
use WolfansSm\Library\Schedule\Register;
use WolfansSm\Library\Schedule\Schedule;
use WolfansSm\Library\Share\Table;
use WolfansSm\Library\Share\Route;

class Task {
    public function __construct() {
    }

    protected $taskList = [];

    public function run($taskId, $routeId, $runOptions = []) {
        $schedule = Register::getSchedules($taskId, $routeId);
        if (!($schedule instanceof Schedule)) {
            return '';
        }
        $options     = $schedule->getOptions();
        $cycleMaxNum = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 1;
        $loopSleepms = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
        $this->setTask($schedule->getTaskList());
        $runStatus = [];
        while ($cycleMaxNum-- > 0) {
            $runStatus = $this->exec($options);
            $this->setLogTmpStatus($routeId, $runStatus);
            //捕获信号
            usleep($loopSleepms * 1000);
        }
        $this->dealLogStatus($routeId, $runStatus);
    }

    protected function setLogTmpStatus($routeId, $runStatus) {
        if (is_array($runStatus) && isset($runStatus['log_num']) && isset($runStatus['log_len'])) {
            Table::setLogTmpStat($routeId, $runStatus['log_num'], $runStatus['log_len']);
        }
    }

    protected function dealLogStatus($routeId, $runStatus) {
        if (is_array($runStatus) && isset($runStatus['log_num']) && isset($runStatus['log_len'])) {
            Table::addLogAllStat($routeId, $runStatus['log_num'], $runStatus['log_len']);
        }
    }

    protected function setTask(array $schedules) {
        //异步wait，同步fork
        foreach ($schedules as $schedule) {
            $this->taskList[] = new $schedule();
        }
    }

    protected function exec($options) {
        $runStatus = [];
        foreach ($this->taskList as $task) {
            if (method_exists($task, 'setChildProc')) {
                $task->setChildProc();
            }
            $task->runOptions = $options;
            $task->run();
            if (method_exists($task, 'runStatus')) {
                $runStatus = $task->runStatus();
            }
        }
        return $runStatus;
    }
}
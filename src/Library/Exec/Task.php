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

    public function run($taskId, $routeId) {
        $schedule = Register::getSchedules($taskId, $routeId);
        if (!($schedule instanceof Schedule)) {
            return '';
        }
        $options     = $schedule->getOptions();
        $cycleMaxNum = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 1;
        $loopSleepms = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
        $this->setTask($schedule->getTaskList());
        while ($cycleMaxNum-- > 0) {
            $this->exec();
            //捕获信号
            usleep($loopSleepms * 1000);
        }
    }

    protected function setTask(array $schedules) {
        //异步wait，同步fork
        foreach ($schedules as $schedule) {
            $this->taskList[] = new $schedule();
        }
    }

    protected function exec() {
        foreach ($this->taskList as $task) {
            if(method_exists($task,'setChildProc')){
                $task->setChildProc();
            }
            $task->run();
        }
    }
}
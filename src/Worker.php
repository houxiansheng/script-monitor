<?php

namespace WolfansSm;

use WolfansSm\Library\Schedule\Command;
use WolfansSm\Library\Schedule\Schedule;
use WolfansSm\Library\Share\Route;
use \WolfansSm\Library\Share\Table;
use \WolfansSm\Library\Schedule\Register;
use \WolfansSm\Library\Exec\Task;

class  Worker {
    protected $taskId;
    protected $routeId;

    public function __construct() {
        $argvArr       = getopt('', ['taskid:', 'routeid:', 'loopnum::', 'loopsleepms::']);
        $this->routeId = isset($argvArr['routeid']) ? Route::decodeRouteId($argvArr['routeid']) : '';
        $this->taskId  = isset($argvArr['taskid']) ? $argvArr['taskid'] : '';
    }

    /**
     * 仅注册taskid相同的任务
     *
     * @param Command $command
     */
    public function setCommand(Command $command) {
        if ($command->getTaskId() == $this->taskId) {
            \WolfansSm\Register::setCommand($command);
        }
    }

    function run() {
        (new Task())->run($this->taskId, $this->routeId);
        //        $schedule = Register::getSchedules($this->taskId, $this->routeId);
        //        if (!($schedule instanceof Schedule)) {
        //            return '';
        //        }
        //        $options     = $schedule->getOptions();
        //        $cycleMaxNum = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 1;
        //        $loopSleepms = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
        //        $task        = new Task();
        //        $task->setTask($schedule->getTaskList());
        //        while ($cycleMaxNum-- > 0) {
        //            $task->run();
        //            //捕获信号
        //            usleep($loopSleepms * 1000);
        //        }
    }
}


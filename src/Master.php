<?php
namespace WolfansSm;

use WolfansSm\Library\Exec\Fork;
use WolfansSm\Library\Schedule\Command;
use \WolfansSm\Library\Schedule\Register as RegisterSchedule;

class Master {
    protected $taskId;

    public function __construct($phpRoot, $workFile) {
        define('WOLFANS_PHP_ROOT', $phpRoot);
        define('WOLFANS_DIR_RUNPHP', $workFile);
        $argvArr      = getopt('', ['taskid:']);
        $this->taskId = isset($argvArr['taskid']) ? $argvArr['taskid'] : '';
    }

    /**
     * 仅注册taskid相同的任务
     *
     * @param Command $command
     */
    public function setCommand(Command $command) {
        if ($command->getTaskId() == $this->taskId) {
            Register::setCommand($command);
        }
    }

    /**
     * 执行任务
     */
    public function run() {
        RegisterSchedule::setCommandShareTable($this->taskId);
        (new Fork())->run();
    }
}
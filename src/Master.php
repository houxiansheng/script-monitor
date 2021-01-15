<?php
namespace WolfansSm;

use WolfansSm\Library\Exec\Fork;
use WolfansSm\Library\Schedule\Command;
use \WolfansSm\Library\Schedule\Register as RegisterSchedule;

class Master {
    protected $taskId;

    public function __construct() {
        $argvArr      = getopt('', ['taskid:']);
        $this->taskId = isset($argvArr['taskid']) ? $argvArr['taskid'] : '';
        if (!$this->taskId) {
            var_dump('缺少taskid');
            exit();
        }
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

    public function setExecFile($phpRoot, $workFile) {
        define('WOLFANS_PHP_ROOT', $phpRoot);
        define('WOLFANS_DIR_RUNPHP', $workFile);
    }

    /**
     * 执行任务
     */
    public function run() {
        RegisterSchedule::setCommandShareTable($this->taskId);
        (new Fork())->run();
    }
}
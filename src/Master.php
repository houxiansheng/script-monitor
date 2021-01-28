<?php
namespace WolfansSm;

use WolfansSm\Library\Exec\Fork;
use WolfansSm\Library\Schedule\Command;
use \WolfansSm\Library\Schedule\Register as RegisterSchedule;

class Master {
    protected $taskId;
    protected $httpPort    = null;
    protected $allHttpPort = [];
    protected $ipList      = [];
    protected $forkClass   = null;

    public function __construct() {
        $argvArr         = getopt('', ['taskid:']);
        $this->taskId    = isset($argvArr['taskid']) ? $argvArr['taskid'] : 1;
        $this->forkClass = new Fork();
    }

    /**
     * 仅注册taskid相同的任务
     *
     * @param Command $command
     */
    public function setCommand(Command $command) {
        if ($command->getTaskId() == $this->taskId) {
            RegisterSchedule::setCommand($command);
            RegisterSchedule::setCommandShareTable($command->getTaskId());
        }
        //聚合所有端口
        if (is_numeric($command->getHttpPort()) && $command->getHttpPort() > 0) {
            RegisterSchedule::setHttpPort($command->getHttpPort());
            $command->getTaskId() == $this->taskId && RegisterSchedule::setListenHttpPort($command->getHttpPort());
        }
    }

    public function setHttpIp($ip) {
        RegisterSchedule::setListenHttpIp($ip);
    }

    public function setHttpIpList(array $list) {
        RegisterSchedule::setHttpIpList($list);
    }

    public function setExecFile($phpRoot, $workFile) {
        define('WOLFANS_PHP_ROOT', $phpRoot);
        define('WOLFANS_DIR_RUNPHP', $workFile);
    }

    /**
     * 执行任务
     */
    public function run() {
        $this->forkClass->run();
    }
}
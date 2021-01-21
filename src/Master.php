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
            $this->httpPort = $command->getHttpPort();
            RegisterSchedule::setCommand($command);
        }
        //聚合所有端口
        if (is_numeric($command->getHttpPort()) && $command->getHttpPort() > 0) {
            $this->allHttpPort[] = $command->getHttpPort();
        }
    }

    public function setIpList(array $list) {
        $this->ipList = $list;
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
        $fork = new Fork();
        $fork->setHttpPort($this->httpPort, $this->allHttpPort, $this->ipList);
        $fork->run();
    }
}
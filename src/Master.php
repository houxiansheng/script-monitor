<?php
namespace WolfansSm;

use WolfansSm\Library\Command\Fork;
use \WolfansSm\Library\Schedule\Register as RegisterSchedule;

class Master {
    public function __construct($phpRoot) {
        define('WOLFANS_PHP_ROOT', $phpRoot);
        define('WOLFANS_DIR_RUNPHP', __DIR__ . "/Worker.php");
    }

    /**
     * 执行任务
     */
    public function run($taskId) {
        RegisterSchedule::setCommandShareTable($taskId);
        $fork = (new Fork());
        while (true) {
            $fork->run();
            sleep(2);
        }
    }
}
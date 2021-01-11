<?php

namespace WolfansSm;

use \WolfansSm\Library\Command\Table;
use \WolfansSm\Library\Schedule\Register;
use \WolfansSm\Library\Schedule\Task;

$argvArr = getopt('', ['taskid:', 'routeid:', 'loopnum::', 'loopsleepms::']);
$routeId = isset($argvArr['routeid']) ? $argvArr['routeid'] : '';
$taskId  = isset($argvArr['taskid']) ? $argvArr['taskid'] : '';

$schedule = Register::getSchedules($taskId, $routeId);
//$schedule    = new Schedule();
$options     = $schedule->getOptions();
$cycleMaxNum = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 1;
$execCount   = 0;
$Task        = new Task();
while ($cycleMaxNum - $execCount > 0) {
    $Task->run($schedule->getTaskList());
    //获取共享空间配置
    $options     = Table::getSchedule($routeId);
    $cycleMaxNum = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 1;
    $loopSleepms = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
    //捕获信号
    usleep($loopSleepms * 1000);
}

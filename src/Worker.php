<?php

namespace Wolfans;

$startTime   = microtime(true);
$argvArr     = getopt('', ['routeid:', 'loopnum::', 'loopsleepms::']);
$cycleMaxNum = isset($argvArr['loopnum']) && is_numeric($argvArr['loopnum']) ? $argvArr['loopnum'] : 1;
$loopSleepms = isset($argvArr['loopsleepms']) && is_numeric($argvArr['loopsleepms']) ? $argvArr['loopsleepms'] : 100;
$routeId     = isset($argvArr['routeid']) ? $argvArr['routeid'] : '';
$schedule    = new Schedule();
$registerRes = $schedule->register($routeId);
if (!$registerRes) {
    Log::info('无事件处理:' . json_encode($argvArr));
    exit();
}
while ($cycleMaxNum > 0) {
    //捕获信号
    $schedule->run();
    usleep($loopSleepms * 1000);
    $cycleMaxNum--;
}

$endTime = microtime(true);

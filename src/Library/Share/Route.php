<?php
namespace WolfansSm\Library\Share;

use WolfansSm\Library\Schedule\Schedule;

class Route {
    const END_TAG   = '_END_TAG';
    const START_TAG = 'START_TAG_';

    public static function encodeRouteId($routeId) {
        return self::START_TAG . $routeId . self::END_TAG;
    }

    public static function decodeRouteId($routeId) {
        return mb_substr($routeId, mb_strlen(self::START_TAG), -(mb_strlen(self::END_TAG)));
    }

    //    public function getRouteOptions($routeId, $options) {
    //        $minProcess   = isset($options['min_pnum']) && is_numeric($options['min_pnum']) ? $options['min_pnum'] : 1;
    //        $maxProcess   = isset($options['max_pnum']) && is_numeric($options['max_pnum']) ? $options['max_pnum'] : 1;
    //        $minExecTime  = isset($options['min_exectime']) && is_numeric($options['min_exectime']) ? $options['min_exectime'] : 0;
    //        $intervalTime = isset($options['interval_time']) && is_numeric($options['interval_time']) ? $options['interval_time'] : 60;
    //        $loopNum      = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 60;
    //        $loopSleepMs  = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
    //
    //        $params[] = rtrim(TRANSFER_PATH, '/') . '/' . ltrim($this->runPhpFile, '/');
    //        $params[] = '--routeid=' . $routeId;
    //        $params[] = '--loopnum=' . $loopNum;
    //        $params[] = '--loopsleepms=' . $loopSleepMs;
    //        $params[] = '> /dev/null & ';
    //    }

    /**
     * @param          $routeId
     * @param Schedule $schedule
     */
    public static function getParamStr($taskId, $routeId, $options) {
        $loopNum     = isset($options['loopnum']) && is_numeric($options['loopnum']) ? $options['loopnum'] : 60;
        $loopSleepMs = isset($options['loopsleepms']) && is_numeric($options['loopsleepms']) ? $options['loopsleepms'] : 100;
        $params[]    = '--taskid=' . $taskId;
        $params[]    = '--routeid=' . self::encodeRouteId($routeId);
        $params[]    = '--loopnum=' . $loopNum;
        $params[]    = '--loopsleepms=' . $loopSleepMs;
        $params[]    = '> /dev/null & ';
        return $params;
    }
}
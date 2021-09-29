<?php
namespace WolfansSm\Library\Share;

use WolfansSm\Library\Log\Log;

class Table {
    protected static $shareSchedule;
    protected static $shareCount;

    public static function init() {
        self::$shareSchedule = new \Swoole\Table(1024);
        self::$shareSchedule->column('min_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('max_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('robot_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('min_exectime', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('interval_time', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('loopnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('loopsleepms', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('current_exec_num', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('route_id', \Swoole\Table::TYPE_STRING, 256);
        self::$shareSchedule->column('task_id', \Swoole\Table::TYPE_STRING, 128);
        self::$shareSchedule->column('history_exec_num', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('all_exec_time', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('last_exec_time', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('crontab', \Swoole\Table::TYPE_STRING, 128);
        self::$shareSchedule->column('can_run_sec', \Swoole\Table::TYPE_INT, 8);//是否可执行
        self::$shareSchedule->column('log_num', \Swoole\Table::TYPE_INT, 8);//日志数量
        self::$shareSchedule->column('log_len', \Swoole\Table::TYPE_INT, 8);//日志长度
        self::$shareSchedule->create();
        self::$shareCount = new \Swoole\Table(1024);
        self::$shareCount->column('route_id', \Swoole\Table::TYPE_STRING, 256);
        self::$shareCount->column('stime', \Swoole\Table::TYPE_INT, 4);
        self::$shareCount->create();
    }

    public static function getShareSchedule() {
        return self::$shareSchedule;
    }

    public static function getShareCount() {
        return self::$shareCount;
    }

    /**
     * 获取配置
     *
     * @return mixed
     */
    public static function getSchedules() {
        return self::$shareSchedule;
    }

    public static function getSchedule($routeId) {
        return self::$shareSchedule;
    }

    public static function addSchedule($taskId, $routeId, array $options) {
        $options['robot_pnum']       = isset($options['min_pnum']) ? $options['min_pnum'] : 1;
        $options['route_id']         = $routeId;
        $options['task_id']          = $taskId;
        $options['current_exec_num'] = 0;
        $options['history_exec_num'] = 0;
        $options['all_exec_time']    = 0;
        $options['can_run_sec']      = 0;
        $options['last_exec_time']   = 0;
        $options['log_num']          = 0;
        $options['log_len']          = 0;
        $options['crontab']          = isset($options['crontab']) ? $options['crontab'] : '';
        self::$shareSchedule->set((string)$routeId, $options);
        Log::info("addSubTask\t" . $taskId . "\t" . $routeId . "\t" . json_encode($options));
    }

    /**
     * 根据进程号添加
     */
    public static function addCountByPid($pid, $routeId) {
        if (!self::$shareCount->exist((string)$pid)) {
            self::$shareCount->set((string)$pid, ['route_id' => $routeId, 'stime' => time()]);
        }
        if (self::$shareSchedule->exist((string)$routeId)) {
            self::$shareSchedule->incr((string)$routeId, 'current_exec_num', 1);
            self::$shareSchedule->incr((string)$routeId, 'history_exec_num', 1);
            self::$shareSchedule->set((string)$routeId, ['last_exec_time' => time()]);
        }
    }

    /**
     * 根据进程号减少
     */
    public static function subByPid($pid) {
        if (self::$shareCount->exist((string)$pid)) {
            $val      = self::$shareCount->get((string)$pid);
            $routeId  = $val['route_id'];
            $execTime = self::execTime($val['stime']);
            self::$shareCount->del((string)$pid);
            self::subCountByRouteId($routeId, $execTime);
            return $routeId;
        } else {
            return null;
        }
    }

    /**
     * 获取计数
     *
     * @param $routeId
     *
     * @return int
     */
    public static function getCountByRouteId($routeId) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            $routeVal = self::$shareSchedule->get((string)$routeId);
            return $routeVal['current_exec_num'];
        } else {
            return 0;
        }
    }

    /**
     * 获取计数
     *
     * @param $routeId
     *
     * @return int
     */
    public static function getMaxCountByRouteId($routeId) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            $routeVal = self::$shareSchedule->get((string)$routeId);
            return $routeVal['max_pnum'];
        } else {
            return 0;
        }
    }

    /**
     * 根据routeid减少计数
     *
     * @param $routeId
     */
    public static function subCountByRouteId($routeId, $execTime) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            self::$shareSchedule->decr((string)$routeId, 'current_exec_num', 1);
            self::$shareSchedule->incr((string)$routeId, 'all_exec_time', $execTime);
        }
    }

    /**
     * 增加任务
     *
     * @param $routeId
     */
    public static function addRunList($routeId) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            $res = self::$shareSchedule->set((string)$routeId, ['can_run_sec' => 1]);
        }
    }

    /**减少任务
     *
     * @param $routeId
     */
    public static function subRunList($routeId) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            self::$shareSchedule->set((string)$routeId, ['can_run_sec' => 0]);
        }
    }

    /**
     * 增加日志量记录
     *
     * @param $routeId
     * @param $logNum
     * @param $logLen
     */
    public static function addLogAllStat($routeId, $logNum, $logLen) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            $routeVal       = self::$shareSchedule->get((string)$routeId);
            $last_exec_time = intval($routeVal['last_exec_time']);
            $now            = time();
            $todaySec       = $now - strtotime("today");
            $hourSec        = $now - ($todaySec % 3600);
            if ($last_exec_time < $hourSec) {
                self::$shareSchedule->set((string)$routeId, ['log_num' => 0, 'log_len' => 0]);
            } else {
                self::$shareSchedule->incr((string)$routeId, 'log_num', (int)$logNum);
                self::$shareSchedule->incr((string)$routeId, 'log_len', (int)$logLen);
            }
        }
    }

    /**
     * 设置增量
     *
     * @param $routeId
     * @param $logNum
     * @param $logLen
     */
    public static function setLogTmpStat($routeId, $logNum, $logLen) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            self::$shareSchedule->set((string)$routeId, ['log_tmp_num' => (int)$logNum, 'log_tmp_len' => (int)$logLen]);
        }
    }

    protected static function execTime($startTime) {
        return time() - $startTime;
    }
}
<?php
namespace WolfansSm\Library\Share;

class Table {
    protected static $shareSchedule;
    protected static $shareCount;

    public static function init() {
        self::$shareSchedule = new \Swoole\Table(1024);
        self::$shareSchedule->column('min_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('min_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('max_pnum', \Swoole\Table::TYPE_INT, 4);
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
        $options['route_id']         = $routeId;
        $options['task_id']          = $taskId;
        $options['current_exec_num'] = 0;
        $options['history_exec_num'] = 0;
        $options['all_exec_time']    = 0;
        $options['can_run_sec']      = 0;
        $options['last_exec_time']   = 0;
        $options['crontab']          = isset($options['crontab']) ? $options['crontab'] : '';
        self::$shareSchedule->set((string)$routeId, $options);
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

    protected static function execTime($startTime) {
        return time() - $startTime;
    }
}
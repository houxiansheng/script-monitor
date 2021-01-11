<?php
namespace WolfansSm\Library\Share;

class Table {
    protected static $shareSchedule;
    protected static $shareCount;

    public static function init() {
        self::$shareSchedule = new Swoole\Table(1024);
        self::$shareSchedule->column('min_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('max_pnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('min_exectime', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('interval_time', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('loopnum', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('loopsleepms', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->column('current_exec_num', \Swoole\Table::TYPE_INT, 4);
        self::$shareSchedule->create();

        self::$shareCount = new Swoole\Table(1024);
        self::$shareCount->column('route_id', \Swoole\Table::TYPE_STRING, 256);
        self::$shareCount->column('stime', \Swoole\Table::TYPE_INT, 4);
        self::$shareCount->create();
    }

    public function getShareSchedule() {
        return self::$shareSchedule;
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

    public static function addSchedule($routeId, array $options) {
        $options['route_id']         = $routeId;
        $options['current_exec_num'] = 0;
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
            $routeVal = self::$shareSchedule->get((string)$routeId);
            $routeVal['current_exec_num']++;
            self::$shareSchedule->set((string)$routeId, $routeVal);
        }
    }

    /**
     * 根据进程号减少
     */
    public static function subByPid($pid) {
        if (self::$shareCount->exist((string)$pid)) {
            $val     = self::$shareCount->get((string)$pid);
            $routeId = $val['route_id'];
            self::subCountByRouteId($routeId);
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
    public static function subCountByRouteId($routeId) {
        if (self::$shareSchedule->exist((string)$routeId)) {
            $routeVal = self::$shareSchedule->get((string)$routeId);
            $routeVal['current_exec_num']++;
            $routeVal['current_exec_num'] >= 0 && self::$shareSchedule->set((string)$routeId, $routeVal);
        }
    }
}
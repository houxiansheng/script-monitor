<?php
/**
 * 命令基础类
 */
namespace WolfansSm\Library\Schedule;

abstract class Command {

    protected $taskId       = null;
    protected $routeList    = [];
    protected $scheduleList = [];

    public function __construct() {
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function getRouteList() {
        return $this->routeList;
    }

    /**
     * @return mixed
     */

    public function getScheduleList($routeId = null) {
        if ($routeId) {
            return isset($this->scheduleList[$routeId]) ? $this->scheduleList[$routeId] : [];
        } else {
            return $this->scheduleList;
        }
    }

    /**
     * 参数说明
     * task_id:任务脚本，确定由哪个脚本进行调度
     * min_pnum:该路由最小进程数
     * max_pnum:该路由最大进程数
     * min_exectime:预估脚本最小执行时间
     * interval_time：大于min_pnum后，每增加一个进程的间隔时间
     * loopnum：在monitorRun.php中for循环次数
     * loopsleepms:在monitorRun.php中每循环一次，睡眠时间（毫秒）
     */
    abstract function SetRoute();

    /**
     * 设置任务
     */
    abstract function SetSchedule();

}

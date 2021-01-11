<?php
/**
 * 任务类型
 */
namespace Wolfans\Sm\Schedule;

class Schedule {
    protected $taskId    = null;
    protected $routeId   = null;
    protected $options   = [];
    protected $schedules = [];

    public function __construct($taskId, $routeId) {
        $this->taskId  = $taskId;
        $this->routeId = $routeId;
    }

    /**
     * 设置运行参数
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    public function setOptions($key, $val) {
        $this->options[$key] = $val;
        return $this;
    }

    /**
     * 设置要执行的类
     *
     * @param           $key
     * @param \stdClass $class
     */
    public function setTask(\stdClass $class) {
        $this->schedules[] = $class;
    }

    /**
     * 获取任务id
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * 获取路由id
     *
     * @return null
     */
    public function getRouteId() {
        return $this->routeId;
    }

    /**
     * 获取任务列表
     *
     * @return array
     */
    public function getTaskList() {
        return $this->schedules;
    }

    public function getOptions() {
        return $this->getOptions();
    }
}
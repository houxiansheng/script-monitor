<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Core\ParseCrontab;
use WolfansSm\Library\Share\Table;

class Crontab {
    protected $lastTime = null;

    public function __construct() {
        $this->lastTime = time();
    }

    public function run() {
        $this->tick();
        \Swoole\Event::wait();
    }

    public function tick() {
        \Swoole\Timer::tick(1000, function () {
            $this->policy();
        });
    }

    /**
     * 监测需要fork的任务
     */
    public function policy() {
        $now = time();
        //避免丢失某1s数据，进行遍历
        for (; $this->lastTime <= $now; $this->lastTime++) {
            $second = $this->lastTime % 10;
            foreach (Table::getShareSchedule() as $options) {
                $routeId = isset($options['route_id']) ? $options['route_id'] : '';
                $crontab = isset($options['crontab']) ? $options['crontab'] : '';
                $secList = ParseCrontab::parse($crontab);
                if ($secList && is_array($secList) && isset($secList[$second])) {
                    Table::addRunList($routeId, $now);
                }
            }
        }
    }

}
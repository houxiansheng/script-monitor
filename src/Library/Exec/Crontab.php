<?php
namespace WolfansSm\Library\Exec;

use WolfansSm\Library\Core\ParseCrontab;
use WolfansSm\Library\Log\Log;
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
     * 是否存在任务
     */
    public function hasCrontab() {
        return Table::getShareSchedule()->count();
    }

    /**
     * 监测需要fork的任务
     */
    public function policy() {
        $now       = time();
        $remainder = $now % 60;
        //ParseCrontab::parse不能解析出0秒的任务，会自动解析为1s。所以跳过当前时间为0的情况
        if ($remainder == 0) {
            $now += 1;
        }
        //避免丢失某1s数据，进行遍历
        for (; $this->lastTime <= $now; $this->lastTime++) {
            $second = (int)date('s', $this->lastTime);
            foreach (Table::getShareSchedule() as $options) {
                $routeId = isset($options['route_id']) ? $options['route_id'] : '';
                $crontab = isset($options['crontab']) ? $options['crontab'] : '';
                $secList = ParseCrontab::parse($crontab);
                if ($secList && is_array($secList) && isset($secList[$second])) {
                    Table::addRunList($routeId, $now);
                    Log::crontab($routeId . "\tcrontab:" . $crontab . "\t runtime:" . date('Y-m-d H:i:s', $this->lastTime) . "\t" . json_encode($secList));
                }
            }
        }
    }

}
<?php
namespace Wolfans\Command;

class Fork {
    protected $class       = [];
    protected $routeList   = [];
    protected $execProcess = [];
    protected $command;
    protected $phpRoot     = null;
    //    protected $runPhpFile  = 'monitorRun.php';
    //    protected $shareTable;

    public function __construct() {
        $this->command = new Command();
        $this->phpRoot = Conf::get('PHP_ROOT');
    }

    public function setRouteList(array $routeList) {
        $this->routeList = $routeList;
    }

    public function run() {
        $this->ex();
        $this->forkP();
        sleep(4);
        foreach ($this->shareTable as $row) {
            var_dump($row);
        }

        exit();
        //        do {
        //            $this->forkP();
        //        } while ($this->waitP());
    }

    protected function forkP() {
        foreach ($this->routeList as $uniqKey => $route) {
            //            if (!isset($this->routeList[$uniqKey]['monitor_exec_num'])) {
            //                $this->routeList[$uniqKey]['monitor_exec_num'] = 0;
            //            }
            if (!$this->shareTable->exist(string($uniqKey))) {
                $this->shareTable->set(string($uniqKey), ['num' => 0]);
            }

            $routeId      = $this->command->encodeRouteId($uniqKey);
            $minProcess   = isset($route['min_pnum']) && is_numeric($route['min_pnum']) ? $route['min_pnum'] : 1;
            $maxProcess   = isset($route['max_pnum']) && is_numeric($route['max_pnum']) ? $route['max_pnum'] : 1;
            $minExecTime  = isset($route['min_exectime']) && is_numeric($route['min_exectime']) ? $route['min_exectime'] : 0;
            $intervalTime = isset($route['interval_time']) && is_numeric($route['interval_time']) ? $route['interval_time'] : 60;
            $loopNum      = isset($route['loopnum']) && is_numeric($route['loopnum']) ? $route['loopnum'] : 60;
            $loopSleepMs  = isset($route['loopsleepms']) && is_numeric($route['loopsleepms']) ? $route['loopsleepms'] : 100;

            $params   = [];
            $params[] = rtrim(TRANSFER_PATH, '/') . '/' . ltrim($this->runPhpFile, '/');
            $params[] = '--routeid=' . $routeId;
            $params[] = '--loopnum=' . $loopNum;
            $params[] = '--loopsleepms=' . $loopSleepMs;
            $params[] = '> /dev/null & ';
            //生成进程
            for (; $this->routeList[$uniqKey]['monitor_exec_num'] < $minProcess; $this->routeList[$uniqKey]['monitor_exec_num']++) {
                var_dump(implode(' ', $params));
                $process = new \Swoole\Process(function (\Swoole\Process $childProcess) use ($params) {
                    $childProcess->exec($this->phpRoot, $params); // exec 系统调用
                });
                $process->start();
                $pid                     = $process->pid;
                $this->execProcess[$pid] = $uniqKey;
                Log::info('process_num:' . $this->routeList[$uniqKey]['monitor_exec_num'] . ' cmd:' . implode(' ', $params));
            }
        }
    }

    protected function waitP() {
        $exilstList = \Swoole\Process::wait();
        if ($exilstList) {
            $pid       = $exilstList['pid'];
            $scriptKey = $this->execProcess[$pid];
            $this->routeList[$scriptKey]['monitor_exec_num']--;
            unset($this->execProcess[$pid]);
            return true;
        } else {
            return false;
        }
    }

    protected function ex() {
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            while ($ret = \Swoole\Process::wait(false)) {
                // create a new child process
                var_dump($ret);
                global $list;
                $list[] = json_encode($ret);
            }
        });
    }
}
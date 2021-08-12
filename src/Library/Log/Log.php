<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Log;

use WolfansSm\Library\Share\Table;

class Log {
    /**
     * @param $msg string
     */
    static function info($msg) {
        $str = date('Y-m-d H:i:s') . "\tinfo\t" . $msg . PHP_EOL;
        $des = '/data1/apache2/phplogs/wolfans-fork-' . date('YmdH') . '.log';
        self::write($str, $des);
    }

    static function error($msg) {
        $str = date('Y-m-d H:i:s') . "\terr\t" . $msg . PHP_EOL;
        $des = '/data1/apache2/phplogs/wolfans-fork-' . date('YmdH') . '.log';
        self::write($str, $des);
    }

    /**
     * 文件内容
     *
     * @param $msg
     * @param $des
     */
    static function write($msg, $des) {
        error_log($msg, 3, $des);
    }
}

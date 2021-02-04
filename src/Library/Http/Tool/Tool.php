<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Http\Tool;

use WolfansSm\Library\Http\App\Route;
use WolfansSm\Library\Share\Table;

class Tool {
    public static function isIp($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        } else {
            return false;
        }
    }
}
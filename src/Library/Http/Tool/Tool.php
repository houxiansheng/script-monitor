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

    public static function stats() {
        $cmd     = 'ps -eo pcpu,vsz,rss,command';
        $ret     = shell_exec("$cmd");
        $retArr  = explode("\n", $ret);
        $posList = $data = [];
        foreach ($retArr as $key => $retArrs) {
            if ($key == 0) {
                continue;
            }
            $retArrs = trim($retArrs);
            if (!$posList) {
                $pos = 0;
                while ($pos < mb_strlen($retArrs) && $pos < 1000 && count($posList) < 5) {
                    $newPos = strpos($retArrs, ' ', $pos);
                    if (is_bool($newPos)) {
                        break;
                    }
                    if ($newPos == $pos) {
                        $pos++;
                    } else {
                        $posList[] = $pos;
                        $pos       = $newPos;
                    }
                }
            }
            if (is_numeric(strpos($retArrs, 'wolfans-worker-'))) {
                $command = trim(substr($retArrs, $posList[3] + 15));
                $tmp     = [
                    'cpu'     => trim(substr($retArrs, $posList[0], $posList[1])),
                    'vsz'     => trim(substr($retArrs, $posList[1], $posList[2] - $posList[1])),
                    'rss'     => trim(substr($retArrs, $posList[2], $posList[3] - $posList[2])),
                    'command' => $command
                ];
                if (isset($data[$command])) {
                    $data[$command]['cpu'] = $tmp['cpu'] > $data[$command]['cpu'] ? $tmp['cpu'] : $data[$command]['cpu'];
                    $data[$command]['vsz'] = $tmp['vsz'] > $data[$command]['vsz'] ? $tmp['vsz'] : $data[$command]['vsz'];
                    $data[$command]['rss'] = $tmp['rss'] > $data[$command]['rss'] ? $tmp['rss'] : $data[$command]['rss'];
                } else {
                    $data[$command] = $tmp;
                }
            }
        }
        return $data;
    }

    /**
     * 转换为kb
     *
     * @param $num
     *
     * @return string
     */
    public static function getByte($num) {
        $num  = intval($num);
        $unit = ['KB', 'MB', 'GB', 'TB', 'PB'];
        $ue   = 'B';
        foreach ($unit as $u) {
            $num1 = $num >> 10;
            if ($num1 <= 0) {
                break;
            }
            $num = $num1;
            $ue  = $u;
        }
        return $num > 0 ? ($num . $ue) : '';
    }

    /**
     * 转换为十万
     *
     * @param $num
     *
     * @return string
     */

    public static function getNum($num) {
        $num  = intval($num);
        $unit = ['十', '百', '千', '万', '十万', '百万', '千万', '亿', '十亿', '百亿'];
        $ue   = '';
        foreach ($unit as $u) {
            $num1 = $num / 10;
            if ($num1 < 1) {
                break;
            }
            $num = $num1;
            $ue  = $u;
        }
        return $num > 0 ? (number_format($num, 2) . '(' . $ue . ')') : '';
    }
}
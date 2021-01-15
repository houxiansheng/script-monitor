<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Http\App;

use WolfansSm\Library\Share\Table;

class Route {
    public function index($route, $request) {
        if ($route == '/json') {
            return $this->json();
        } else {
            return $this->table();
        }
    }

    public function json() {
        $data = [];
        foreach (Table::getShareSchedule() as $options) {
            $data[] = $options;
        }
        return json_encode($data);
    }

    public function table() {
        $ipList = ['10.75.32.235:9501'];
        $task   = [];
        foreach ($ipList as $ip) {
            $json = file_get_contents('http://' . $ip . '/json');
            $arr  = @json_decode($json, true);
            is_array($arr) && $task[$ip] = $arr;
        }

        $html = '
            <style type="text/css">
            table.gridtable {
                font-family: verdana,arial,sans-serif;
                font-size:11px;
                color:#333333;
                border-width: 1px;
                border-color: #666666;
                border-collapse: collapse;
            }
            table.gridtable th {
                border-width: 1px;
                padding: 8px;
                border-style: solid;
                border-color: #666666;
                background-color: #dedede;
            }
            table.gridtable td {
                border-width: 1px;
                padding: 8px;
                border-style: solid;
                border-color: #666666;
                background-color: #ffffff;
            }
            </style>';
        $html .= '<table class="gridtable">';
        $html .= '<tr> <th>任务</th> <th>最大</th><th>最小</th><th>loop</th><th>sleep</th> <th>历史启动</th><th>总/平时时间</th><th>运行量</th> </tr>';
        foreach ($task as $ip => $schedule) {
            foreach (Table::getShareSchedule() as $options) {
                $aa   = $options['history_exec_num'] ? $options['history_exec_num'] : 1;
                $html .= '<tr> <td>' .
                    $options['route_id'] . '</td> <td>' .
                    $options['max_pnum'] . '</td><td>' .
                    $options['min_pnum'] . '</td><td>' .
                    $options['loopnum'] . '</td><td>' .
                    $options['loopsleepms'] . '</td><td>' .
                    $options['history_exec_num'] . '</td><td>' .
                    $options['all_exec_time'] . '/' . intval($options['all_exec_time'] / $aa) . '</td><td>' .
                    $options['current_exec_num'] . '</td></tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }
}
<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Http;

use WolfansSm\Library\Http\App\Route;
use WolfansSm\Library\Share\Table;

class Server {
    public function run($port, $allPort, $ipList) {
        if (is_numeric($port) && $port > 0) {
            $http = new \Swoole\Http\Server("0.0.0.0", $port);
            $http->on('request', function ($request, $response) use ($port, $allPort, $ipList) {
                $route      = isset($request->server['request_uri']) ? $request->server['request_uri'] : '/';
                $post       = isset($request->post) && is_array($request->post) ? $request->post : [];
                $get        = isset($request->get) && is_array($request->get) ? $request->get : [];
                $routeClass = new Route($allPort, $ipList);
                $response->end($routeClass->index($route, array_merge($post, $get)));
            });
            $http->start();
        }
    }
}
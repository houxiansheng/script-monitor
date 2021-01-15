<?php
/**
 * 注册要启动的任务
 */
namespace WolfansSm\Library\Http;

use WolfansSm\Library\Http\App\Route;
use WolfansSm\Library\Share\Table;

class Server {
    public function run() {
        $http = new \Swoole\Http\Server("0.0.0.0", 9501);
        $http->on('request', function ($request, $response) {
            $route = isset($request->server['request_uri']) ? $request->server['request_uri'] : '/';
            $post  = isset($request->post) && is_array($request->post) ? $request->post : [];
            $get   = isset($request->get) && is_array($request->get) ? $request->get : [];
            $response->end((new Route())->index($route, array_merge($post, $get)));
        });
        $http->start();
    }
}
<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Grocy\Services\WebSocketService;
use Grocy\Services\StockService;

require dirname(__DIR__) . '/packages/autoload.php';

// 初始化服务
$stockService = new StockService();
$webSocket = new WebSocketService($stockService);

// 创建WebSocket服务器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $webSocket
        )
    ),
    8080
);

echo "WebSocket Server started on port 8805\n";

$server->run(); 
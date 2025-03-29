<?php

namespace Grocy\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketService implements MessageComponentInterface
{
    protected $clients;
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->clients = new \SplObjectStorage;
        $this->stockService = $stockService;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'dashboard_update') {
            $dashboardData = $this->getDashboardData();
            $from->send(json_encode($dashboardData));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcastDashboardUpdate()
    {
        $data = $this->getDashboardData();
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }

    protected function getDashboardData()
    {
        // 获取实时数据
        return [
            'stockOverview' => [
                'total_items' => $this->stockService->getTotalItems(),
                'total_value' => $this->stockService->getTotalValue(),
                'items_at_risk' => $this->stockService->getItemsAtRisk(),
                'expiring_soon' => $this->stockService->getExpiringSoon()
            ],
            'stockTrend' => $this->stockService->getStockTrend()
        ];
    }
} 
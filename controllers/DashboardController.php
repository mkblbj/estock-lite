<?php

namespace Grocy\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function Overview(Request $request, Response $response, array $args)
    {
        // 获取库存概览数据
        $stockService = $this->getStockService();
        $currentStock = $stockService->GetCurrentStock();
        
        // 计算总商品数
        $totalProducts = $this->getDatabase()->products()->where('active = 1')->count();
        
        // 计算总在库商品数
        $productsInStock = count(array_filter($currentStock, function($item) {
            return $item->amount > 0;
        }));
        
        // 计算总库存价值
        $totalStockValue = array_reduce($currentStock, function($carry, $item) {
            return $carry + floatval($item->value);
        }, 0);

        // 获取库存不足商品数
        $stockMissing = $this->getDatabase()->stock_missing_products()->count();
        
        // 获取库存位置分布
        $locationDistribution = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT l.name as location, COUNT(*) as count
            FROM stock_current sc
            JOIN products p ON sc.product_id = p.id
            JOIN locations l ON p.location_id = l.id
            GROUP BY l.name
            ORDER BY count DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取商品分类分布
        $categoryDistribution = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT IFNULL(pg.name, '未分类') as category, COUNT(*) as count
            FROM stock_current sc
            JOIN products p ON sc.product_id = p.id
            LEFT JOIN product_groups pg ON p.product_group_id = pg.id
            GROUP BY pg.name
            ORDER BY count DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
        
        // 获取最近入库记录
        $recentAdditions = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT p.name, sl.amount, sl.row_created_timestamp as date
            FROM stock_log sl
            JOIN products p ON sl.product_id = p.id
            WHERE sl.transaction_type = 'purchase'
            ORDER BY sl.row_created_timestamp DESC
            LIMIT 5
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取最近出库记录
        $recentConsumptions = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT p.name, sl.amount, sl.row_created_timestamp as date
            FROM stock_log sl
            JOIN products p ON sl.product_id = p.id
            WHERE sl.transaction_type = 'consume'
            ORDER BY sl.row_created_timestamp DESC
            LIMIT 5
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取库存预警（低于最小库存量的商品）
        $lowStockProducts = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT p.name, p.min_stock_amount, smp.amount_missing, qu.name as qu_name
            FROM stock_missing_products smp
            JOIN products p ON smp.id = p.id
            JOIN quantity_units qu ON p.qu_id_stock = qu.id
            ORDER BY smp.amount_missing DESC
            LIMIT 10
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取当前库存商品数量排名
        $topStockProducts = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT p.name, sc.amount, sc.value, qu.name as qu_name
            FROM stock_current sc
            JOIN products p ON sc.product_id = p.id
            JOIN quantity_units qu ON p.qu_id_stock = qu.id
            ORDER BY sc.amount DESC
            LIMIT 10
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取30天内的库存变动趋势数据
        $trendDays = 30;
        $stockTrend = [];
        for ($i = $trendDays; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stockLog = $this->getDatabaseService()->ExecuteDbQuery("
                SELECT transaction_type, SUM(amount) as total_amount
                FROM stock_log
                WHERE transaction_type IN ('purchase', 'consume')
                AND date(row_created_timestamp) = ?
                GROUP BY transaction_type
            ", [$date])->fetchAll(\PDO::FETCH_OBJ);
                
            $purchases = 0;
            $consumptions = 0;
            
            foreach ($stockLog as $log) {
                if ($log->transaction_type === 'purchase') {
                    $purchases = $log->total_amount;
                } else if ($log->transaction_type === 'consume') {
                    $consumptions = $log->total_amount;
                }
            }
            
            $stockTrend[] = [
                'date' => $date,
                'purchases' => $purchases,
                'consumptions' => $consumptions
            ];
        }
        
        return $this->renderPage($response, 'dashboard', [
            'totalProducts' => $totalProducts,
            'productsInStock' => $productsInStock, 
            'totalStockValue' => $totalStockValue,
            'stockMissing' => $stockMissing,
            'locationDistribution' => json_encode($locationDistribution),
            'categoryDistribution' => json_encode($categoryDistribution),
            'recentAdditions' => $recentAdditions,
            'recentConsumptions' => $recentConsumptions,
            'lowStockProducts' => $lowStockProducts,
            'topStockProducts' => $topStockProducts,
            'stockTrend' => json_encode($stockTrend)
        ]);
    }
} 
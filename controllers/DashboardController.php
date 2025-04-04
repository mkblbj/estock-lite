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
            LIMIT 10
        ")->fetchAll(\PDO::FETCH_OBJ);
            
        // 获取最近出库记录
        $recentConsumptions = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT p.name, sl.amount, sl.row_created_timestamp as date
            FROM stock_log sl
            JOIN products p ON sl.product_id = p.id
            WHERE sl.transaction_type = 'consume'
            ORDER BY sl.row_created_timestamp DESC
            LIMIT 10
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
            
        // 获取30天内的库存变动趋势数据（实际上是最近30条记录）
        $trendDays = 30;
        $stockTrend = [];
        
        // 不使用日期范围，改为获取最近的记录
        $allStockData = $this->getDatabaseService()->ExecuteDbQuery("
            SELECT 
                date(row_created_timestamp) as record_date,
                transaction_type,
                SUM(amount) as total_amount
            FROM stock_log
            WHERE 
                (transaction_type = 'purchase' OR 
                 transaction_type = 'consume' OR
                 transaction_type = 'inventory-correction' OR
                 transaction_type = 'product-opened')
            GROUP BY date(row_created_timestamp), transaction_type
            ORDER BY record_date DESC
            LIMIT 60
        ")->fetchAll(\PDO::FETCH_ASSOC);
        
        // 记录原始数据用于调试
        error_log("所有库存记录数据: " . json_encode($allStockData));
        
        // 获取所有不同的日期
        $uniqueDates = [];
        foreach ($allStockData as $record) {
            $date = $record['record_date'];
            if (!in_array($date, $uniqueDates)) {
                $uniqueDates[] = $date;
            }
        }
        
        // 排序日期（降序）
        rsort($uniqueDates);
        
        // 只保留最近的30个日期
        $uniqueDates = array_slice($uniqueDates, 0, $trendDays);
        
        // 初始化每天的数据
        foreach ($uniqueDates as $date) {
            $stockTrend[$date] = [
                'date' => $date,
                'purchases' => 0,
                'consumptions' => 0
            ];
        }
        
        // 填充实际数据
        foreach ($allStockData as $record) {
            $date = $record['record_date'];
            $type = $record['transaction_type'];
            $amount = floatval($record['total_amount']);
            
            if (isset($stockTrend[$date])) {
                // 根据交易类型归类到入库或出库
                if ($type === 'purchase' || ($type === 'inventory-correction' && $amount > 0)) {
                    $stockTrend[$date]['purchases'] += abs($amount);
                } else if ($type === 'consume' || $type === 'product-opened' || 
                          ($type === 'inventory-correction' && $amount < 0)) {
                    $stockTrend[$date]['consumptions'] += abs($amount);
                }
            }
        }
        
        // 转换为数组格式
        $stockTrendArray = array_values($stockTrend);
        
        // 按日期升序排序
        usort($stockTrendArray, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        // 检查是否有非零数据
        $hasNonZeroData = false;
        foreach ($stockTrendArray as $item) {
            if (abs($item['purchases']) > 0 || abs($item['consumptions']) > 0) {
                $hasNonZeroData = true;
                break;
            }
        }
        
        // 直接检查是否有入库或出库记录
        if (!$hasNonZeroData) {
            $anyRecords = $this->getDatabaseService()->ExecuteDbQuery("
                SELECT COUNT(*) as count
                FROM stock_log
                WHERE 
                    (transaction_type = 'purchase' OR 
                     transaction_type = 'consume' OR
                     transaction_type = 'inventory-correction' OR
                     transaction_type = 'product-opened')
                AND amount != 0
                LIMIT 1
            ")->fetch(\PDO::FETCH_OBJ);
            
            $hasNonZeroData = ($anyRecords && $anyRecords->count > 0);
        }
        
        // 记录最终数据用于调试
        error_log("最终趋势数据: " . json_encode($stockTrendArray) . ", 有非零数据: " . ($hasNonZeroData ? "是" : "否"));
        
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
            'stockTrend' => json_encode($stockTrendArray),
            'hasStockTrendData' => $hasNonZeroData
        ]);
    }
} 
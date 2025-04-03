<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function Overview(Request $request, Response $response, array $args)
    {
        // 获取库存概览数据
        $stockService = $this->getStockService();
        $db = $this->getDatabase();
        
        // 使用原生SQL查询来处理复杂的连接和聚合
        $itemsAtRisk = $db->query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             JOIN stock_current sc ON sc.product_id = p.id
             WHERE p.active = 1 
             AND p.min_stock_amount > 0
             AND p.min_stock_amount > sc.amount"
        )->fetch()['count'];
        
        // 获取当前在库的产品数
        $productsInStockCount = $db->query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             JOIN stock_current sc ON p.id = sc.product_id
             WHERE p.active = 1 
             AND sc.amount > 0"
        )->fetch()['count'];

        $stockOverview = [
            'total_items' => $db->products()->where('active = 1')->count(),
            'total_value' => $db->stock_current()->sum('value'),
            'items_at_risk' => $itemsAtRisk,
            'products_in_stock' => $productsInStockCount
        ];

        // 获取库存预警数据
        $stockAlerts = $db->query(
            "SELECT 
                p.id, 
                p.name, 
                IFNULL(sc.amount, 0) as amount,
                p.min_stock_amount
             FROM products p
             LEFT JOIN stock_current sc ON sc.product_id = p.id
             WHERE p.active = 1 
             AND p.min_stock_amount > 0
             AND (IFNULL(sc.amount, 0) < p.min_stock_amount)
             ORDER BY (p.min_stock_amount - IFNULL(sc.amount, 0)) DESC
             LIMIT 10"
        )->fetchAll(\PDO::FETCH_OBJ);
        
        // 进行调试输出
        error_log('Stock alerts: ' . print_r($stockAlerts, true));

        // 获取最近入库记录
        $recentReceived = $db->stock_log()
            ->where('transaction_type', 'purchase')
            ->orderBy('row_created_timestamp', 'DESC')
            ->limit(5);

        // 获取即将过期商品
        $expiringSoon = $db->stock_current()
            ->where('best_before_date <= DATE(DATE(\'now\', \'localtime\'), \'+7 days\')')
            ->where('best_before_date > DATE(\'now\', \'localtime\')')
            ->orderBy('best_before_date')
            ->limit(5);
            
        // 获取当前在库的产品数据
        $productsInStock = $db->query(
            "SELECT 
                p.id as product_id, 
                p.name as product_name, 
                sc.amount, 
                IFNULL(sc.value, 0) as value
             FROM products p
             JOIN stock_current sc ON p.id = sc.product_id
             WHERE p.active = 1 
             AND sc.amount > 0
             ORDER BY sc.amount DESC
             LIMIT 10"
        )->fetchAll(\PDO::FETCH_OBJ);
        

        // 获取库存变化趋势数据（最近30天）
        $stockTrendData = $db->query(
            "WITH RECURSIVE dates(date) AS (
                SELECT date('now', '-29 days')
                UNION ALL
                SELECT date(date, '+1 day')
                FROM dates
                WHERE date < date('now')
            )
            SELECT 
                dates.date as date,
                COALESCE(
                    (SELECT SUM(s.amount)
                    FROM stock s
                    JOIN products p ON s.product_id = p.id
                    WHERE p.active = 1
                    AND DATE(s.row_created_timestamp) <= dates.date),
                    0
                ) as total_amount
            FROM dates
            ORDER BY dates.date ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // 获取商品分类分布
        $categoryDistribution = $db->query(
            "SELECT 
                COALESCE(pg.name, '未分类') as name, 
                COUNT(p.id) as count,
                SUM(CASE WHEN sc.amount > 0 THEN 1 ELSE 0 END) as in_stock_count
             FROM products p
             LEFT JOIN product_groups pg ON pg.id = p.product_group_id
             LEFT JOIN stock_current sc ON p.id = sc.product_id
             WHERE p.active = 1
             GROUP BY pg.id, pg.name
             HAVING count > 0
             ORDER BY count DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // 记录调试信息
        error_log('Stock Trend Data: ' . print_r($stockTrendData, true));
        error_log('Category Distribution Data: ' . print_r($categoryDistribution, true));

        return $this->renderPage($response, 'dashboard', [
            'stockOverview' => $stockOverview,
            'stockAlerts' => $stockAlerts,
            'recentReceived' => $recentReceived,
            'expiringSoon' => $expiringSoon,
            'stockTrendData' => $stockTrendData,
            'categoryDistribution' => $categoryDistribution,
            'productsInStock' => $productsInStock
        ]);
    }

    public function GetDashboardData(Request $request, Response $response)
    {
        $db = $this->getDatabase();
        
        // 使用原生SQL查询来处理复杂的连接和聚合
        $itemsAtRisk = $db->query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             JOIN stock_current sc ON sc.product_id = p.id
             WHERE p.active = 1 
             AND p.min_stock_amount > 0
             AND p.min_stock_amount > sc.amount"
        )->fetch()['count'];
        
        // 获取当前在库的产品数
        $productsInStockCount = $db->query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             JOIN stock_current sc ON p.id = sc.product_id
             WHERE p.active = 1 
             AND sc.amount > 0"
        )->fetch()['count'];
        
        $data = [
            'stockOverview' => [
                'total_items' => $db->products()->where('active = 1')->count(),
                'total_value' => $db->stock_current()->sum('value'),
                'items_at_risk' => $itemsAtRisk,
                'products_in_stock' => $productsInStockCount
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 
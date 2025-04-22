<?php

require_once __DIR__ . '/../helpers/DB.php';

class CreateShippingRecordsTable
{
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS shipping_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            courier_id INT NOT NULL,
            quantity INT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (courier_id) REFERENCES couriers(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // 执行SQL
        $pdo = DB::getInstance()->getPdo();
        $pdo->exec($sql);
        
        return $sql;
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS shipping_records";
        
        // 执行SQL
        $pdo = DB::getInstance()->getPdo();
        $pdo->exec($sql);
        
        return $sql;
    }
    
    public function seed()
    {
        $pdo = DB::getInstance()->getPdo();
        
        // 获取所有courier_id
        $stmt = $pdo->query("SELECT id FROM couriers LIMIT 3");
        $courierIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 如果存在courier记录，则添加测试数据
        if (!empty($courierIds)) {
            // 准备插入语句
            $stmt = $pdo->prepare("INSERT INTO shipping_records 
                (date, courier_id, quantity, notes) 
                VALUES (?, ?, ?, ?)");
            
            // 当前日期和前几天的日期
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $twoDaysAgo = date('Y-m-d', strtotime('-2 day'));
            
            // 插入测试数据
            $testData = [
                [$today, $courierIds[0], 5, '当日测试数据1'],
                [$today, $courierIds[1], 3, '当日测试数据2'],
                [$yesterday, $courierIds[0], 6, '昨日测试数据'],
                [$twoDaysAgo, $courierIds[0], 4, '前天测试数据'],
            ];
            
            foreach ($testData as $record) {
                $stmt->execute($record);
            }
            
            return "已添加" . count($testData) . "条测试数据";
        }
        
        return "无法添加测试数据，请先确保couriers表中有数据";
    }
} 
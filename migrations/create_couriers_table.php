<?php

require_once __DIR__ . '/../helpers/DB.php';

class CreateCouriersTable
{
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS couriers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            code VARCHAR(50),
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // 执行SQL
        $pdo = DB::getInstance()->getPdo();
        $pdo->exec($sql);
        
        return $sql;
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS couriers";
        
        // 执行SQL
        $pdo = DB::getInstance()->getPdo();
        $pdo->exec($sql);
        
        return $sql;
    }
    
    public function seed()
    {
        $seedData = [
            ['name' => '顺丰速运', 'code' => 'SF', 'is_active' => true, 'sort_order' => 1],
            ['name' => '中通快递', 'code' => 'ZTO', 'is_active' => true, 'sort_order' => 2],
            ['name' => '京东物流', 'code' => 'JD', 'is_active' => true, 'sort_order' => 3],
            ['name' => '圆通速递', 'code' => 'YTO', 'is_active' => true, 'sort_order' => 4],
            ['name' => '韵达速递', 'code' => 'YD', 'is_active' => true, 'sort_order' => 5]
        ];
        
        $pdo = DB::getInstance()->getPdo();
        foreach ($seedData as $data) {
            $isActive = $data['is_active'] ? 'TRUE' : 'FALSE';
            $sql = "INSERT INTO couriers (name, code, is_active, sort_order) VALUES (
                '{$data['name']}', 
                '{$data['code']}', 
                {$isActive}, 
                {$data['sort_order']}
            )";
            $pdo->exec($sql);
        }
        
        return "数据填充完成";
    }
} 
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
            remark TEXT DEFAULT NULL COMMENT '备注信息',
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
        // $seedData = [
        //     ['name' => 'ゆうパケット (1CM)', 'code' => 'up1', 'remark' => '国内知名快递公司，速度快，价格较高', 'is_active' => true, 'sort_order' => 1],
        //     ['name' => 'ゆうパケット (2CM)', 'code' => 'up2', 'remark' => '全国性快递公司，性价比高', 'is_active' => true, 'sort_order' => 2],
        //     ['name' => 'ゆうパケットパフ', 'code' => 'ypp', 'remark' => '电商自营物流，配送稳定', 'is_active' => true, 'sort_order' => 3],
        //     ['name' => 'クリップポスト (3CM)', 'code' => 'cp3', 'remark' => '全国连锁快递企业', 'is_active' => true, 'sort_order' => 4],
        //     ['name' => 'ゆうパック', 'code' => 'upk', 'remark' => '全国性快递企业，服务范围广', 'is_active' => true, 'sort_order' => 5] 
        // ];
        
        // $pdo = DB::getInstance()->getPdo();
        // foreach ($seedData as $data) {
        //     $isActive = $data['is_active'] ? 'TRUE' : 'FALSE';
        //     $sql = "INSERT INTO couriers (name, code, remark, is_active, sort_order) VALUES (
        //         '{$data['name']}', 
        //         '{$data['code']}', 
        //         '{$data['remark']}', 
        //         {$isActive}, 
        //         {$data['sort_order']}
        //     )";
        //     $pdo->exec($sql);
        // }
        
        // return "数据填充完成";

        // 可以添加测试数据
        // 暂不添加初始数据
        return "无初始数据需要填充";
    }
} 
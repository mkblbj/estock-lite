<?php
/**
 * 执行数据库迁移脚本
 */

// 引入迁移文件
require_once 'migrations/create_couriers_table.php';
require_once 'migrations/create_shipping_records_table.php';

try {
    echo "开始执行数据库迁移...\n";
    
    // 执行快递员表迁移
    echo "===== 快递员表迁移 =====\n";
    $couriersMigration = new CreateCouriersTable();
    
    echo "创建快递员表结构...\n";
    $couriersMigration->up();
    echo "快递员表结构创建成功!\n";
    
    echo "填充快递员初始数据...\n";
    $couriersMigration->seed();
    echo "快递员数据填充完成!\n";
    
    // 执行发货记录表迁移
    echo "===== 发货记录表迁移 =====\n";
    $shippingMigration = new CreateShippingRecordsTable();
    
    echo "创建发货记录表结构...\n";
    $shippingMigration->up();
    echo "发货记录表结构创建成功!\n";
    
    echo "填充发货记录初始数据...\n";
    $shippingMigration->seed();
    echo "发货记录数据填充完成!\n";
    
    echo "所有迁移执行成功!\n";
    
} catch (Exception $e) {
    die("执行迁移时出错: " . $e->getMessage() . "\n");
}
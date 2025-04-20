<?php
/**
 * 执行数据库迁移脚本
 */

// 引入迁移文件
require_once 'migrations/create_couriers_table.php';

try {
    echo "开始执行数据库迁移...\n";
    
    // 创建迁移实例
    $migration = new CreateCouriersTable();
    
    // 执行迁移的up方法
    echo "创建数据表结构...\n";
    $migration->up();
    echo "表结构创建成功!\n";
    
    // 执行seed方法填充数据
    echo "填充初始数据...\n";
    $migration->seed();
    echo "数据填充完成!\n";
    
    echo "迁移执行成功!\n";
    
} catch (Exception $e) {
    die("执行迁移时出错: " . $e->getMessage() . "\n");
}
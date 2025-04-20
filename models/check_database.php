<?php
/**
 * 数据库连接检查脚本
 * 用于验证数据库连接是否正常
 */

// 引入数据库助手类
require_once __DIR__ . '/../helpers/DB.php';

// 设置输出颜色
function coloredText($text, $color) {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m"
    ];
    
    $isTerminal = php_sapi_name() === 'cli';
    
    if ($isTerminal) {
        return $colors[$color] . $text . $colors['reset'];
    } else {
        $htmlColors = [
            'green' => 'color: green;',
            'red' => 'color: red;',
            'yellow' => 'color: orange;',
            'blue' => 'color: blue;'
        ];
        return "<span style=\"{$htmlColors[$color]}\">{$text}</span>";
    }
}

function printLine($text) {
    $isTerminal = php_sapi_name() === 'cli';
    echo $text . ($isTerminal ? "\n" : "<br>");
}

// 初始化输出
if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>数据库连接检查</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .result { background-color: #f5f5f5; padding: 15px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>数据库连接检查</h1>
        <div class='result'>";
}

printLine(coloredText("=== 数据库连接检查 ===", "blue"));
printLine("");

try {
    // 获取数据库连接实例
    $db = DB::getInstance();
    
    // 检查连接状态
    printLine(coloredText("✓ 成功连接到数据库", "green"));
    
    // 获取数据库信息
    $dbInfo = $db->fetch("SELECT version() as db_version, database() as db_name");
    
    if ($dbInfo) {
        printLine(coloredText("✓ 数据库名称: " . $dbInfo['db_name'], "green"));
        printLine(coloredText("✓ 数据库版本: " . $dbInfo['db_version'], "green"));
    }
    
    // 测试是否能访问快递公司表
    $tableExists = $db->fetch("SHOW TABLES LIKE 'couriers'");
    
    if ($tableExists) {
        printLine(coloredText("✓ 快递公司表存在", "green"));
        
        // 统计快递公司数量
        $count = $db->fetch("SELECT COUNT(*) as count FROM couriers");
        printLine(coloredText("✓ 快递公司数量: " . $count['count'], "green"));
        
        // 检查表结构
        $columns = $db->fetchAll("SHOW COLUMNS FROM couriers");
        
        printLine(coloredText("✓ 表结构:", "green"));
        foreach ($columns as $column) {
            printLine("  - " . $column['Field'] . " (" . $column['Type'] . ")");
        }
    } else {
        printLine(coloredText("✗ 快递公司表不存在", "red"));
        
        // 显示所有表
        printLine(coloredText("i 数据库中的表:", "yellow"));
        $tables = $db->fetchAll("SHOW TABLES");
        
        if (count($tables) > 0) {
            foreach ($tables as $table) {
                $tableName = reset($table);
                printLine("  - " . $tableName);
            }
        } else {
            printLine(coloredText("  没有表", "red"));
        }
    }
    
    // 检查数据库权限
    printLine(coloredText("i 数据库权限检查:", "yellow"));
    
    // 测试SELECT权限
    try {
        $db->query("SELECT 1");
        printLine(coloredText("✓ SELECT权限: 正常", "green"));
    } catch (Exception $e) {
        printLine(coloredText("✗ SELECT权限: 失败 - " . $e->getMessage(), "red"));
    }
    
    // 测试INSERT权限
    try {
        $db->beginTransaction();
        
        if ($tableExists) {
            $id = $db->query("INSERT INTO couriers (name, code, is_active, sort_order) VALUES ('测试权限', 'TEST', 1, 999)");
            printLine(coloredText("✓ INSERT权限: 正常", "green"));
            
            // 测试UPDATE权限
            try {
                $db->query("UPDATE couriers SET sort_order = 1000 WHERE code = 'TEST' AND name = '测试权限'");
                printLine(coloredText("✓ UPDATE权限: 正常", "green"));
            } catch (Exception $e) {
                printLine(coloredText("✗ UPDATE权限: 失败 - " . $e->getMessage(), "red"));
            }
            
            // 测试DELETE权限
            try {
                $db->query("DELETE FROM couriers WHERE code = 'TEST' AND name = '测试权限'");
                printLine(coloredText("✓ DELETE权限: 正常", "green"));
            } catch (Exception $e) {
                printLine(coloredText("✗ DELETE权限: 失败 - " . $e->getMessage(), "red"));
            }
        } else {
            printLine(coloredText("i 跳过INSERT/UPDATE/DELETE测试，因为表不存在", "yellow"));
        }
        
        $db->rollBack();
    } catch (Exception $e) {
        $db->rollBack();
        printLine(coloredText("✗ INSERT权限: 失败 - " . $e->getMessage(), "red"));
    }
    
    printLine("");
    printLine(coloredText("数据库连接检查完成", "blue"));
    
} catch (Exception $e) {
    printLine(coloredText("✗ 数据库连接失败: " . $e->getMessage(), "red"));
    
    // 检查配置文件
    $configFile = __DIR__ . '/../data/config.php';
    if (file_exists($configFile)) {
        printLine(coloredText("i 配置文件存在，检查配置参数", "yellow"));
        
        // 显示配置文件
        $configContent = file_get_contents($configFile);
        
        // 提取并显示连接信息，隐藏敏感信息
        if (preg_match('/DB_HOST.*?=.*?[\'"](.+?)[\'"]/', $configContent, $matches)) {
            printLine("  DB_HOST = " . $matches[1]);
        }
        
        if (preg_match('/DB_DATABASE.*?=.*?[\'"](.+?)[\'"]/', $configContent, $matches)) {
            printLine("  DB_DATABASE = " . $matches[1]);
        }
        
        if (preg_match('/DB_USERNAME.*?=.*?[\'"](.+?)[\'"]/', $configContent, $matches)) {
            printLine("  DB_USERNAME = " . $matches[1]);
        }
        
        if (preg_match('/DB_PASSWORD.*?=.*?[\'"](.+?)[\'"]/', $configContent, $matches)) {
            printLine("  DB_PASSWORD = ****** (出于安全原因已隐藏)");
        }
        
        if (preg_match('/DB_PORT.*?=.*?[\'"]?(\d+)[\'"]?/', $configContent, $matches)) {
            printLine("  DB_PORT = " . $matches[1]);
        }
    } else {
        printLine(coloredText("✗ 配置文件不存在: " . $configFile, "red"));
    }
}

// 结束输出
if (php_sapi_name() !== 'cli') {
    echo "</div>
    </div>
</body>
</html>";
} 
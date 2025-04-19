<?php

// 设置工作目录
chdir(__DIR__);

// 定义数据路径
$datapath = __DIR__ . '/data';
define('GROCY_DATAPATH', $datapath);

// 定义设置函数，避免与helpers冲突
if (!function_exists('MySetting')) {
    function MySetting($name, $value) {
        if (!defined('GROCY_' . $name)) {
            // 检查设置覆盖文件
            $settingOverrideFile = GROCY_DATAPATH . '/settingoverrides/' . $name . '.txt';
            if (file_exists($settingOverrideFile)) {
                define('GROCY_' . $name, trim(file_get_contents($settingOverrideFile)));
            } elseif (getenv('GROCY_' . $name) !== false) {
                // 环境变量覆盖
                define('GROCY_' . $name, getenv('GROCY_' . $name));
            } else {
                define('GROCY_' . $name, $value);
            }
        }
    }
}

// 引入必要的设置
MySetting('MODE', 'production');

// 定义直接访问数据库的函数
function GetPDO()
{
    $pdo = new PDO('sqlite:' . GROCY_DATAPATH . '/grocy.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// 创建 migrations 表（如果不存在）
$pdo = GetPDO();
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (migration INTEGER NOT NULL PRIMARY KEY UNIQUE, execution_time_timestamp DATETIME DEFAULT (datetime('now', 'localtime')))");

// 获取所有迁移文件
$migrationFiles = [];
foreach (new FilesystemIterator(__DIR__ . '/migrations') as $file) {
    $migrationFiles[$file->getBasename()] = $file;
}
ksort($migrationFiles);

// 定义特殊迁移ID
$EMERGENCY_MIGRATION_ID = 9999;
$DOALWAYS_MIGRATION_ID = 8888;

// 执行迁移
echo "开始执行数据库迁移...\n";
$migrationCounter = 0;

foreach ($migrationFiles as $migrationKey => $migrationFile) {
    if ($migrationFile->getExtension() === 'php') {
        // PHP 迁移暂不支持，因为需要完整的服务结构
        $migrationNumber = ltrim($migrationFile->getBasename('.php'), '0');
        echo "跳过 PHP 迁移文件 {$migrationFile->getBasename()} (需要完整服务支持)\n";
    } elseif ($migrationFile->getExtension() === 'sql') {
        $migrationNumber = ltrim($migrationFile->getBasename('.sql'), '0');
        
        // 检查迁移是否已执行
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :id');
        $stmt->bindParam(':id', $migrationNumber, PDO::PARAM_INT);
        $stmt->execute();
        $rowCount = $stmt->fetchColumn();
        
        if ($rowCount == 0 || $migrationNumber == $EMERGENCY_MIGRATION_ID || $migrationNumber == $DOALWAYS_MIGRATION_ID) {
            try {
                echo "执行迁移: {$migrationFile->getBasename()}\n";
                $pdo->beginTransaction();
                
                // 执行SQL文件内容
                $sql = file_get_contents($migrationFile->getPathname());
                $pdo->exec($sql);
                
                // 记录迁移执行
                if ($migrationNumber != $EMERGENCY_MIGRATION_ID && $migrationNumber != $DOALWAYS_MIGRATION_ID) {
                    $insertStmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:id)');
                    $insertStmt->bindParam(':id', $migrationNumber, PDO::PARAM_INT);
                    $insertStmt->execute();
                    $migrationCounter++;
                }
                
                $pdo->commit();
                echo "成功执行迁移: {$migrationFile->getBasename()}\n";
            } catch (Exception $ex) {
                $pdo->rollback();
                echo "迁移失败: {$migrationFile->getBasename()} - " . $ex->getMessage() . "\n";
            }
        } else {
            echo "跳过已执行的迁移: {$migrationFile->getBasename()}\n";
        }
    }
}

// 如果有执行新的迁移，尝试优化数据库
if ($migrationCounter > 0) {
    try {
        // 确保没有活跃的事务
        $pdo = null;
        // 重新连接并执行VACUUM
        $pdo = GetPDO();
        $pdo->exec('VACUUM');
        echo "数据库已优化(VACUUM)\n";
    } catch (Exception $ex) {
        echo "数据库优化失败: " . $ex->getMessage() . "\n";
    }
}

echo "数据库迁移完成！共执行 {$migrationCounter} 个迁移文件\n"; 
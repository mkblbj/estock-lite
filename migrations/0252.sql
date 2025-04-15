CREATE TABLE courier_types (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    active TINYINT NOT NULL DEFAULT 1,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 添加几个默认的快递类型
INSERT INTO courier_types (name, description) VALUES ('顺丰', '顺丰速运');
INSERT INTO courier_types (name, description) VALUES ('圆通', '圆通速递');
INSERT INTO courier_types (name, description) VALUES ('中通', '中通快递');
INSERT INTO courier_types (name, description) VALUES ('申通', '申通快递');
INSERT INTO courier_types (name, description) VALUES ('韵达', '韵达快递');
INSERT INTO courier_types (name, description) VALUES ('邮政', '中国邮政');
INSERT INTO courier_types (name, description) VALUES ('EMS', 'EMS特快专递');
INSERT INTO courier_types (name, description) VALUES ('其他', '其他快递类型');

-- 添加权限
INSERT OR REPLACE INTO permission_hierarchy (name, parent) VALUES ('ADMIN_COURIER', 'ADMIN');

-- 更新版本号
UPDATE migration_numbers SET migration_number = 252 WHERE name = 'current_migration_number'; 
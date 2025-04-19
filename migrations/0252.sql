-- 创建快递类型表
CREATE TABLE courier_types (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    active TINYINT NOT NULL DEFAULT 1,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 创建快递发件记录表
CREATE TABLE courier_entries (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    courier_type_id INTEGER NOT NULL,
    entry_date DATE NOT NULL,
    count INTEGER NOT NULL DEFAULT 0,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY(courier_type_id) REFERENCES courier_types(id)
);

-- 创建索引以提高查询性能
CREATE INDEX courier_entries_date_idx ON courier_entries(entry_date);
CREATE INDEX courier_entries_type_idx ON courier_entries(courier_type_id);
CREATE INDEX courier_entries_type_date_idx ON courier_entries(courier_type_id, entry_date);

-- 创建统计视图
CREATE VIEW courier_statistics AS
SELECT 
    ce.entry_date,
    ct.name as courier_name,
    ct.id as courier_id,
    ce.count
FROM courier_entries ce
JOIN courier_types ct ON ce.courier_type_id = ct.id
WHERE ct.active = 1;

-- 创建函数供统计查询使用
CREATE VIEW courier_daily_summary AS
SELECT 
    entry_date,
    SUM(count) as total_count,
    GROUP_CONCAT(courier_name || ': ' || count) as details
FROM courier_statistics
GROUP BY entry_date;

CREATE VIEW courier_type_summary AS
SELECT 
    courier_id,
    courier_name,
    SUM(count) as total_count
FROM courier_statistics
GROUP BY courier_id, courier_name; 
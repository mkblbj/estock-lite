CREATE TABLE courier_data (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    courier_type_id INTEGER NOT NULL,
    date DATE NOT NULL,
    package_count INTEGER NOT NULL,
    remarks TEXT,
    created_by INTEGER,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (courier_type_id) REFERENCES courier_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE(courier_type_id, date)
);

-- 添加索引以优化查询性能
CREATE INDEX idx_courier_data_date ON courier_data(date);
CREATE INDEX idx_courier_data_courier_type_id ON courier_data(courier_type_id);

-- 更新版本号
UPDATE migration_numbers SET migration_number = 253 WHERE name = 'current_migration_number'; 
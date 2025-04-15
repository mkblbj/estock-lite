-- 创建项目任务进度表
CREATE TABLE project_tasks (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    project_name TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    percentage INTEGER NOT NULL DEFAULT 0,
    priority INTEGER NOT NULL DEFAULT 0,
    deadline DATETIME,
    assigned_to TEXT,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime')),
    last_updated_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 创建索引以加快按项目名称查询的速度
CREATE INDEX project_tasks_project_name_idx ON project_tasks(project_name);

-- 创建项目任务历史表，用于记录任务状态变更历史
CREATE TABLE project_task_history (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    task_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    percentage INTEGER NOT NULL,
    changed_by TEXT,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY(task_id) REFERENCES project_tasks(id) ON DELETE CASCADE
);

-- 创建索引以加快按任务ID查询历史记录的速度
CREATE INDEX project_task_history_task_id_idx ON project_task_history(task_id);

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

-- 添加一些初始数据
INSERT INTO courier_types (name, description, active) VALUES 
('顺丰速运', '顺丰快递服务', 1),
('京东物流', '京东快递服务', 1),
('中通快递', '中通快递服务', 1),
('圆通速递', '圆通快递服务', 1),
('韵达快递', '韵达快递服务', 1),
('申通快递', '申通快递服务', 1),
('邮政EMS', '邮政特快专递', 1);

-- 添加一些测试数据（最近7天的随机数据）
INSERT INTO courier_entries (courier_type_id, entry_date, count) VALUES 
(1, date('now', '-6 day'), 15),
(2, date('now', '-6 day'), 8),
(3, date('now', '-6 day'), 12),
(4, date('now', '-6 day'), 10),
(5, date('now', '-6 day'), 6),
(6, date('now', '-6 day'), 9),
(7, date('now', '-6 day'), 3),

(1, date('now', '-5 day'), 18),
(2, date('now', '-5 day'), 10),
(3, date('now', '-5 day'), 14),
(4, date('now', '-5 day'), 9),
(5, date('now', '-5 day'), 7),
(6, date('now', '-5 day'), 8),
(7, date('now', '-5 day'), 4),

(1, date('now', '-4 day'), 22),
(2, date('now', '-4 day'), 12),
(3, date('now', '-4 day'), 15),
(4, date('now', '-4 day'), 11),
(5, date('now', '-4 day'), 9),
(6, date('now', '-4 day'), 8),
(7, date('now', '-4 day'), 5),

(1, date('now', '-3 day'), 20),
(2, date('now', '-3 day'), 11),
(3, date('now', '-3 day'), 16),
(4, date('now', '-3 day'), 12),
(5, date('now', '-3 day'), 8),
(6, date('now', '-3 day'), 10),
(7, date('now', '-3 day'), 6),

(1, date('now', '-2 day'), 25),
(2, date('now', '-2 day'), 15),
(3, date('now', '-2 day'), 18),
(4, date('now', '-2 day'), 13),
(5, date('now', '-2 day'), 10),
(6, date('now', '-2 day'), 12),
(7, date('now', '-2 day'), 8),

(1, date('now', '-1 day'), 21),
(2, date('now', '-1 day'), 13),
(3, date('now', '-1 day'), 17),
(4, date('now', '-1 day'), 14),
(5, date('now', '-1 day'), 11),
(6, date('now', '-1 day'), 9),
(7, date('now', '-1 day'), 7),

(1, date('now'), 23),
(2, date('now'), 14),
(3, date('now'), 19),
(4, date('now'), 15),
(5, date('now'), 10),
(6, date('now'), 11),
(7, date('now'), 6);

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
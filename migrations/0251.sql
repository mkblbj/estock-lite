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
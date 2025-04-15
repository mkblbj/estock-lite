-- 重新创建courier_statistics视图
-- 这个迁移脚本是为了确保视图存在，防止"no such table: courier_statistics"错误

-- 先检查courier_types和courier_entries表是否存在
SELECT CASE
  WHEN NOT EXISTS(SELECT 1 FROM sqlite_master WHERE type='table' AND name='courier_types') THEN
    (SELECT RAISE(FAIL, 'courier_types表不存在，请先创建表'))
  ELSE
    (SELECT 1)
END;

SELECT CASE
  WHEN NOT EXISTS(SELECT 1 FROM sqlite_master WHERE type='table' AND name='courier_entries') THEN
    (SELECT RAISE(FAIL, 'courier_entries表不存在，请先创建表'))
  ELSE
    (SELECT 1)
END;

-- 删除现有视图并重新创建
DROP VIEW IF EXISTS courier_statistics;
CREATE VIEW courier_statistics AS
SELECT 
    ce.entry_date,
    ct.name as courier_name,
    ct.id as courier_id,
    ce.count
FROM courier_entries ce
JOIN courier_types ct ON ce.courier_type_id = ct.id
WHERE ct.active = 1;

-- 再检查视图是否正确创建
SELECT CASE
  WHEN NOT EXISTS(SELECT 1 FROM sqlite_master WHERE type='view' AND name='courier_statistics') THEN
    (SELECT RAISE(FAIL, 'courier_statistics视图创建失败'))
  ELSE
    (SELECT 1)
END;

-- 确保视图相关的其他视图也存在
DROP VIEW IF EXISTS courier_daily_summary;
CREATE VIEW courier_daily_summary AS
SELECT 
    entry_date,
    SUM(count) as total_count,
    GROUP_CONCAT(courier_name || ': ' || count) as details
FROM courier_statistics
GROUP BY entry_date;

DROP VIEW IF EXISTS courier_type_summary;
CREATE VIEW courier_type_summary AS
SELECT 
    courier_id,
    courier_name,
    SUM(count) as total_count
FROM courier_statistics
GROUP BY courier_id, courier_name; 
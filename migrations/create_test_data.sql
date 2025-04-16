-- 添加最近30天的测试数据，确保展示时能看到统计图表
-- 脚本创建日期：2025-04-15

-- 首先确保所有快递类型都是活跃状态
UPDATE courier_types SET active = 1;

-- 删除可能存在的当前日期的测试数据，防止重复
DELETE FROM courier_entries 
WHERE entry_date >= date('now', '-30 day')
  AND entry_date <= date('now');

-- 添加最近30天的测试数据
INSERT INTO courier_entries (courier_type_id, entry_date, count) VALUES 
-- 今天的数据
(1, date('now'), 25),
(2, date('now'), 15),
(3, date('now'), 18),
(4, date('now'), 10),
(5, date('now'), 8),
(6, date('now'), 12),
(7, date('now'), 5),

-- 昨天的数据
(1, date('now', '-1 day'), 22),
(2, date('now', '-1 day'), 13),
(3, date('now', '-1 day'), 16),
(4, date('now', '-1 day'), 9),
(5, date('now', '-1 day'), 7),
(6, date('now', '-1 day'), 11),
(7, date('now', '-1 day'), 4),

-- 2天前的数据
(1, date('now', '-2 day'), 24),
(2, date('now', '-2 day'), 14),
(3, date('now', '-2 day'), 17),
(4, date('now', '-2 day'), 12),
(5, date('now', '-2 day'), 9),
(6, date('now', '-2 day'), 10),
(7, date('now', '-2 day'), 6),

-- 3天前的数据
(1, date('now', '-3 day'), 21),
(2, date('now', '-3 day'), 12),
(3, date('now', '-3 day'), 15),
(4, date('now', '-3 day'), 10),
(5, date('now', '-3 day'), 8),
(6, date('now', '-3 day'), 9),
(7, date('now', '-3 day'), 3),

-- 7天前的数据
(1, date('now', '-7 day'), 23),
(2, date('now', '-7 day'), 14),
(3, date('now', '-7 day'), 19),
(4, date('now', '-7 day'), 11),
(5, date('now', '-7 day'), 6),
(6, date('now', '-7 day'), 8),
(7, date('now', '-7 day'), 5),

-- 14天前的数据
(1, date('now', '-14 day'), 20),
(2, date('now', '-14 day'), 12),
(3, date('now', '-14 day'), 16),
(4, date('now', '-14 day'), 9),
(5, date('now', '-14 day'), 7),
(6, date('now', '-14 day'), 10),
(7, date('now', '-14 day'), 4),

-- 21天前的数据
(1, date('now', '-21 day'), 19),
(2, date('now', '-21 day'), 11),
(3, date('now', '-21 day'), 15),
(4, date('now', '-21 day'), 8),
(5, date('now', '-21 day'), 5),
(6, date('now', '-21 day'), 7),
(7, date('now', '-21 day'), 3),

-- 30天前的数据
(1, date('now', '-30 day'), 18),
(2, date('now', '-30 day'), 10),
(3, date('now', '-30 day'), 14),
(4, date('now', '-30 day'), 7),
(5, date('now', '-30 day'), 4),
(6, date('now', '-30 day'), 6),
(7, date('now', '-30 day'), 2);

-- 确保视图存在
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
const db = require('./index');

/**
 * 执行数据库迁移
 */
async function runMigrations() {
  try {
    await db.connect();
    console.log('开始执行数据库迁移...');
    
    // 创建couriers表
    await createCouriersTable();
    
    // 创建shipping_records表
    await createShippingRecordsTable();
    
    // 添加测试数据
    await seedData();
    
    console.log('数据库迁移完成！');
    process.exit(0);
  } catch (error) {
    console.error('数据库迁移失败:', error);
    process.exit(1);
  }
}

/**
 * 创建快递公司表
 */
async function createCouriersTable() {
  const sql = `
    CREATE TABLE IF NOT EXISTS couriers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL UNIQUE,
      code VARCHAR(50),
      remark TEXT DEFAULT NULL COMMENT '备注信息',
      is_active BOOLEAN DEFAULT TRUE,
      sort_order INT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  `;
  
  await db.query(sql);
  console.log('couriers表创建成功');
}

/**
 * 创建发货记录表
 */
async function createShippingRecordsTable() {
  const sql = `
    CREATE TABLE IF NOT EXISTS shipping_records (
      id INT AUTO_INCREMENT PRIMARY KEY,
      date DATE NOT NULL,
      courier_id INT NOT NULL,
      quantity INT NOT NULL,
      notes TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (courier_id) REFERENCES couriers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  `;
  
  await db.query(sql);
  console.log('shipping_records表创建成功');
}

/**
 * 添加测试数据
 */
async function seedData() {
  // 检查是否已经有数据
  const [rows] = await db.pool.execute('SELECT COUNT(*) as count FROM couriers');
  if (rows[0].count > 0) {
    console.log('已存在数据，跳过测试数据创建');
    return;
  }
  
  // 添加测试快递公司
  const couriersSql = `
    INSERT INTO couriers (name, code, remark, is_active, sort_order) VALUES
    ('ゆうパケット (1CM)', 'up1', '国内知名快递公司，速度快，价格较高', 1, 1),
    ('ゆうパケット (2CM)', 'up2', '全国性快递公司，性价比高', 1, 2),
    ('ゆうパケットパフ', 'ypp', '电商自营物流，配送稳定', 1, 3),
    ('クリップポスト (3CM)', 'cp3', '全国连锁快递企业', 1, 4),
    ('ゆうパック', 'upk', '全国性快递企业，服务范围广', 1, 5)
  `;
  
  await db.query(couriersSql);
  console.log('测试快递公司数据添加成功');
  
  // 添加测试发货记录
  const today = new Date().toISOString().slice(0, 10);
  const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
  const twoDaysAgo = new Date(Date.now() - 172800000).toISOString().slice(0, 10);
  
  const recordsSql = `
    INSERT INTO shipping_records (date, courier_id, quantity, notes) VALUES
    (?, 1, 5, '当日测试数据1'),
    (?, 2, 3, '当日测试数据2'),
    (?, 1, 6, '昨日测试数据'),
    (?, 1, 4, '前天测试数据')
  `;
  
  await db.query(recordsSql, [today, today, yesterday, twoDaysAgo]);
  console.log('测试发货记录数据添加成功');
}

// 执行迁移
runMigrations(); 
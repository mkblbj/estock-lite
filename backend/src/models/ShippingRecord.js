const db = require('../db');

class ShippingRecord {
  constructor() {
    this.table = 'shipping_records';
  }

  /**
   * 获取发货记录列表（带分页和筛选）
   * @param {Object} options 查询选项
   * @returns {Promise<Array>} 发货记录列表
   */
  async getAll(options = {}) {
    let sql = `SELECT sr.*, c.name as courier_name 
              FROM ${this.table} sr
              LEFT JOIN couriers c ON sr.courier_id = c.id`;
    
    const params = [];
    const whereClauses = [];
    
    // 按日期筛选
    if (options.date) {
      whereClauses.push("sr.date = ?");
      params.push(options.date);
    }
    
    // 按日期范围筛选
    if (options.date_from) {
      whereClauses.push("sr.date >= ?");
      params.push(options.date_from);
    }
    
    if (options.date_to) {
      whereClauses.push("sr.date <= ?");
      params.push(options.date_to);
    }
    
    // 按快递类型筛选
    if (options.courier_id) {
      whereClauses.push("sr.courier_id = ?");
      params.push(parseInt(options.courier_id, 10));
    }
    
    // 按多个快递类型筛选
    if (options.courier_ids && Array.isArray(options.courier_ids) && options.courier_ids.length > 0) {
      const placeholders = options.courier_ids.map(() => '?').join(',');
      whereClauses.push(`sr.courier_id IN (${placeholders})`);
      params.push(...options.courier_ids.map(id => parseInt(id, 10)));
    }
    
    // 按数量范围筛选
    if (options.min_quantity !== undefined) {
      whereClauses.push("sr.quantity >= ?");
      params.push(parseInt(options.min_quantity, 10));
    }
    
    if (options.max_quantity !== undefined) {
      whereClauses.push("sr.quantity <= ?");
      params.push(parseInt(options.max_quantity, 10));
    }
    
    // 按备注关键词搜索
    if (options.notes_search) {
      whereClauses.push("sr.notes LIKE ?");
      params.push(`%${options.notes_search}%`);
    }
    
    // 组合WHERE子句
    if (whereClauses.length > 0) {
      sql += " WHERE " + whereClauses.join(" AND ");
    }
    
    // 排序
    const sortBy = this.validateSortField(options.sort_by) || 'date';
    const sortOrder = (options.sort_order || '').toUpperCase() === 'ASC' ? 'ASC' : 'DESC';
    
    sql += ` ORDER BY sr.${sortBy} ${sortOrder}`;
    
    // 分页
    if (options.page && options.per_page) {
      const page = Math.max(1, parseInt(options.page, 10));
      const perPage = Math.max(1, parseInt(options.per_page, 10));
      const offset = (page - 1) * perPage;
      
      // 使用整数值而不是占位符
      sql += ` LIMIT ${offset}, ${perPage}`;
    }
    
    return await db.query(sql, params);
  }

  /**
   * 验证排序字段是否合法
   * @param {string} field 字段名
   * @returns {string|null} 合法字段名或null
   */
  validateSortField(field) {
    const allowedSortFields = ['id', 'date', 'courier_id', 'quantity', 'created_at', 'updated_at'];
    return allowedSortFields.includes(field) ? field : null;
  }

  /**
   * 获取记录总数（用于分页）
   * @param {Object} options 查询选项
   * @returns {Promise<number>} 记录总数
   */
  async count(options = {}) {
    let sql = `SELECT COUNT(*) as total FROM ${this.table} sr`;
    
    const params = [];
    const whereClauses = [];
    
    // 按日期筛选
    if (options.date) {
      whereClauses.push("sr.date = ?");
      params.push(options.date);
    }
    
    // 按日期范围筛选
    if (options.date_from) {
      whereClauses.push("sr.date >= ?");
      params.push(options.date_from);
    }
    
    if (options.date_to) {
      whereClauses.push("sr.date <= ?");
      params.push(options.date_to);
    }
    
    // 按快递类型筛选
    if (options.courier_id) {
      whereClauses.push("sr.courier_id = ?");
      params.push(parseInt(options.courier_id, 10));
    }
    
    // 按多个快递类型筛选
    if (options.courier_ids && Array.isArray(options.courier_ids) && options.courier_ids.length > 0) {
      const placeholders = options.courier_ids.map(() => '?').join(',');
      whereClauses.push(`sr.courier_id IN (${placeholders})`);
      params.push(...options.courier_ids.map(id => parseInt(id, 10)));
    }
    
    // 按数量范围筛选
    if (options.min_quantity !== undefined) {
      whereClauses.push("sr.quantity >= ?");
      params.push(parseInt(options.min_quantity, 10));
    }
    
    if (options.max_quantity !== undefined) {
      whereClauses.push("sr.quantity <= ?");
      params.push(parseInt(options.max_quantity, 10));
    }
    
    // 按备注关键词搜索
    if (options.notes_search) {
      whereClauses.push("sr.notes LIKE ?");
      params.push(`%${options.notes_search}%`);
    }
    
    // 组合WHERE子句
    if (whereClauses.length > 0) {
      sql += " WHERE " + whereClauses.join(" AND ");
    }
    
    const result = await db.query(sql, params);
    return result[0] ? parseInt(result[0].total, 10) : 0;
  }

  /**
   * 根据ID获取发货记录
   * @param {number} id 发货记录ID
   * @returns {Promise<Object|null>} 发货记录对象或null
   */
  async getById(id) {
    const sql = `SELECT sr.*, c.name as courier_name 
                FROM ${this.table} sr
                LEFT JOIN couriers c ON sr.courier_id = c.id
                WHERE sr.id = ?`;
    const results = await db.query(sql, [id]);
    return results.length > 0 ? results[0] : null;
  }

  /**
   * 添加发货记录
   * @param {Object} data 发货记录数据
   * @returns {Promise<number>} 新创建的记录ID
   */
  async add(data) {
    const sql = `INSERT INTO ${this.table} (date, courier_id, quantity, notes) VALUES (?, ?, ?, ?)`;
    
    const notes = data.notes || null;
    
    const result = await db.query(sql, [
      data.date,
      data.courier_id,
      data.quantity,
      notes
    ]);
    
    return result.insertId;
  }

  /**
   * 批量添加发货记录
   * @param {string} date 日期
   * @param {Array<Object>} records 记录数据数组
   * @returns {Promise<Object>} 添加结果
   */
  async batchAdd(date, records) {
    if (!records || !records.length) {
      return { success: true, created: 0, records: [] };
    }
    
    try {
      return await db.transaction(async (connection) => {
        const createdRecords = [];
        let created = 0;
        
        for (const record of records) {
          // 组合完整记录数据
          const recordData = {
            date,
            courier_id: record.courier_id,
            quantity: record.quantity,
            notes: record.notes || null
          };
          
          // 添加记录
          const [result] = await connection.execute(
            `INSERT INTO ${this.table} (date, courier_id, quantity, notes) VALUES (?, ?, ?, ?)`,
            [recordData.date, recordData.courier_id, recordData.quantity, recordData.notes]
          );
          
          if (result.insertId) {
            created++;
            
            // 获取完整记录信息
            const [rows] = await connection.execute(
              `SELECT sr.*, c.name as courier_name 
               FROM ${this.table} sr
               LEFT JOIN couriers c ON sr.courier_id = c.id
               WHERE sr.id = ?`,
              [result.insertId]
            );
            
            if (rows.length > 0) {
              createdRecords.push(rows[0]);
            }
          }
        }
        
        return {
          success: true,
          created,
          records: createdRecords
        };
      });
    } catch (error) {
      return {
        success: false,
        message: '批量添加失败: ' + error.message
      };
    }
  }

  /**
   * 更新发货记录
   * @param {number} id 发货记录ID
   * @param {Object} data 要更新的数据
   * @returns {Promise<boolean>} 更新是否成功
   */
  async update(id, data) {
    const setClauses = [];
    const params = [];
    
    // 构建SET子句
    if (data.date !== undefined) {
      setClauses.push("date = ?");
      params.push(data.date);
    }
    
    if (data.courier_id !== undefined) {
      setClauses.push("courier_id = ?");
      params.push(data.courier_id);
    }
    
    if (data.quantity !== undefined) {
      setClauses.push("quantity = ?");
      params.push(data.quantity);
    }
    
    if (data.notes !== undefined) {
      setClauses.push("notes = ?");
      params.push(data.notes);
    }
    
    // 如果没有需要更新的字段，直接返回成功
    if (setClauses.length === 0) {
      return true;
    }
    
    // 将ID添加到参数数组末尾
    params.push(id);
    
    const sql = `UPDATE ${this.table} SET ${setClauses.join(", ")} WHERE id = ?`;
    
    const result = await db.query(sql, params);
    return result.affectedRows > 0;
  }

  /**
   * 删除发货记录
   * @param {number} id 发货记录ID
   * @returns {Promise<boolean>} 删除是否成功
   */
  async delete(id) {
    const sql = `DELETE FROM ${this.table} WHERE id = ?`;
    const result = await db.query(sql, [id]);
    return result.affectedRows > 0;
  }
}

module.exports = new ShippingRecord(); 
<?php

require_once __DIR__ . '/../helpers/DB.php';

/**
 * 发货记录模型类
 */
class ShippingRecord
{
    // 表名
    private $table = 'shipping_records';
    
    // 数据库连接实例
    private $db;
    
    // 属性
    private $id;
    private $date;
    private $courier_id;
    private $quantity;
    private $notes;
    private $created_at;
    private $updated_at;
    
    public function __construct()
    {
        $this->db = DB::getInstance();
    }
    
    /**
     * 获取所有发货记录
     * 
     * @param array $options 查询选项
     * @return array
     */
    public function getAll($options = [])
    {
        $sql = "SELECT sr.*, c.name as courier_name 
                FROM {$this->table} sr
                LEFT JOIN couriers c ON sr.courier_id = c.id";
        
        $params = [];
        $whereConditions = [];
        
        // 按日期筛选
        if (isset($options['date'])) {
            $whereConditions[] = "sr.date = ?";
            $params[] = $options['date'];
        }
        
        // 按日期范围筛选
        if (isset($options['date_from'])) {
            $whereConditions[] = "sr.date >= ?";
            $params[] = $options['date_from'];
        }
        
        if (isset($options['date_to'])) {
            $whereConditions[] = "sr.date <= ?";
            $params[] = $options['date_to'];
        }
        
        // 按快递类型筛选
        if (isset($options['courier_id'])) {
            $whereConditions[] = "sr.courier_id = ?";
            $params[] = $options['courier_id'];
        }
        
        // 按多个快递类型筛选
        if (isset($options['courier_ids']) && is_array($options['courier_ids']) && !empty($options['courier_ids'])) {
            $placeholders = implode(',', array_fill(0, count($options['courier_ids']), '?'));
            $whereConditions[] = "sr.courier_id IN ({$placeholders})";
            $params = array_merge($params, $options['courier_ids']);
        }
        
        // 按数量范围筛选
        if (isset($options['min_quantity'])) {
            $whereConditions[] = "sr.quantity >= ?";
            $params[] = $options['min_quantity'];
        }
        
        if (isset($options['max_quantity'])) {
            $whereConditions[] = "sr.quantity <= ?";
            $params[] = $options['max_quantity'];
        }
        
        // 按备注关键词搜索
        if (isset($options['notes_search']) && !empty($options['notes_search'])) {
            $whereConditions[] = "sr.notes LIKE ?";
            $params[] = '%' . $options['notes_search'] . '%';
        }
        
        // 组合WHERE子句
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // 排序
        $sortBy = isset($options['sort_by']) ? $options['sort_by'] : 'date';
        $sortOrder = isset($options['sort_order']) ? $options['sort_order'] : 'DESC';
        
        // 验证排序字段
        $allowedSortFields = ['id', 'date', 'courier_id', 'quantity', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date'; // 默认排序字段
        }
        
        // 验证排序方向
        $sortOrder = strtoupper($sortOrder);
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
            $sortOrder = 'DESC'; // 默认排序方向
        }
        
        $sql .= " ORDER BY sr.{$sortBy} {$sortOrder}";
        
        // 分页
        if (isset($options['page']) && isset($options['per_page'])) {
            $page = max(1, intval($options['page']));
            $perPage = max(1, intval($options['per_page']));
            $offset = ($page - 1) * $perPage;
            
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $perPage;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 获取记录总数
     * 
     * @param array $options 查询选项
     * @return int
     */
    public function count($options = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} sr";
        
        $params = [];
        $whereConditions = [];
        
        // 按日期筛选
        if (isset($options['date'])) {
            $whereConditions[] = "sr.date = ?";
            $params[] = $options['date'];
        }
        
        // 按日期范围筛选
        if (isset($options['date_from'])) {
            $whereConditions[] = "sr.date >= ?";
            $params[] = $options['date_from'];
        }
        
        if (isset($options['date_to'])) {
            $whereConditions[] = "sr.date <= ?";
            $params[] = $options['date_to'];
        }
        
        // 按快递类型筛选
        if (isset($options['courier_id'])) {
            $whereConditions[] = "sr.courier_id = ?";
            $params[] = $options['courier_id'];
        }
        
        // 按多个快递类型筛选
        if (isset($options['courier_ids']) && is_array($options['courier_ids']) && !empty($options['courier_ids'])) {
            $placeholders = implode(',', array_fill(0, count($options['courier_ids']), '?'));
            $whereConditions[] = "sr.courier_id IN ({$placeholders})";
            $params = array_merge($params, $options['courier_ids']);
        }
        
        // 按数量范围筛选
        if (isset($options['min_quantity'])) {
            $whereConditions[] = "sr.quantity >= ?";
            $params[] = $options['min_quantity'];
        }
        
        if (isset($options['max_quantity'])) {
            $whereConditions[] = "sr.quantity <= ?";
            $params[] = $options['max_quantity'];
        }
        
        // 按备注关键词搜索
        if (isset($options['notes_search']) && !empty($options['notes_search'])) {
            $whereConditions[] = "sr.notes LIKE ?";
            $params[] = '%' . $options['notes_search'] . '%';
        }
        
        // 组合WHERE子句
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result ? intval($result['total']) : 0;
    }
    
    /**
     * 根据ID获取发货记录
     * 
     * @param int $id 发货记录ID
     * @return array|false
     */
    public function getById($id)
    {
        $sql = "SELECT sr.*, c.name as courier_name 
                FROM {$this->table} sr
                LEFT JOIN couriers c ON sr.courier_id = c.id
                WHERE sr.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * 添加发货记录
     * 
     * @param array $data 发货记录数据
     * @return int|bool 成功返回新ID，失败返回false
     */
    public function add($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} (date, courier_id, quantity, notes) VALUES (?, ?, ?, ?)";
            
            $notes = isset($data['notes']) ? $data['notes'] : null;
            
            $this->db->query($sql, [
                $data['date'],
                $data['courier_id'],
                $data['quantity'],
                $notes
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 批量添加发货记录
     * 
     * @param string $date 日期
     * @param array $records 记录数据数组
     * @return array 添加结果
     */
    public function batchAdd($date, $records)
    {
        if (empty($records)) {
            return ['success' => true, 'created' => 0, 'records' => []];
        }
        
        try {
            $this->db->beginTransaction();
            
            $createdRecords = [];
            $created = 0;
            
            foreach ($records as $record) {
                // 组合完整记录数据
                $recordData = [
                    'date' => $date,
                    'courier_id' => $record['courier_id'],
                    'quantity' => $record['quantity'],
                    'notes' => isset($record['notes']) ? $record['notes'] : null
                ];
                
                // 添加记录
                $id = $this->add($recordData);
                
                if ($id) {
                    $created++;
                    
                    // 获取完整记录信息
                    $newRecord = $this->getById($id);
                    if ($newRecord) {
                        $createdRecords[] = $newRecord;
                    }
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'created' => $created,
                'records' => $createdRecords
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => '批量添加失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 更新发货记录
     * 
     * @param int $id 发货记录ID
     * @param array $data 要更新的数据
     * @return bool
     */
    public function update($id, $data)
    {
        $setClauses = [];
        $params = [];
        
        // 构建SET子句
        if (isset($data['date'])) {
            $setClauses[] = "date = ?";
            $params[] = $data['date'];
        }
        
        if (isset($data['courier_id'])) {
            $setClauses[] = "courier_id = ?";
            $params[] = $data['courier_id'];
        }
        
        if (isset($data['quantity'])) {
            $setClauses[] = "quantity = ?";
            $params[] = $data['quantity'];
        }
        
        if (array_key_exists('notes', $data)) {
            $setClauses[] = "notes = ?";
            $params[] = $data['notes'];
        }
        
        // 如果没有需要更新的字段，直接返回成功
        if (empty($setClauses)) {
            return true;
        }
        
        // 将ID添加到参数数组末尾
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(", ", $setClauses) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 删除发货记录
     * 
     * @param int $id 发货记录ID
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
} 
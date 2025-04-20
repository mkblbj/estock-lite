<?php

require_once __DIR__ . '/../helpers/DB.php';

/**
 * 快递公司模型类
 */
class Courier
{
    // 表名
    private $table = 'couriers';
    
    // 数据库连接实例
    private $db;
    
    // 属性
    private $id;
    private $name;
    private $code;
    private $is_active;
    private $sort_order;
    private $created_at;
    private $updated_at;
    
    public function __construct()
    {
        $this->db = DB::getInstance();
    }
    
    /**
     * 获取所有快递公司
     * 
     * @param bool $activeOnly 是否只获取启用的快递公司
     * @return array
     */
    public function getAll($activeOnly = false)
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = TRUE";
        }
        
        $sql .= " ORDER BY sort_order ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 根据ID获取快递公司
     * 
     * @param int $id 快递公司ID
     * @return array|false
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * 添加快递公司
     * 
     * @param array $data 快递公司数据
     * @return int|bool 成功返回新ID，失败返回false
     */
    public function add($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} (name, code, is_active, sort_order) VALUES (?, ?, ?, ?)";
            
            $isActive = isset($data['is_active']) ? $data['is_active'] : true;
            $sortOrder = isset($data['sort_order']) ? $data['sort_order'] : 0;
            
            $this->db->query($sql, [
                $data['name'],
                $data['code'],
                $isActive ? 1 : 0,
                $sortOrder
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 更新快递公司
     * 
     * @param int $id 快递公司ID
     * @param array $data 要更新的数据
     * @return bool
     */
    public function update($id, $data)
    {
        $setClauses = [];
        $params = [];
        
        // 构建SET子句
        if (isset($data['name'])) {
            $setClauses[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['code'])) {
            $setClauses[] = "code = ?";
            $params[] = $data['code'];
        }
        
        if (isset($data['is_active'])) {
            $setClauses[] = "is_active = ?";
            $params[] = $data['is_active'] ? 1 : 0;
        }
        
        if (isset($data['sort_order'])) {
            $setClauses[] = "sort_order = ?";
            $params[] = $data['sort_order'];
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
     * 删除快递公司
     * 
     * @param int $id 快递公司ID
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
    
    /**
     * 切换快递公司启用状态
     * 
     * @param int $id 快递公司ID
     * @return bool
     */
    public function toggleActive($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 更新排序
     * 
     * @param array $sortData 排序数据 [['id' => 1, 'sort_order' => 3], ...]
     * @return bool
     */
    public function updateSort($sortData)
    {
        if (empty($sortData)) {
            return true;
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($sortData as $item) {
                if (!isset($item['id']) || !isset($item['sort_order'])) {
                    continue;
                }
                
                $sql = "UPDATE {$this->table} SET sort_order = ? WHERE id = ?";
                $this->db->query($sql, [$item['sort_order'], $item['id']]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
} 
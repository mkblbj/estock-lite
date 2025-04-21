<?php

require_once __DIR__ . '/../models/Courier.php';

class CourierService
{
    private $db;
    
    public function __construct($db = null)
    {
        $this->db = $db;
        if ($db) {
            Courier::setDb($db);
        }
    }
    
    /**
     * 获取所有快递种类
     *
     * @param array $options 过滤和排序选项
     * @return array 快递种类列表
     */
    public function getAllCouriers($options = [])
    {
        $couriers = Courier::getAll($options);
        $result = [];
        
        foreach ($couriers as $courier) {
            $result[] = $courier->toArray();
        }
        
        return $result;
    }
    
    /**
     * 根据ID获取快递种类
     *
     * @param int $id 快递种类ID
     * @return array|null 快递种类数据
     */
    public function getCourierById($id)
    {
        $courier = Courier::getById($id);
        return $courier ? $courier->toArray() : null;
    }
    
    /**
     * 创建快递种类
     *
     * @param array $data 快递种类数据
     * @return array 结果信息
     */
    public function createCourier($data)
    {
        $courier = new Courier($data);
        
        // 验证数据
        $errors = $courier->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // 保存数据
        $result = $courier->save();
        
        if ($result) {
            return [
                'success' => true,
                'data' => $courier->toArray(),
                'message' => '快递种类创建成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '快递种类创建失败'
            ];
        }
    }
    
    /**
     * 更新快递种类
     *
     * @param int $id 快递种类ID
     * @param array $data 快递种类数据
     * @return array 结果信息
     */
    public function updateCourier($id, $data)
    {
        $courier = Courier::getById($id);
        
        if (!$courier) {
            return [
                'success' => false,
                'message' => '快递种类不存在'
            ];
        }
        
        // 更新属性
        if (isset($data['name'])) $courier->setName($data['name']);
        if (isset($data['code'])) $courier->setCode($data['code']);
        if (isset($data['remark'])) $courier->setRemark($data['remark']);
        if (isset($data['is_active'])) $courier->setIsActive($data['is_active']);
        if (isset($data['sort_order'])) $courier->setSortOrder($data['sort_order']);
        
        // 验证数据
        $errors = $courier->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // 保存数据
        $result = $courier->save();
        
        if ($result) {
            return [
                'success' => true,
                'data' => $courier->toArray(),
                'message' => '快递种类更新成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '快递种类更新失败'
            ];
        }
    }
    
    /**
     * 删除快递种类
     *
     * @param int $id 快递种类ID
     * @return array 结果信息
     */
    public function deleteCourier($id)
    {
        $courier = Courier::getById($id);
        
        if (!$courier) {
            return [
                'success' => false,
                'message' => '快递种类不存在'
            ];
        }
        
        // TODO: 检查是否有关联的发货记录，如有则不允许删除
        
        $result = $courier->delete();
        
        if ($result) {
            return [
                'success' => true,
                'message' => '快递种类删除成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '快递种类删除失败'
            ];
        }
    }
    
    /**
     * 重新排序快递种类
     *
     * @param array $order 排序数据
     * @return array 结果信息
     */
    public function reorderCouriers($order)
    {
        $result = Courier::reorder($order);
        
        if ($result) {
            return [
                'success' => true,
                'message' => '排序更新成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '排序更新失败'
            ];
        }
    }
} 
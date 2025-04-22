<?php

require_once __DIR__ . '/../models/ShippingRecord.php';
require_once __DIR__ . '/../models/Courier.php';

class ShippingService
{
    private $shippingRecord;
    private $courier;
    
    public function __construct()
    {
        $this->shippingRecord = new ShippingRecord();
        $this->courier = new Courier();
    }
    
    /**
     * 获取所有发货记录
     *
     * @param array $options 过滤和分页选项
     * @return array
     */
    public function getShippingRecords($options = [])
    {
        // 获取记录总数
        $totalRecords = $this->shippingRecord->count($options);
        
        // 获取分页记录
        $records = $this->shippingRecord->getAll($options);
        
        // 组装分页信息
        $page = isset($options['page']) ? max(1, intval($options['page'])) : 1;
        $perPage = isset($options['per_page']) ? max(1, intval($options['per_page'])) : 10;
        $lastPage = ceil($totalRecords / $perPage);
        
        return [
            'records' => $records,
            'pagination' => [
                'total' => $totalRecords,
                'perPage' => $perPage,
                'currentPage' => $page,
                'lastPage' => $lastPage
            ]
        ];
    }
    
    /**
     * 获取单个发货记录
     *
     * @param int $id
     * @return array|null
     */
    public function getShippingRecord($id)
    {
        return $this->shippingRecord->getById($id);
    }
    
    /**
     * 创建发货记录
     *
     * @param array $data
     * @return array
     */
    public function createShippingRecord($data)
    {
        // 验证数据
        $errors = $this->validateShippingRecord($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // 添加记录
        $id = $this->shippingRecord->add($data);
        
        if ($id) {
            // 获取完整记录信息
            $record = $this->shippingRecord->getById($id);
            
            return [
                'success' => true,
                'data' => $record,
                'message' => '发货记录添加成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '发货记录添加失败'
            ];
        }
    }
    
    /**
     * 更新发货记录
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateShippingRecord($id, $data)
    {
        // 检查记录是否存在
        $record = $this->shippingRecord->getById($id);
        if (!$record) {
            return [
                'success' => false,
                'message' => '发货记录不存在'
            ];
        }
        
        // 验证数据
        $errors = $this->validateShippingRecord($data, false);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // 更新记录
        $result = $this->shippingRecord->update($id, $data);
        
        if ($result) {
            // 获取更新后的记录
            $updatedRecord = $this->shippingRecord->getById($id);
            
            return [
                'success' => true,
                'data' => $updatedRecord,
                'message' => '发货记录更新成功'
            ];
        } else {
            return [
                'success' => false,
                'message' => '发货记录更新失败'
            ];
        }
    }
    
    /**
     * 删除发货记录
     *
     * @param int $id
     * @return array
     */
    public function deleteShippingRecord($id)
    {
        // 检查记录是否存在
        $record = $this->shippingRecord->getById($id);
        if (!$record) {
            return [
                'success' => false,
                'message' => '发货记录不存在'
            ];
        }
        
        // 删除记录
        $result = $this->shippingRecord->delete($id);
        
        if ($result) {
            return [
                'success' => true,
                'message' => '发货记录已删除'
            ];
        } else {
            return [
                'success' => false,
                'message' => '发货记录删除失败'
            ];
        }
    }
    
    /**
     * 批量添加发货记录
     *
     * @param array $data 包含date和records字段的数组
     * @return array
     */
    public function batchCreateShippingRecords($data)
    {
        // 验证日期
        if (!isset($data['date']) || empty($data['date'])) {
            return [
                'success' => false,
                'errors' => ['date' => '日期不能为空']
            ];
        }
        
        // 验证日期格式
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            return [
                'success' => false,
                'errors' => ['date' => '日期格式不正确，应为YYYY-MM-DD']
            ];
        } else {
            // 检查日期是否有效
            $dateTime = DateTime::createFromFormat('Y-m-d', $data['date']);
            if (!$dateTime || $dateTime->format('Y-m-d') !== $data['date']) {
                return [
                    'success' => false,
                    'errors' => ['date' => '日期无效']
                ];
            }
            
            // 检查日期是否在合理范围内
            $now = new DateTime();
            $maxDate = (clone $now)->modify('+1 day'); // 允许设置明天的记录
            $minDate = (clone $now)->modify('-1 month');
            
            if ($dateTime > $maxDate) {
                return [
                    'success' => false,
                    'errors' => ['date' => '日期不能超过当前日期后一天']
                ];
            } elseif ($dateTime < $minDate) {
                return [
                    'success' => false,
                    'errors' => ['date' => '日期不能早于一个月前']
                ];
            }
        }
        
        // 验证记录数组
        if (!isset($data['records']) || !is_array($data['records']) || empty($data['records'])) {
            return [
                'success' => false,
                'errors' => ['records' => '记录数据不能为空']
            ];
        }
        
        // 验证记录数量不超过限制
        if (count($data['records']) > 100) {
            return [
                'success' => false,
                'errors' => ['records' => '批量添加记录数量不能超过100条']
            ];
        }
        
        // 验证每条记录
        foreach ($data['records'] as $index => $record) {
            // 验证快递公司ID
            if (!isset($record['courier_id']) || empty($record['courier_id'])) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.courier_id" => '快递公司ID不能为空']
                ];
            }
            
            // 检查快递公司ID是否为整数
            if (!is_numeric($record['courier_id']) || intval($record['courier_id']) != $record['courier_id']) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.courier_id" => '快递公司ID必须是整数']
                ];
            }
            
            // 验证数量
            if (!isset($record['quantity']) || $record['quantity'] === '') {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.quantity" => '数量不能为空']
                ];
            }
            
            if (!is_numeric($record['quantity'])) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.quantity" => '数量必须是数字']
                ];
            }
            
            if (floatval($record['quantity']) != intval($record['quantity'])) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.quantity" => '数量必须是整数']
                ];
            }
            
            if (intval($record['quantity']) < 0) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.quantity" => '数量不能是负数']
                ];
            }
            
            if (intval($record['quantity']) > 10000) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.quantity" => '数量不能超过10000']
                ];
            }
            
            // 验证备注长度
            if (isset($record['notes']) && strlen($record['notes']) > 500) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.notes" => '备注长度不能超过500个字符']
                ];
            }
            
            // 检查快递公司是否存在且处于活跃状态
            $courier = $this->courier->getById($record['courier_id']);
            if (!$courier) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.courier_id" => "ID为{$record['courier_id']}的快递公司不存在"]
                ];
            } elseif (!$courier['is_active']) {
                return [
                    'success' => false,
                    'errors' => ["records.{$index}.courier_id" => "ID为{$record['courier_id']}的快递公司已停用"]
                ];
            }
        }
        
        // 批量添加记录
        $result = $this->shippingRecord->batchAdd($data['date'], $data['records']);
        
        if ($result['success']) {
            return [
                'success' => true,
                'data' => [
                    'created' => $result['created'],
                    'records' => $result['records']
                ],
                'message' => "成功添加{$result['created']}条发货记录"
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message']
            ];
        }
    }
    
    /**
     * 验证发货记录数据
     *
     * @param array $data 要验证的数据
     * @param bool $isCreate 是否是创建操作
     * @return array 错误信息
     */
    private function validateShippingRecord($data, $isCreate = true)
    {
        $errors = [];
        
        // 日期验证
        if ($isCreate && (!isset($data['date']) || empty($data['date']))) {
            $errors['date'] = '日期不能为空';
        } elseif (isset($data['date'])) {
            // 检查日期格式
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
                $errors['date'] = '日期格式不正确，应为YYYY-MM-DD';
            } else {
                // 检查日期是否有效
                $dateTime = DateTime::createFromFormat('Y-m-d', $data['date']);
                if (!$dateTime || $dateTime->format('Y-m-d') !== $data['date']) {
                    $errors['date'] = '日期无效';
                }
                
                // 检查日期是否在合理范围内（不超过当前日期一年，不早于当前日期一个月）
                $now = new DateTime();
                $maxDate = (clone $now)->modify('+1 day'); // 允许设置明天的记录
                $minDate = (clone $now)->modify('-1 month');
                
                if ($dateTime > $maxDate) {
                    $errors['date'] = '日期不能超过当前日期后一天';
                } elseif ($dateTime < $minDate) {
                    $errors['date'] = '日期不能早于一个月前';
                }
            }
        }
        
        // 快递公司ID验证
        if ($isCreate && (!isset($data['courier_id']) || empty($data['courier_id']))) {
            $errors['courier_id'] = '快递公司ID不能为空';
        } elseif (isset($data['courier_id'])) {
            // 检查快递公司ID是否为整数
            if (!is_numeric($data['courier_id']) || intval($data['courier_id']) != $data['courier_id']) {
                $errors['courier_id'] = '快递公司ID必须是整数';
            } else {
                // 检查快递公司是否存在且处于活跃状态
                $courier = $this->courier->getById($data['courier_id']);
                if (!$courier) {
                    $errors['courier_id'] = "ID为{$data['courier_id']}的快递公司不存在";
                } elseif (!$courier['is_active']) {
                    $errors['courier_id'] = "ID为{$data['courier_id']}的快递公司已停用";
                }
            }
        }
        
        // 数量验证
        if ($isCreate && (!isset($data['quantity']) || $data['quantity'] === '')) {
            $errors['quantity'] = '数量不能为空';
        } elseif (isset($data['quantity'])) {
            if (!is_numeric($data['quantity'])) {
                $errors['quantity'] = '数量必须是数字';
            } elseif (floatval($data['quantity']) != intval($data['quantity'])) {
                $errors['quantity'] = '数量必须是整数';
            } elseif (intval($data['quantity']) < 0) {
                $errors['quantity'] = '数量不能是负数';
            } elseif (intval($data['quantity']) > 10000) {
                $errors['quantity'] = '数量不能超过10000';
            }
        }
        
        // 备注验证
        if (isset($data['notes']) && strlen($data['notes']) > 500) {
            $errors['notes'] = '备注长度不能超过500个字符';
        }
        
        return $errors;
    }
} 
<?php

require_once __DIR__ . '/../models/Courier.php';

/**
 * 快递公司API控制器
 */
class CourierApiController
{
    private $courier;
    
    public function __construct()
    {
        $this->courier = new Courier();
        header('Content-Type: application/json');
    }
    
    /**
     * 返回JSON响应
     * 
     * @param mixed $data 响应数据
     * @param int $statusCode HTTP状态码
     * @return void
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 获取所有快递公司
     * 
     * @return void
     */
    public function getAll()
    {
        // 获取URL参数
        $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
        
        $couriers = $this->courier->getAll($activeOnly);
        $this->jsonResponse([
            'code' => 0,
            'message' => '获取成功',
            'data' => $couriers
        ]);
    }
    
    /**
     * 获取单个快递公司
     * 
     * @param int $id 快递公司ID
     * @return void
     */
    public function getById($id)
    {
        $courier = $this->courier->getById($id);
        
        if (!$courier) {
            $this->jsonResponse([
                'code' => 404,
                'message' => '快递公司不存在'
            ], 404);
        }
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '获取成功',
            'data' => $courier
        ]);
    }
    
    /**
     * 添加快递公司
     * 
     * @return void
     */
    public function add()
    {
        // 获取POST数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证数据
        if (!isset($data['name']) || empty($data['name'])) {
            $this->jsonResponse([
                'code' => 400,
                'message' => '快递公司名称不能为空'
            ], 400);
        }
        
        if (!isset($data['code']) || empty($data['code'])) {
            $this->jsonResponse([
                'code' => 400,
                'message' => '快递公司代码不能为空'
            ], 400);
        }
        
        // 添加快递公司
        $id = $this->courier->add($data);
        
        if (!$id) {
            $this->jsonResponse([
                'code' => 500,
                'message' => '添加失败'
            ], 500);
        }
        
        // 获取新添加的快递公司
        $newCourier = $this->courier->getById($id);
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '添加成功',
            'data' => $newCourier
        ], 201);
    }
    
    /**
     * 更新快递公司
     * 
     * @param int $id 快递公司ID
     * @return void
     */
    public function update($id)
    {
        // 检查快递公司是否存在
        $courier = $this->courier->getById($id);
        
        if (!$courier) {
            $this->jsonResponse([
                'code' => 404,
                'message' => '快递公司不存在'
            ], 404);
        }
        
        // 获取PUT数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 更新快递公司
        $result = $this->courier->update($id, $data);
        
        if (!$result) {
            $this->jsonResponse([
                'code' => 500,
                'message' => '更新失败'
            ], 500);
        }
        
        // 获取更新后的快递公司
        $updatedCourier = $this->courier->getById($id);
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '更新成功',
            'data' => $updatedCourier
        ]);
    }
    
    /**
     * 删除快递公司
     * 
     * @param int $id 快递公司ID
     * @return void
     */
    public function delete($id)
    {
        // 检查快递公司是否存在
        $courier = $this->courier->getById($id);
        
        if (!$courier) {
            $this->jsonResponse([
                'code' => 404,
                'message' => '快递公司不存在'
            ], 404);
        }
        
        // 删除快递公司
        $result = $this->courier->delete($id);
        
        if (!$result) {
            $this->jsonResponse([
                'code' => 500,
                'message' => '删除失败'
            ], 500);
        }
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '删除成功'
        ]);
    }
    
    /**
     * 切换快递公司启用状态
     * 
     * @param int $id 快递公司ID
     * @return void
     */
    public function toggleActive($id)
    {
        // 检查快递公司是否存在
        $courier = $this->courier->getById($id);
        
        if (!$courier) {
            $this->jsonResponse([
                'code' => 404,
                'message' => '快递公司不存在'
            ], 404);
        }
        
        // 切换状态
        $result = $this->courier->toggleActive($id);
        
        if (!$result) {
            $this->jsonResponse([
                'code' => 500,
                'message' => '操作失败'
            ], 500);
        }
        
        // 获取更新后的快递公司
        $updatedCourier = $this->courier->getById($id);
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '操作成功',
            'data' => $updatedCourier
        ]);
    }
    
    /**
     * 更新快递公司排序
     * 
     * @return void
     */
    public function updateSort()
    {
        // 获取POST数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            $this->jsonResponse([
                'code' => 400,
                'message' => '参数错误'
            ], 400);
        }
        
        // 更新排序
        $result = $this->courier->updateSort($data['items']);
        
        if (!$result) {
            $this->jsonResponse([
                'code' => 500,
                'message' => '更新排序失败'
            ], 500);
        }
        
        $this->jsonResponse([
            'code' => 0,
            'message' => '更新排序成功'
        ]);
    }
} 
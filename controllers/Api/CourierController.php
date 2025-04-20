<?php

require_once __DIR__ . '/../../services/CourierService.php';

class ApiCourierController
{
    private $courierService;
    
    public function __construct()
    {
        $this->courierService = new CourierService();
    }
    
    /**
     * 获取所有快递种类
     *
     * @return void
     */
    public function index()
    {
        // 获取请求参数
        $is_active = isset($_GET['status']) ? ($_GET['status'] === 'active') : null;
        $sort_by = $_GET['sort'] ?? 'sort_order';
        $sort_order = $_GET['order'] ?? 'ASC';
        $search = $_GET['search'] ?? '';
        
        $options = [
            'is_active' => $is_active,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'search' => $search
        ];
        
        $result = $this->courierService->getAllCouriers($options);
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
    
    /**
     * 获取单个快递种类
     *
     * @param int $id 快递种类ID
     * @return void
     */
    public function show($id)
    {
        $courier = $this->courierService->getCourierById($id);
        
        if ($courier) {
            $this->jsonResponse(['success' => true, 'data' => $courier]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => '快递种类不存在'], 404);
        }
    }
    
    /**
     * 创建快递种类
     *
     * @return void
     */
    public function store()
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        // 创建快递种类
        $result = $this->courierService->createCourier($data);
        
        if ($result['success']) {
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    /**
     * 更新快递种类
     *
     * @param int $id 快递种类ID
     * @return void
     */
    public function update($id)
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        // 更新快递种类
        $result = $this->courierService->updateCourier($id, $data);
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, $result['message'] === '快递种类不存在' ? 404 : 400);
        }
    }
    
    /**
     * 删除快递种类
     *
     * @param int $id 快递种类ID
     * @return void
     */
    public function destroy($id)
    {
        // 删除快递种类
        $result = $this->courierService->deleteCourier($id);
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, $result['message'] === '快递种类不存在' ? 404 : 400);
        }
    }
    
    /**
     * 重新排序快递种类
     *
     * @return void
     */
    public function reorder()
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        if (!isset($data['order']) || !is_array($data['order'])) {
            $this->jsonResponse(['success' => false, 'message' => '排序数据格式不正确'], 400);
            return;
        }
        
        // 更新排序
        $result = $this->courierService->reorderCouriers($data['order']);
        
        $this->jsonResponse($result);
    }
    
    /**
     * 获取请求数据
     *
     * @return array
     */
    private function getRequestData()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    /**
     * 返回JSON响应
     *
     * @param array $data 响应数据
     * @param int $statusCode HTTP状态码
     * @return void
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 
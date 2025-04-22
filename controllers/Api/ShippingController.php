<?php

require_once __DIR__ . '/../../services/ShippingService.php';

class ApiShippingController
{
    private $shippingService;
    
    public function __construct()
    {
        $this->shippingService = new ShippingService();
    }
    
    /**
     * 获取发货记录列表
     *
     * @return void
     */
    public function index()
    {
        // 获取请求参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
        $sortBy = $_GET['sortBy'] ?? 'date';
        $sortOrder = $_GET['sortOrder'] ?? 'DESC';
        $date = $_GET['date'] ?? null;
        $courierId = $_GET['courier_id'] ?? null;
        
        // 获取新增的筛选条件
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $minQuantity = isset($_GET['min_quantity']) ? intval($_GET['min_quantity']) : null;
        $maxQuantity = isset($_GET['max_quantity']) ? intval($_GET['max_quantity']) : null;
        $notesSearch = $_GET['notes_search'] ?? null;
        
        // 获取多个快递公司ID筛选
        $courierIds = null;
        if (isset($_GET['courier_ids']) && !empty($_GET['courier_ids'])) {
            $courierIds = explode(',', $_GET['courier_ids']);
            $courierIds = array_map('intval', $courierIds);
        }
        
        $options = [
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'date' => $date,
            'courier_id' => $courierId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
            'notes_search' => $notesSearch
        ];
        
        // 添加多个快递公司ID筛选
        if (!empty($courierIds)) {
            $options['courier_ids'] = $courierIds;
        }
        
        $result = $this->shippingService->getShippingRecords($options);
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
    
    /**
     * 获取单个发货记录
     *
     * @param int $id 发货记录ID
     * @return void
     */
    public function show($id)
    {
        $record = $this->shippingService->getShippingRecord($id);
        
        if ($record) {
            $this->jsonResponse(['success' => true, 'data' => $record]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => '发货记录不存在'], 404);
        }
    }
    
    /**
     * 创建发货记录
     *
     * @return void
     */
    public function store()
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        // 创建发货记录
        $result = $this->shippingService->createShippingRecord($data);
        
        if ($result['success']) {
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    /**
     * 更新发货记录
     *
     * @param int $id 发货记录ID
     * @return void
     */
    public function update($id)
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        // 更新发货记录
        $result = $this->shippingService->updateShippingRecord($id, $data);
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, $result['message'] === '发货记录不存在' ? 404 : 400);
        }
    }
    
    /**
     * 删除发货记录
     *
     * @param int $id 发货记录ID
     * @return void
     */
    public function destroy($id)
    {
        // 删除发货记录
        $result = $this->shippingService->deleteShippingRecord($id);
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, $result['message'] === '发货记录不存在' ? 404 : 400);
        }
    }
    
    /**
     * 批量添加发货记录
     *
     * @return void
     */
    public function batch()
    {
        // 获取请求数据
        $data = $this->getRequestData();
        
        // 批量添加发货记录
        $result = $this->shippingService->batchCreateShippingRecords($data);
        
        if ($result['success']) {
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 400);
        }
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
<?php
/**
 * 快递公司API处理器
 * 此文件直接处理 /api/couriers 路径的请求
 */

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// 引入必要的类
require_once __DIR__ . '/../controllers/CourierApiController.php';

// 解析请求路径
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 移除查询字符串
$uriParts = explode('?', $requestUri);
$uri = $uriParts[0];

// 解析路由参数
$pattern = '/\/api\/couriers(?:\/([^\/]+))?(?:\/([^\/]+))?/';
preg_match($pattern, $uri, $matches);

$id = $matches[1] ?? null;
$action = $matches[2] ?? null;

// 创建控制器实例
$controller = new CourierApiController();

try {
    // 根据请求方法和路径参数调用相应的方法
    switch ($requestMethod) {
        case 'GET':
            if ($id === null) {
                // GET /api/couriers - 获取所有快递公司
                $controller->getAll();
            } else {
                // GET /api/couriers/{id} - 获取单个快递公司
                $controller->getById($id);
            }
            break;
            
        case 'POST':
            if ($id === 'sort' || $id === 'reorder') {
                // POST /api/couriers/sort 或 /api/couriers/reorder - 更新排序
                $controller->updateSort();
            } else {
                // POST /api/couriers - 添加新快递公司
                $controller->add();
            }
            break;
            
        case 'PUT':
            if ($action === 'toggle') {
                // PUT /api/couriers/{id}/toggle - 切换启用状态
                $controller->toggleActive($id);
            } else {
                // PUT /api/couriers/{id} - 更新快递公司
                $controller->update($id);
            }
            break;
            
        case 'DELETE':
            // DELETE /api/couriers/{id} - 删除快递公司
            $controller->delete($id);
            break;
            
        default:
            // 不支持的请求方法
            http_response_code(405);
            echo json_encode([
                'code' => 405,
                'message' => '不支持的请求方法'
            ]);
            break;
    }
} catch (Exception $e) {
    // 处理异常
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器内部错误',
        'error' => $e->getMessage()
    ]);
} 
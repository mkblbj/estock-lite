<?php
/**
 * 发货记录API处理器
 * 此文件直接处理 /api/shipping 路径的请求
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
require_once __DIR__ . '/../controllers/Api/ShippingController.php';

// 解析请求路径
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 移除查询字符串
$uriParts = explode('?', $requestUri);
$uri = $uriParts[0];

// 解析路由参数
$pattern = '/\/api\/shipping(?:\/([^\/]+))?(?:\/([^\/]+))?/';
preg_match($pattern, $uri, $matches);

$id = $matches[1] ?? null;
$action = $matches[2] ?? null;

// 创建控制器实例
$controller = new ApiShippingController();

try {
    // 根据请求方法和路径参数调用相应的方法
    switch ($requestMethod) {
        case 'GET':
            if ($id === null) {
                // GET /api/shipping - 获取发货记录列表
                $controller->index();
            } else {
                // GET /api/shipping/{id} - 获取单个发货记录
                $controller->show($id);
            }
            break;
            
        case 'POST':
            if ($id === 'batch') {
                // POST /api/shipping/batch - 批量添加发货记录
                $controller->batch();
            } else {
                // POST /api/shipping - 添加新发货记录
                $controller->store();
            }
            break;
            
        case 'PUT':
            // PUT /api/shipping/{id} - 更新发货记录
            $controller->update($id);
            break;
            
        case 'DELETE':
            // DELETE /api/shipping/{id} - 删除发货记录
            $controller->destroy($id);
            break;
            
        default:
            // 不支持的请求方法
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => '不支持的请求方法'
            ]);
            break;
    }
} catch (Exception $e) {
    // 处理异常
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'error' => $e->getMessage()
    ]);
}
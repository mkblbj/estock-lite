<?php
/**
 * API路由文件
 * 处理所有API请求
 */

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// 获取请求URI和方法
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 移除查询字符串
$uriParts = explode('?', $requestUri);
$uri = $uriParts[0];

// 解析路由 (假设API路由格式为 /api/resource/id)
$pattern = '/\/api\/([^\/]+)(?:\/([^\/]+))?(?:\/([^\/]+))?/';
preg_match($pattern, $uri, $matches);

// 如果没有匹配到有效的API路由
if (empty($matches)) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['code' => 404, 'message' => 'API endpoint not found']);
    exit;
}

// 解析资源和ID
$resource = $matches[1] ?? null;
$id = $matches[2] ?? null;
$action = $matches[3] ?? null;

// 根据资源类型加载相应的控制器
$controllerFile = '';
$controllerClass = '';
$method = '';

// 定义路由映射
$routes = [
    'couriers' => [
        'file' => 'controllers/CourierApiController.php',
        'class' => 'CourierApiController',
        'methods' => [
            'GET' => [
                '' => 'getAll',
                '{id}' => 'getById'
            ],
            'POST' => [
                '' => 'add',
                'sort' => 'updateSort'
            ],
            'PUT' => [
                '{id}' => 'update',
                '{id}/toggle' => 'toggleActive'
            ],
            'DELETE' => [
                '{id}' => 'delete'
            ]
        ]
    ]
    // 可以添加更多资源路由
];

// 检查资源是否存在
if (!isset($routes[$resource])) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['code' => 404, 'message' => '资源不存在']);
    exit;
}

// 获取控制器信息
$controllerFile = $routes[$resource]['file'];
$controllerClass = $routes[$resource]['class'];

// 验证请求方法
if (!isset($routes[$resource]['methods'][$requestMethod])) {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['code' => 405, 'message' => '请求方法不允许']);
    exit;
}

// 确定要调用的方法
$methodMap = $routes[$resource]['methods'][$requestMethod];

if ($action && isset($methodMap["{id}/$action"])) {
    // 处理 /api/resource/id/action 形式的路由
    $method = $methodMap["{id}/$action"];
} elseif ($id && isset($methodMap["{id}"])) {
    // 处理 /api/resource/id 形式的路由
    $method = $methodMap["{id}"];
} elseif ($id && isset($methodMap[$id])) {
    // 处理 /api/resource/action 形式的路由
    $method = $methodMap[$id];
    $action = $id;
    $id = null;
} elseif (isset($methodMap[''])) {
    // 处理 /api/resource 形式的路由
    $method = $methodMap[''];
} else {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['code' => 404, 'message' => 'API endpoint not found']);
    exit;
}

// 加载控制器
require_once $controllerFile;

// 实例化控制器
$controller = new $controllerClass();

// 调用方法
if ($id && $action) {
    $controller->$method($id, $action);
} elseif ($id) {
    $controller->$method($id);
} elseif ($action) {
    $controller->$method($action);
} else {
    $controller->$method();
} 
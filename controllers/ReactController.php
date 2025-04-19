<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReactController extends BaseController
{
    public function ReactView(Request $request, Response $response, array $args)
    {
        // 可以在这里添加任何需要传递给React应用的数据
        return $this->renderPage($response, 'react.app');
    }

    // 用于测试组件页面
    public function ComponentsDemo(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'react.components-demo');
    }
} 
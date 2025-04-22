<?php

require_once __DIR__ . '/../services/ShippingService.php';
require_once __DIR__ . '/../services/CourierService.php';

class ShippingController
{
    private $shippingService;
    private $courierService;
    
    public function __construct()
    {
        $this->shippingService = new ShippingService();
        $this->courierService = new CourierService();
    }
    
    /**
     * 发货记录主页面
     * 
     * @return void
     */
    public function index()
    {
        // 获取所有快递公司
        $options = ['is_active' => true];
        $couriers = $this->courierService->getAllCouriers($options);
        
        // 渲染视图
        include(__DIR__ . '/../views/shipping/index.blade.php');
    }
    
    /**
     * API文档页面
     * 
     * @return void
     */
    public function apiDocs()
    {
        // 渲染API文档视图
        include(__DIR__ . '/../views/shipping/api-docs.blade.php');
    }
} 
const express = require('express');
const courierRoutes = require('./courierRoutes');
const shippingRoutes = require('./shippingRoutes');

const router = express.Router();

// API 根路径
router.get('/', (req, res) => {
  res.json({
    message: '快递API服务',
    version: '1.0.0',
    documentation: '/api/docs'
  });
});

// 快递公司API路由
router.use('/couriers', courierRoutes);

// 发货记录API路由
router.use('/shipping', shippingRoutes);

// API文档路由 (可选)
router.get('/docs', (req, res) => {
  res.json({
    message: 'API文档',
    apis: [
      {
        name: '快递公司API',
        description: '快递公司的CRUD操作',
        basePath: '/api/couriers',
        endpoints: [
          { method: 'GET', path: '/', description: '获取所有快递公司' },
          { method: 'GET', path: '/:id', description: '获取单个快递公司详情' },
          { method: 'POST', path: '/', description: '创建快递公司' },
          { method: 'PUT', path: '/:id', description: '更新快递公司' },
          { method: 'DELETE', path: '/:id', description: '删除快递公司' },
          { method: 'PUT', path: '/:id/toggle', description: '切换快递公司状态' },
          { method: 'POST', path: '/sort', description: '更新快递公司排序' }
        ]
      },
      {
        name: '发货记录API',
        description: '发货记录的CRUD操作和批量处理',
        basePath: '/api/shipping',
        endpoints: [
          { method: 'GET', path: '/', description: '获取发货记录列表' },
          { method: 'GET', path: '/:id', description: '获取单个发货记录详情' },
          { method: 'POST', path: '/', description: '创建发货记录' },
          { method: 'PUT', path: '/:id', description: '更新发货记录' },
          { method: 'DELETE', path: '/:id', description: '删除发货记录' },
          { method: 'POST', path: '/batch', description: '批量添加发货记录' }
        ]
      }
    ]
  });
});

module.exports = router; 
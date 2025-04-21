# Epic-1 - Story-1

快递种类管理功能

**作为** 系统管理员
**我想要** 方便快捷地管理快递种类
**以便** 随时更新可用的快递选项，满足业务需求变化

## 状态

完成

## 背景

在实现发货统计功能的过程中，首先需要解决的是快递种类的管理问题。快递种类是发货统计的基础数据，需要提供一个简单直观的界面让管理员能够添加、编辑和删除快递种类。快递市场变化快，新的快递公司不断涌现，因此系统需要灵活支持快递种类的动态管理。根据新的架构，我们将采用前后端分离的模式实现，后端使用PHP提供API服务，前端使用React构建用户界面。

## 估计

故事点数: 3

## 任务

1. - [x] 设计快递种类数据库表结构
   1. - [x] 创建couriers表的迁移文件
   2. - [x] 执行迁移创建表
   3. - [x] 添加初始快递种类数据

2. - [x] 实现后端模型和服务
   1. - [x] 创建Courier.php模型
   2. - [x] 定义关系和属性
   3. - [x] 添加必要的验证和业务规则
   4. - [x] 创建CourierService.php服务类处理业务逻辑

3. - [x] 实现API控制器
   1. - [x] 创建Api/CourierController.php
   2. - [x] 实现GET /api/couriers接口获取列表
   3. - [x] 实现POST /api/couriers接口添加记录
   4. - [x] 实现PUT /api/couriers/{id}接口更新记录
   5. - [x] 实现DELETE /api/couriers/{id}接口删除记录
   6. - [x] 实现POST /api/couriers/reorder接口排序

4. - [x] 实现页面控制器
   1. - [x] 创建CourierController.php
   2. - [x] 实现index方法提供基础视图
   3. - [x] 添加API文档页面

5. - [x] 实现React前端组件
   1. - [x] 创建CourierList.tsx组件显示快递种类列表
   2. - [x] 创建CourierForm.tsx组件处理添加和编辑
   3. - [x] 创建CourierActions.tsx组件处理删除和激活/禁用
   4. - [x] 创建拖拽排序组件实现排序功能
   5. - [x] 添加表单验证逻辑

6. - [x] 实现状态管理
   1. - [x] 创建CourierStore.ts管理快递种类状态
   2. - [x] 实现CRUD操作的action
   3. - [x] 实现排序功能的action
   4. - [x] 集成错误处理和加载状态

7. - [x] 实现API服务
   1. - [x] 创建courierService.ts实现API调用
   2. - [x] 添加错误处理和重试逻辑
   3. - [x] 实现数据格式转换

8. - [x] 优化和测试
   1. - [x] 添加单元测试
   2. - [x] 进行端到端测试
   3. - [x] 优化性能和用户体验
   4. - [x] 处理边缘情况

## 约束

- 不能删除已经有发货记录的快递种类，只能设置为非活跃
- 快递种类名称不能重复
- 排序必须是简单直观的拖拽操作
- API响应时间需要在1秒内完成
- 前端操作需要有适当的加载提示
- 表单验证必须同时在前端和后端实现

## 数据模型/架构

```sql
CREATE TABLE couriers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  code VARCHAR(50),
  remark TEXT DEFAULT NULL COMMENT '备注信息',
  is_active BOOLEAN DEFAULT TRUE,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## API规格

### GET /api/couriers
获取所有快递种类列表
**响应**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "ゆうパケット (1CM)",
      "code": "up1",
      "remark": "国内知名快递公司，速度快，价格较高",
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "ゆうパケット (2CM)",
      "code": "up2",
      "remark": "全国性快递公司，性价比高",
      "is_active": true,
      "sort_order": 2
    }
  ]
}
```

### POST /api/couriers
创建新的快递种类
**请求**:
```json
{
  "name": "ゆうパックパフ",
  "code": "ypp",
  "remark": "电商自营物流，配送稳定",
  "is_active": true
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "ゆうパックパフ",
    "code": "ypp",
    "remark": "电商自营物流，配送稳定",
    "is_active": true,
    "sort_order": 3
  }
}
```

### PUT /api/couriers/{id}
更新快递种类
**请求**:
```json
{
  "name": "ゆうパックパフ新",
  "code": "ypp",
  "remark": "更新的备注信息",
  "is_active": true
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "ゆうパックパフ新",
    "code": "ypp",
    "remark": "更新的备注信息",
    "is_active": true,
    "sort_order": 3
  }
}
```

### DELETE /api/couriers/{id}
删除快递种类
**响应**:
```json
{
  "success": true,
  "message": "快递种类已删除"
}
```

### POST /api/couriers/reorder
重新排序快递种类
**请求**:
```json
{
  "order": [3, 1, 2]
}
```
**响应**:
```json
{
  "success": true,
  "message": "排序已更新"
}
```

## 结构

快递种类管理功能将按以下结构实现：

### 后端文件
- 模型: models/Courier.php
- 服务: services/CourierService.php
- 控制器: 
  - controllers/Api/CourierController.php
  - controllers/CourierController.php
- 迁移: migrations/create_couriers_table.php

### 前端文件
- 组件: 
  - react/src/components/courier/CourierList.tsx
  - react/src/components/courier/CourierForm.tsx
  - react/src/components/courier/CourierActions.tsx
- 状态管理: react/src/store/CourierStore.ts
- 服务: react/src/services/courierService.ts
- 工具: react/src/utils/formatUtils.ts

## 图表

```mermaid
sequenceDiagram
    参与者 管理员
    参与者 React前端
    参与者 API控制器
    参与者 CourierService
    参与者 Courier模型
    参与者 数据库
    
    管理员->>React前端: 访问快递种类管理页面
    React前端->>React前端: 初始化CourierStore
    React前端->>API控制器: GET /api/couriers
    API控制器->>CourierService: 获取所有快递种类
    CourierService->>Courier模型: 查询数据
    Courier模型->>数据库: 执行查询
    数据库-->>Courier模型: 返回快递种类数据
    Courier模型-->>CourierService: 返回数据集合
    CourierService-->>API控制器: 返回处理后的数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新CourierStore状态
    React前端->>React前端: 渲染快递种类列表
    React前端-->>管理员: 显示快递种类列表
    
    管理员->>React前端: 添加/编辑快递种类
    React前端->>React前端: 显示表单并验证输入
    React前端->>API控制器: POST/PUT请求
    API控制器->>CourierService: 保存数据
    CourierService->>Courier模型: 验证并保存
    Courier模型->>数据库: 执行插入/更新
    数据库-->>Courier模型: 确认操作结果
    Courier模型-->>CourierService: 返回操作结果
    CourierService-->>API控制器: 返回处理后的结果
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新CourierStore状态
    React前端-->>管理员: 显示成功或错误消息
    
    管理员->>React前端: 拖拽排序快递种类
    React前端->>React前端: 更新本地排序
    React前端->>API控制器: POST /api/couriers/reorder
    API控制器->>CourierService: 更新排序
    CourierService->>Courier模型: 批量更新排序
    Courier模型->>数据库: 执行更新
    数据库-->>Courier模型: 确认操作结果
    Courier模型-->>CourierService: 返回操作结果
    CourierService-->>API控制器: 返回处理后的结果
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 确认排序状态
    React前端-->>管理员: 显示成功或错误消息
```

## 开发备注

- 使用React DnD库实现拖拽排序功能
- 确保表单字段具有适当的验证，前后端验证规则保持一致
- 添加软删除功能，而不是物理删除
- 所有API响应应包含明确的成功/失败标志和消息
- 实现乐观更新以提升用户体验
- 确保移动设备上的良好体验
- 备注字段支持多行文本输入，前端需要使用textarea

## 聊天命令日志

*此部分将在开发过程中记录* 
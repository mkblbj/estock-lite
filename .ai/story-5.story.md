# Epic-2 - Story-5

快递类型管理功能

**作为** 系统管理员
**我想要** 能够管理系统支持的快递类型
**以便** 统计数据能够准确反映业务需要

## 状态

Draft

## 背景

在物流管理中，快递类型会随着业务发展不断变化，可能新增快递合作伙伴或停用某些已有快递服务。为确保系统能够准确记录和统计不同快递类型的发货数量，需要提供一个灵活的快递类型管理功能。管理员可以通过此功能添加、编辑、禁用或启用不同的快递类型，并设置相关属性（如默认排序、颜色标识等）。基于前后端分离架构，前端将通过React组件提供直观的用户界面，后端通过RESTful API提供数据服务。

## 估计

故事点数: 2

## 任务

1. - [ ] 实现后端模型和服务
   1. - [ ] 创建CourierType模型类
   2. - [ ] 实现CourierTypeService服务类
   3. - [ ] 添加数据验证逻辑
   4. - [ ] 实现软删除功能

2. - [ ] 实现API控制器
   1. - [ ] 创建Api/CourierTypeController.php控制器
   2. - [ ] 实现CRUD操作接口
   3. - [ ] 添加权限控制
   4. - [ ] 实现排序和过滤功能

3. - [ ] 实现React前端组件
   1. - [ ] 创建CourierTypeList.tsx组件
   2. - [ ] 创建CourierTypeForm.tsx组件
   3. - [ ] 实现排序拖拽功能
   4. - [ ] 添加颜色选择器
   5. - [ ] 实现状态开关控件

4. - [ ] 实现状态管理
   1. - [ ] 创建CourierTypeStore.ts状态管理
   2. - [ ] 实现CRUD操作状态
   3. - [ ] 添加表单验证状态

5. - [ ] 实现API服务
   1. - [ ] 创建courierTypeService.ts服务
   2. - [ ] 实现API请求方法

6. - [ ] 集成到系统
   1. - [ ] 添加导航菜单入口
   2. - [ ] 设置路由配置
   3. - [ ] 添加权限配置

7. - [ ] 优化和测试
   1. - [ ] 添加单元测试
   2. - [ ] 添加E2E测试
   3. - [ ] 优化用户体验
   4. - [ ] 确保移动设备兼容性

## 约束

- 快递类型名称不能重复
- 快递类型必须支持启用/禁用状态切换
- 系统必须保留快递类型的历史记录（使用软删除）
- 快递类型列表需要支持拖拽排序
- 操作界面必须直观易用
- 所有操作需要有适当的权限控制
- 必须支持响应式设计，适应不同设备

## 数据模型

```sql
CREATE TABLE `courier_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '快递类型名称',
  `code` varchar(20) NOT NULL COMMENT '快递代码',
  `color` varchar(20) DEFAULT NULL COMMENT '显示颜色',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序值',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courier_types_name_unique` (`name`) USING BTREE,
  UNIQUE KEY `courier_types_code_unique` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='快递类型表';
```

## API规格

### GET /api/courier-types
获取快递类型列表
**请求参数**:
- page: 页码
- per_page: 每页记录数
- sort: 排序字段
- order: 排序方式（asc/desc）
- search: 搜索关键词
- status: 状态过滤（active/inactive/all）

**响应**:
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "name": "顺丰速运",
        "code": "SF",
        "color": "#FF6600",
        "sort_order": 1,
        "is_active": true,
        "created_at": "2023-01-01T12:00:00",
        "updated_at": "2023-01-01T12:00:00"
      },
      {
        "id": 2,
        "name": "京东物流",
        "code": "JD",
        "color": "#CB2422",
        "sort_order": 2,
        "is_active": true,
        "created_at": "2023-01-01T12:00:00",
        "updated_at": "2023-01-01T12:00:00"
      }
    ],
    "total": 10,
    "page": 1,
    "per_page": 10,
    "last_page": 1
  }
}
```

### GET /api/courier-types/{id}
获取单个快递类型详情
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "顺丰速运",
    "code": "SF",
    "color": "#FF6600",
    "sort_order": 1,
    "is_active": true,
    "created_at": "2023-01-01T12:00:00",
    "updated_at": "2023-01-01T12:00:00"
  }
}
```

### POST /api/courier-types
创建快递类型
**请求**:
```json
{
  "name": "圆通快递",
  "code": "YTO",
  "color": "#00A0E9",
  "sort_order": 3,
  "is_active": true
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "圆通快递",
    "code": "YTO",
    "color": "#00A0E9",
    "sort_order": 3,
    "is_active": true,
    "created_at": "2023-01-02T12:00:00",
    "updated_at": "2023-01-02T12:00:00"
  },
  "message": "快递类型创建成功"
}
```

### PUT /api/courier-types/{id}
更新快递类型
**请求**:
```json
{
  "name": "圆通速递",
  "code": "YTO",
  "color": "#00A0E9",
  "sort_order": 3,
  "is_active": true
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "圆通速递",
    "code": "YTO",
    "color": "#00A0E9",
    "sort_order": 3,
    "is_active": true,
    "created_at": "2023-01-02T12:00:00",
    "updated_at": "2023-01-02T13:00:00"
  },
  "message": "快递类型更新成功"
}
```

### DELETE /api/courier-types/{id}
删除快递类型（软删除）
**响应**:
```json
{
  "success": true,
  "message": "快递类型删除成功"
}
```

### PUT /api/courier-types/sort
批量更新快递类型排序
**请求**:
```json
{
  "items": [
    {"id": 2, "sort_order": 1},
    {"id": 1, "sort_order": 2},
    {"id": 3, "sort_order": 3}
  ]
}
```
**响应**:
```json
{
  "success": true,
  "message": "排序更新成功"
}
```

### PUT /api/courier-types/{id}/toggle-status
切换快递类型状态
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "is_active": false
  },
  "message": "状态已更新"
}
```

## 结构

快递类型管理功能将按以下结构实现：

### 后端文件
- 模型: models/CourierType.php
- 服务: services/CourierTypeService.php
- 控制器: controllers/Api/CourierTypeController.php
- 请求验证: requests/CourierTypeRequest.php
- 资源: resources/CourierTypeResource.php

### 前端文件
- 页面: 
  - react/src/pages/admin/CourierTypesPage.tsx
- 组件: 
  - react/src/components/courier/CourierTypeList.tsx
  - react/src/components/courier/CourierTypeForm.tsx
  - react/src/components/courier/CourierTypeItem.tsx
  - react/src/components/courier/ColorPicker.tsx
- 状态管理: react/src/store/CourierTypeStore.ts
- 服务: react/src/services/courierTypeService.ts
- 路由: react/src/router/routes.tsx (扩展)

## 图表

```mermaid
sequenceDiagram
    参与者 用户
    参与者 React前端
    参与者 API控制器
    参与者 CourierTypeService
    参与者 CourierType模型
    参与者 数据库
    
    用户->>React前端: 访问快递类型管理页面
    React前端->>API控制器: GET /api/courier-types
    API控制器->>CourierTypeService: 获取快递类型列表
    CourierTypeService->>CourierType模型: 查询数据
    CourierType模型->>数据库: 执行查询
    数据库-->>CourierType模型: 返回结果
    CourierType模型-->>CourierTypeService: 返回模型集合
    CourierTypeService-->>API控制器: 返回处理后的数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新状态
    React前端-->>用户: 显示快递类型列表
    
    alt 添加新快递类型
        用户->>React前端: 点击"添加快递类型"按钮
        React前端->>React前端: 显示添加表单
        用户->>React前端: 填写表单并提交
        React前端->>API控制器: POST /api/courier-types
        API控制器->>CourierTypeService: 创建快递类型
        CourierTypeService->>CourierType模型: 创建数据
        CourierType模型->>数据库: 保存数据
        数据库-->>CourierType模型: 返回结果
        CourierType模型-->>CourierTypeService: 返回新模型
        CourierTypeService-->>API控制器: 返回处理后的数据
        API控制器-->>React前端: 返回JSON响应
        React前端->>React前端: 更新状态
        React前端-->>用户: 显示成功消息并更新列表
    end
    
    alt 编辑快递类型
        用户->>React前端: 点击编辑按钮
        React前端->>React前端: 显示编辑表单
        用户->>React前端: 修改表单并提交
        React前端->>API控制器: PUT /api/courier-types/{id}
        API控制器->>CourierTypeService: 更新快递类型
        CourierTypeService->>CourierType模型: 更新数据
        CourierType模型->>数据库: 保存数据
        数据库-->>CourierType模型: 返回结果
        CourierType模型-->>CourierTypeService: 返回更新后的模型
        CourierTypeService-->>API控制器: 返回处理后的数据
        API控制器-->>React前端: 返回JSON响应
        React前端->>React前端: 更新状态
        React前端-->>用户: 显示成功消息并更新列表
    end
    
    alt 调整排序
        用户->>React前端: 拖拽调整顺序
        React前端->>React前端: 更新本地排序
        用户->>React前端: 完成拖拽操作
        React前端->>API控制器: PUT /api/courier-types/sort
        API控制器->>CourierTypeService: 批量更新排序
        CourierTypeService->>CourierType模型: 更新多条数据
        CourierType模型->>数据库: 保存数据
        数据库-->>CourierType模型: 返回结果
        CourierType模型-->>CourierTypeService: 返回成功状态
        CourierTypeService-->>API控制器: 返回成功状态
        API控制器-->>React前端: 返回JSON响应
        React前端->>React前端: 确认排序更新
        React前端-->>用户: 显示成功消息
    end
```

## 开发备注

- 使用React DnD库实现拖拽排序功能
- 使用React Color或类似组件实现颜色选择器
- 快递类型的启用/禁用需要考虑对已有数据的影响
- 禁用的快递类型在录入页面应该特别标识或隐藏
- 软删除确保历史数据不会丢失，但在UI上不再显示
- 考虑添加导入/导出功能，方便批量处理快递类型
- 快递代码需要唯一，用于集成其他系统
- 快递类型颜色用于在统计图表中区分不同快递类型
- 使用防抖/节流技术优化拖拽排序性能
- 所有操作需要添加适当的错误处理和用户反馈
- 考虑添加批量操作功能，如批量启用/禁用

## 聊天命令日志

*此部分将在开发过程中记录* 
# Epic-1 - Story-3

发货数据录入功能

**作为** 运营人员
**我想要** 快速录入每日发货数据
**以便** 准确记录每种快递的发货数量

## 状态

Draft

## 背景

为了实现发货统计功能，需要先有可靠的数据录入机制。运营人员需要每天录入各种快递的发货数量，这是整个统计系统的数据来源。录入界面应该简洁直观，支持快速输入，并提供相应的数据验证以确保数据准确性。同时，考虑到可能存在的错误记录，需要支持数据的编辑和删除功能。在前后端分离架构下，我们将使用React构建更加直观、交互性强的录入界面，后端提供REST API实现数据的CRUD操作。

## 估计

故事点数: 3

## 任务

1. - [ ] 确认数据库结构
   1. - [ ] 确认shipping_records表结构是否满足需求（如已在Story-2中完成，可跳过）
   2. - [ ] 如有必要，添加额外字段

2. - [ ] 实现后端模型和服务
   1. - [ ] 完善ShippingRecord.php模型（如已在Story-2中创建）
   2. - [ ] 创建ShippingService.php服务类处理业务逻辑
   3. - [ ] 添加验证规则和错误处理

3. - [ ] 实现API控制器
   1. - [ ] 创建或扩展Api/ShippingController.php
   2. - [ ] 实现GET /api/shipping接口获取记录列表
   3. - [ ] 实现POST /api/shipping接口添加记录
   4. - [ ] 实现PUT /api/shipping/{id}接口更新记录
   5. - [ ] 实现DELETE /api/shipping/{id}接口删除记录
   6. - [ ] 实现POST /api/shipping/batch接口批量添加记录

4. - [ ] 实现页面控制器
   1. - [ ] 创建或扩展ShippingController.php
   2. - [ ] 实现index方法提供基础视图
   3. - [ ] 添加API文档页面

5. - [ ] 实现React前端组件
   1. - [ ] 创建ShippingList.tsx组件显示发货记录列表
   2. - [ ] 创建ShippingForm.tsx组件处理添加和编辑
   3. - [ ] 创建ShippingActions.tsx组件处理删除等操作
   4. - [ ] 创建BatchForm.tsx组件实现批量录入功能
   5. - [ ] 实现表单验证和错误提示

6. - [ ] 实现状态管理
   1. - [ ] 创建ShippingStore.ts管理发货记录状态
   2. - [ ] 实现CRUD操作的actions
   3. - [ ] 实现批量录入的action
   4. - [ ] 集成错误处理和加载状态

7. - [ ] 实现API服务
   1. - [ ] 创建shippingService.ts实现API调用
   2. - [ ] 添加错误处理和重试逻辑
   3. - [ ] 实现数据格式转换和验证

8. - [ ] 增强用户体验
   1. - [ ] 实现快速录入模式（预设当天日期和常用快递）
   2. - [ ] 添加表单自动保存功能
   3. - [ ] 实现"另存为新记录"功能
   4. - [ ] 添加批量导入历史数据功能

9. - [ ] 优化和测试
   1. - [ ] 添加单元测试
   2. - [ ] 进行端到端测试
   3. - [ ] 优化移动设备上的体验
   4. - [ ] 测试边缘情况和错误处理

## 约束

- 数据录入界面必须简洁易用，最多3步完成录入
- 批量录入功能必须支持同一天多种快递的一次性提交
- 数据验证必须确保不录入负数或非法数值
- 日期选择器默认应为当天
- 快递种类选择器应按照用户定义的排序展示
- API响应时间需要在1秒内完成
- 表单验证必须同时在前端和后端实现
- 需要支持移动设备上的良好体验

## 数据模型/架构

基于之前定义的shipping_records表结构:

```sql
CREATE TABLE shipping_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  courier_id INT NOT NULL,
  quantity INT NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (courier_id) REFERENCES couriers(id)
);
```

## API规格

### GET /api/shipping
获取发货记录列表
**请求参数**:
- page: 页码
- perPage: 每页记录数
- sortBy: 排序字段
- sortOrder: 排序方向
- date: 按日期筛选
- courier_id: 按快递筛选

**响应**:
```json
{
  "success": true,
  "data": {
    "records": [
      {
        "id": 1,
        "date": "2023-06-10",
        "courier_id": 1,
        "courier_name": "顺丰速运",
        "quantity": 35,
        "notes": "主要为电子产品",
        "created_at": "2023-06-10T08:30:00",
        "updated_at": "2023-06-10T08:30:00"
      },
      {
        "id": 2,
        "date": "2023-06-10",
        "courier_id": 2,
        "courier_name": "中通快递",
        "quantity": 42,
        "notes": "",
        "created_at": "2023-06-10T08:35:00",
        "updated_at": "2023-06-10T08:35:00"
      }
    ],
    "pagination": {
      "total": 120,
      "perPage": 10,
      "currentPage": 1,
      "lastPage": 12
    }
  }
}
```

### POST /api/shipping
添加新的发货记录
**请求**:
```json
{
  "date": "2023-06-11",
  "courier_id": 3,
  "quantity": 28,
  "notes": "包含易碎物品"
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "date": "2023-06-11",
    "courier_id": 3,
    "courier_name": "京东物流",
    "quantity": 28,
    "notes": "包含易碎物品",
    "created_at": "2023-06-11T09:15:00",
    "updated_at": "2023-06-11T09:15:00"
  }
}
```

### PUT /api/shipping/{id}
更新发货记录
**请求**:
```json
{
  "date": "2023-06-11",
  "courier_id": 3,
  "quantity": 30,
  "notes": "包含易碎物品和贵重物品"
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "date": "2023-06-11",
    "courier_id": 3,
    "courier_name": "京东物流",
    "quantity": 30,
    "notes": "包含易碎物品和贵重物品",
    "created_at": "2023-06-11T09:15:00",
    "updated_at": "2023-06-11T09:20:00"
  }
}
```

### DELETE /api/shipping/{id}
删除发货记录
**响应**:
```json
{
  "success": true,
  "message": "发货记录已删除"
}
```

### POST /api/shipping/batch
批量添加发货记录
**请求**:
```json
{
  "date": "2023-06-12",
  "records": [
    {
      "courier_id": 1,
      "quantity": 40,
      "notes": ""
    },
    {
      "courier_id": 2,
      "quantity": 35,
      "notes": ""
    },
    {
      "courier_id": 3,
      "quantity": 25,
      "notes": ""
    }
  ]
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "created": 3,
    "records": [
      {
        "id": 4,
        "date": "2023-06-12",
        "courier_id": 1,
        "courier_name": "顺丰速运",
        "quantity": 40,
        "notes": "",
        "created_at": "2023-06-12T08:00:00",
        "updated_at": "2023-06-12T08:00:00"
      },
      {
        "id": 5,
        "date": "2023-06-12",
        "courier_id": 2,
        "courier_name": "中通快递",
        "quantity": 35,
        "notes": "",
        "created_at": "2023-06-12T08:00:00",
        "updated_at": "2023-06-12T08:00:00"
      },
      {
        "id": 6,
        "date": "2023-06-12",
        "courier_id": 3,
        "courier_name": "京东物流",
        "quantity": 25,
        "notes": "",
        "created_at": "2023-06-12T08:00:00",
        "updated_at": "2023-06-12T08:00:00"
      }
    ]
  }
}
```

## 结构

发货数据录入功能将按以下结构实现：

### 后端文件
- 模型: models/ShippingRecord.php
- 服务: services/ShippingService.php
- 控制器: 
  - controllers/Api/ShippingController.php
  - controllers/ShippingController.php

### 前端文件
- 组件: 
  - react/src/components/shipping/ShippingList.tsx
  - react/src/components/shipping/ShippingForm.tsx
  - react/src/components/shipping/ShippingActions.tsx
  - react/src/components/shipping/BatchForm.tsx
- 状态管理: react/src/store/ShippingStore.ts
- 服务: react/src/services/shippingService.ts
- 工具: 
  - react/src/utils/dateUtils.ts
  - react/src/utils/formUtils.ts

## 图表

```mermaid
sequenceDiagram
    参与者 用户
    参与者 React前端
    参与者 API控制器
    参与者 ShippingService
    参与者 ShippingRecord模型
    参与者 数据库
    
    用户->>React前端: 访问发货记录列表
    React前端->>React前端: 初始化ShippingStore
    React前端->>API控制器: GET /api/shipping
    API控制器->>ShippingService: 获取发货记录
    ShippingService->>ShippingRecord模型: 查询记录
    ShippingRecord模型->>数据库: 执行查询
    数据库-->>ShippingRecord模型: 返回记录数据
    ShippingRecord模型-->>ShippingService: 返回记录集合
    ShippingService-->>API控制器: 返回处理后的数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新ShippingStore状态
    React前端->>React前端: 渲染发货记录列表
    React前端-->>用户: 显示发货记录列表
    
    用户->>React前端: 请求添加新记录
    React前端->>React前端: 显示发货表单
    React前端->>API控制器: GET /api/couriers（获取快递种类）
    API控制器-->>React前端: 返回快递种类列表
    React前端-->>用户: 显示添加表单
    
    用户->>React前端: 填写并提交表单
    React前端->>React前端: 验证表单数据
    React前端->>API控制器: POST /api/shipping
    API控制器->>ShippingService: 保存新记录
    ShippingService->>ShippingRecord模型: 创建记录
    ShippingRecord模型->>数据库: 保存数据
    数据库-->>ShippingRecord模型: 确认保存
    ShippingRecord模型-->>ShippingService: 返回新记录
    ShippingService-->>API控制器: 返回处理后的数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新ShippingStore状态
    React前端-->>用户: 显示成功消息并更新列表
    
    用户->>React前端: 请求批量录入
    React前端->>React前端: 显示批量录入表单
    用户->>React前端: 填写并提交批量数据
    React前端->>React前端: 验证批量数据
    React前端->>API控制器: POST /api/shipping/batch
    API控制器->>ShippingService: 批量保存记录
    ShippingService->>ShippingRecord模型: 批量创建记录
    ShippingRecord模型->>数据库: 保存数据
    数据库-->>ShippingRecord模型: 确认保存
    ShippingRecord模型-->>ShippingService: 返回新记录集合
    ShippingService-->>API控制器: 返回处理后的数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新ShippingStore状态
    React前端-->>用户: 显示成功消息并更新列表
```

## 开发备注

- 使用React Hook Form处理表单状态和验证
- 考虑添加"快速录入"功能，预设当天日期和常用快递
- 对于批量录入，使用动态表单行，支持添加和删除行
- 实现"另存为新记录"功能，方便复制类似数据
- 添加导入功能，支持从Excel导入历史数据
- 考虑添加数据校验规则，如同一天同一快递只能有一条记录
- 实现乐观更新以提升用户体验
- 确保表单在移动设备上有良好的布局和响应式设计

## 聊天命令日志

*此部分将在开发过程中记录* 
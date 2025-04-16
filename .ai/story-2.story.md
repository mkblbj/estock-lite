# Epic-1 - Story-2
# 快递类型管理界面

**As a** 系统管理员
**I want** 管理快递类型/公司的信息
**so that** 我可以添加、编辑和删除快递类型，灵活适应业务变化

## Status

Completed

## Context

在完成数据库设计后，需要创建快递类型管理界面，允许管理员对快递类型进行增删改查操作。该页面需要列出所有快递类型，并提供添加、编辑和删除功能，支持激活/禁用不同类型的快递。

## Estimation

Story Points: 2

## Tasks

1. - [x] 创建控制器
   1. - [x] 创建CourierController.php处理页面请求
   2. - [x] 创建CourierApiController.php处理API请求
   3. - [x] 创建CourierService.php处理业务逻辑

2. - [x] 创建快递类型管理页面
   1. - [x] 创建视图文件views/courier/types.blade.php
   2. - [x] 创建表格显示快递类型列表
   3. - [x] 添加搜索和过滤功能

3. - [x] 实现快递类型的添加功能
   1. - [x] 创建添加表单模态框
   2. - [x] 实现表单验证
   3. - [x] 实现API请求和响应处理

4. - [x] 实现快递类型的编辑功能
   1. - [x] 创建编辑表单模态框
   2. - [x] 实现表单验证
   3. - [x] 实现API请求和响应处理

5. - [x] 实现快递类型的删除功能
   1. - [x] 添加删除确认对话框
   2. - [x] 实现使用状态检查（已使用的类型不允许删除）
   3. - [x] 实现API请求和响应处理

6. - [x] 添加前端交互逻辑
   1. - [x] 创建couriertypes.js文件
   2. - [x] 实现数据表格初始化和搜索功能
   3. - [x] 实现表单验证和提交功能
   4. - [x] 添加成功/失败消息提示

7. - [x] 测试
   1. - [x] 测试添加功能
   2. - [x] 测试编辑功能
   3. - [x] 测试删除功能
   4. - [x] 测试搜索和过滤功能

8. - [x] 界面优化和Bug修复
   1. - [x] 修复/courier页面的双滚动条问题
   2. - [x] 修复图表无限拉伸问题
   3. - [x] 移除/courier/types页面的"Show disabled"选项，默认显示所有数据

## Constraints

- 遵循现有系统的UI风格和交互方式
- 使用系统现有的表单验证和消息提示机制
- 确保所有功能都遵循权限控制

## Data Models / Schema

### 快递类型表(courier_types)
```sql
CREATE TABLE courier_types (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    active TINYINT NOT NULL DEFAULT 1,
    row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
)
```

## Structure

- `controllers/CourierController.php`: 处理页面请求
- `controllers/CourierApiController.php`: 处理API请求
- `services/CourierService.php`: 处理业务逻辑
- `views/courier/types.blade.php`: 快递类型管理页面
- `public/viewjs/couriertypes.js`: 前端交互逻辑
- `routes.php`: 添加路由配置

## Diagrams

```mermaid
sequenceDiagram
    participant User as 用户
    participant UI as 页面
    participant API as API控制器
    participant Service as 服务层
    participant DB as 数据库
    
    User->>UI: 访问快递类型页面
    UI->>API: 获取快递类型列表
    API->>Service: 请求数据
    Service->>DB: 查询数据
    DB-->>Service: 返回数据
    Service-->>API: 格式化数据
    API-->>UI: 返回JSON数据
    UI-->>User: 显示快递类型列表
    
    User->>UI: 添加/编辑快递类型
    UI->>API: 发送表单数据
    API->>Service: 验证并处理数据
    Service->>DB: 保存数据
    DB-->>Service: 确认保存
    Service-->>API: 返回结果
    API-->>UI: 返回操作状态
    UI-->>User: 显示成功/失败消息
```

## Dev Notes

- 使用DataTables.js创建可搜索、可排序的表格
- 使用模态框实现添加和编辑功能，避免页面跳转
- 已经实现了权限检查，确保只有具有适当权限的用户才能修改数据
- 为了数据完整性，已实现了使用状态检查，已被使用的快递类型不允许删除
- 界面已优化，修复了多个UI问题，提升了用户体验

## Chat Command Log

- 用户: 请实现一个新功能，增加一张表来统计每天快递发出的件数
- AI: 已完成数据库设计与实现（Story-1）
- AI: 开始实现快递类型管理界面（Story-2）
- AI: 创建了控制器、服务和视图文件
- AI: 实现了快递类型的CRUD功能
- AI: 完成了UI优化和Bug修复
- AI: 完成了Story-2的所有任务和测试 
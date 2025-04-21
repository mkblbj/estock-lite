# 快递类型管理 API 文档

本文档描述了快递类型管理功能的API接口，包括查询、添加、修改、删除和排序等操作。

## 基础URL

所有API的基础URL为：`/api/couriers`

## 通用响应格式

所有API接口返回JSON格式的响应，一般结构如下：

```json
{
  "code": 0,       // 0表示成功，非0表示失败
  "message": "操作结果描述",
  "data": {}       // 返回的数据，可能是对象或数组
}
```

## API 端点列表

### 1. 获取所有快递类型

- **URL**: `/api/couriers`
- **方法**: GET
- **描述**: 获取系统中所有快递类型的列表
- **查询参数**:
  - `active_only=true` (可选) - 只获取启用状态的快递类型
- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "获取成功",
  "data": [
    {
      "id": 1,
      "name": "ゆうパケット (1CM)",
      "code": "up1",
      "remark": "国内知名快递公司，速度快，价格较高",
      "is_active": 1,
      "sort_order": 1,
      "created_at": "2025-04-20 17:10:49",
      "updated_at": "2025-04-20 17:10:49"
    },
    // 更多快递类型...
  ]
}
```

### 2. 获取单个快递类型详情

- **URL**: `/api/couriers/{id}`
- **方法**: GET
- **描述**: 获取指定ID的快递类型详细信息
- **参数**: 
  - `id` - 快递类型ID (路径参数)
- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "获取成功",
  "data": {
    "id": 1,
    "name": "ゆうパケット (1CM)",
    "code": "up1",
    "remark": "国内知名快递公司，速度快，价格较高",
    "is_active": 1,
    "sort_order": 1,
    "created_at": "2025-04-20 17:10:49",
    "updated_at": "2025-04-20 17:10:49"
  }
}
```

### 3. 添加新快递类型

- **URL**: `/api/couriers`
- **方法**: POST
- **描述**: 创建新的快递类型
- **请求体**:

```json
{
  "name": "新快递名称",
  "code": "快递代码",
  "remark": "备注信息(可选)",
  "is_active": true
}
```

- **成功响应** (201 Created):

```json
{
  "code": 0,
  "message": "添加成功",
  "data": {
    "id": 6,
    "name": "新快递名称",
    "code": "快递代码",
    "remark": "备注信息(可选)",
    "is_active": 1,
    "sort_order": 6,
    "created_at": "2025-04-21 10:15:30",
    "updated_at": "2025-04-21 10:15:30"
  }
}
```

### 4. 更新快递类型

- **URL**: `/api/couriers/{id}`
- **方法**: PUT
- **描述**: 更新指定ID的快递类型信息
- **参数**: 
  - `id` - 快递类型ID (路径参数)
- **请求体**:

```json
{
  "name": "更新的名称",
  "code": "更新的代码",
  "remark": "更新的备注",
  "is_active": true
}
```

- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "更新成功",
  "data": {
    "id": 1,
    "name": "更新的名称",
    "code": "更新的代码",
    "remark": "更新的备注",
    "is_active": 1,
    "sort_order": 1,
    "created_at": "2025-04-20 17:10:49",
    "updated_at": "2025-04-21 10:20:15"
  }
}
```

### 5. 删除快递类型

- **URL**: `/api/couriers/{id}`
- **方法**: DELETE
- **描述**: 删除指定ID的快递类型
- **参数**: 
  - `id` - 快递类型ID (路径参数)
- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "删除成功"
}
```

- **失败响应** (400 Bad Request):

```json
{
  "code": 400,
  "message": "无法删除已有关联数据的快递类型"
}
```

### 6. 切换快递类型启用状态

- **URL**: `/api/couriers/{id}/toggle`
- **方法**: PUT
- **描述**: 切换指定ID的快递类型的启用/禁用状态
- **参数**: 
  - `id` - 快递类型ID (路径参数)
- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "状态已更新",
  "data": {
    "id": 1,
    "is_active": 0  // 0表示禁用，1表示启用
  }
}
```

### 7. 更新快递类型排序

- **URL**: `/api/couriers/sort` 或 `/api/couriers/reorder`
- **方法**: POST
- **描述**: 更新多个快递类型的排序顺序
- **请求体**:

```json
{
  "items": [
    {"id": 3, "sort_order": 1},
    {"id": 1, "sort_order": 2},
    {"id": 2, "sort_order": 3}
  ]
}
```

- **成功响应** (200 OK):

```json
{
  "code": 0,
  "message": "排序更新成功"
}
```

## 错误响应

所有API在发生错误时会返回适当的HTTP状态码和错误信息：

- **400 Bad Request** - 请求参数错误
- **404 Not Found** - 资源不存在
- **500 Internal Server Error** - 服务器内部错误

错误响应示例：

```json
{
  "code": 404,
  "message": "快递类型不存在"
}
```

## 注意事项

1. 所有请求和响应均采用JSON格式
2. 请求头需要包含 `Content-Type: application/json`
3. 已有发货记录的快递类型不能被删除，只能被设置为非活跃状态
4. 快递类型名称不能重复
5. 所有API都支持跨域请求 
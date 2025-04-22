# 发货数据录入 API 文档

本文档描述了发货数据录入功能的API接口，包括查询、添加、修改、删除和批量添加发货记录等操作。

## 基础URL

所有API的基础URL为：`/api/shipping`

## 通用响应格式

所有API接口返回JSON格式的响应，一般结构如下：

```json
{
  "success": true,      // true表示成功，false表示失败
  "message": "操作结果描述",
  "data": {}            // 返回的数据，可能是对象或数组
}
```

## API 端点列表

### 1. 获取发货记录列表

- **URL**: `/api/shipping`
- **方法**: GET
- **描述**: 获取系统中所有发货记录的列表，支持分页和筛选
- **查询参数**:
  - `page` (可选) - 页码，默认为1
  - `perPage` (可选) - 每页记录数，默认为10
  - `sortBy` (可选) - 排序字段，默认为"date"，支持的字段有：id, date, courier_id, quantity, created_at, updated_at
  - `sortOrder` (可选) - 排序方向，默认为"DESC"，可选值："ASC", "DESC"
  - `date` (可选) - 按特定日期筛选，格式为YYYY-MM-DD
  - `date_from` (可选) - 按日期范围筛选起始日期，格式为YYYY-MM-DD
  - `date_to` (可选) - 按日期范围筛选截止日期，格式为YYYY-MM-DD
  - `courier_id` (可选) - 按单个快递公司ID筛选
  - `courier_ids` (可选) - 按多个快递公司ID筛选，格式为逗号分隔的ID列表，如"1,2,3"
  - `min_quantity` (可选) - 按最小数量筛选
  - `max_quantity` (可选) - 按最大数量筛选
  - `notes_search` (可选) - 按备注关键词搜索
- **成功响应** (200 OK):

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
      // 更多发货记录...
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

### 2. 获取单个发货记录详情

- **URL**: `/api/shipping/{id}`
- **方法**: GET
- **描述**: 获取指定ID的发货记录详细信息
- **参数**: 
  - `id` - 发货记录ID (路径参数)
- **成功响应** (200 OK):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "date": "2023-06-10",
    "courier_id": 1,
    "courier_name": "顺丰速运",
    "quantity": 35,
    "notes": "主要为电子产品",
    "created_at": "2023-06-10T08:30:00",
    "updated_at": "2023-06-10T08:30:00"
  }
}
```

### 3. 添加新发货记录

- **URL**: `/api/shipping`
- **方法**: POST
- **描述**: 创建新的发货记录
- **请求体**:

```json
{
  "date": "2023-06-11",
  "courier_id": 3,
  "quantity": 28,
  "notes": "包含易碎物品"
}
```

- **成功响应** (201 Created):

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
  },
  "message": "发货记录添加成功"
}
```

### 4. 更新发货记录

- **URL**: `/api/shipping/{id}`
- **方法**: PUT
- **描述**: 更新指定ID的发货记录信息
- **参数**: 
  - `id` - 发货记录ID (路径参数)
- **请求体**:

```json
{
  "date": "2023-06-11",
  "courier_id": 3,
  "quantity": 30,
  "notes": "包含易碎物品和贵重物品"
}
```

- **成功响应** (200 OK):

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
  },
  "message": "发货记录更新成功"
}
```

### 5. 删除发货记录

- **URL**: `/api/shipping/{id}`
- **方法**: DELETE
- **描述**: 删除指定ID的发货记录
- **参数**: 
  - `id` - 发货记录ID (路径参数)
- **成功响应** (200 OK):

```json
{
  "success": true,
  "message": "发货记录已删除"
}
```

### 6. 批量添加发货记录

- **URL**: `/api/shipping/batch`
- **方法**: POST
- **描述**: 批量添加多条发货记录，相同日期下的多种快递记录
- **请求体**:

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

- **成功响应** (201 Created):

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
  },
  "message": "成功添加3条发货记录"
}
```

## 错误响应

API在发生错误时会返回适当的HTTP状态码和错误信息：

- **400 Bad Request** - 请求参数错误
- **404 Not Found** - 资源不存在
- **500 Internal Server Error** - 服务器内部错误

错误响应示例：

```json
{
  "success": false,
  "errors": {
    "date": "日期不能为空",
    "courier_id": "快递公司不存在"
  }
}
```

或者：

```json
{
  "success": false,
  "message": "发货记录不存在"
}
```

## 数据验证规则

发货记录的数据验证规则如下：

1. **日期** (`date`)
   - 必填项
   - 格式必须为 YYYY-MM-DD
   
2. **快递公司ID** (`courier_id`)
   - 必填项
   - 必须是存在的快递公司ID
   
3. **数量** (`quantity`)
   - 必填项
   - 必须是非负整数

## 注意事项

1. 所有请求和响应均采用JSON格式
2. 请求头需要包含 `Content-Type: application/json`
3. 批量添加记录时，所有记录必须有相同的日期
4. 所有API都支持跨域请求 
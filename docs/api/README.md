# API文档

本目录包含系统中所有API接口的文档。

## 可用API

1. [快递类型管理API](courier_api.md) - 快递公司的CRUD操作
2. [发货数据录入API](shipping_api.md) - 发货记录的CRUD操作和批量处理

## 通用说明

- 所有API均返回JSON格式的响应
- 大部分API都支持跨域请求
- API请求需要携带适当的Content-Type头
- API会返回合适的HTTP状态码

## API响应通用格式

所有API接口返回JSON格式的响应，一般结构如下：

```json
{
  "code": 0,       // 0表示成功，非0表示失败
  "message": "操作结果描述",
  "data": {}       // 返回的数据，可能是对象或数组
}
```

## 错误处理

所有API在发生错误时会返回适当的HTTP状态码和错误信息：

- **400 Bad Request** - 请求参数错误
- **401 Unauthorized** - 未授权或认证失败
- **403 Forbidden** - 没有权限访问
- **404 Not Found** - 资源不存在
- **500 Internal Server Error** - 服务器内部错误

## 跨域支持

所有API均支持跨域请求(CORS)，前端可以从任何域名发起请求。

## 认证方式

目前API不需要认证即可访问。未来可能会添加基于Token的认证机制。 
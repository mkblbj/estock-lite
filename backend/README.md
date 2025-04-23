# 快递API后端服务

这是一个基于Node.js和Express实现的快递API后端服务，用于管理快递公司和发货记录。

## 功能特性

- 快递公司管理：添加、查询、修改、删除快递公司
- 发货记录管理：添加、查询、修改、删除发货记录
- 批量添加发货记录
- 支持多种筛选条件和排序选项
- 完整的数据验证和错误处理

## 技术栈

- Node.js
- Express
- MySQL

## 安装与运行

### 前置条件

- Node.js (>= 12.x)
- MySQL (>= 5.7)

### 安装步骤

1. 克隆或下载代码

2. 安装依赖
   ```bash
   cd backend
   npm install
   ```

3. 配置数据库
   - 创建数据库 shipping_db
   - 编辑 .env 文件或 src/config.json 设置数据库连接信息

4. 运行数据库迁移
   ```bash
   node src/db/migrations.js
   ```

5. 启动服务
   ```bash
   npm run dev
   ```

## API文档

服务启动后，可以访问 `http://localhost:3000/api/docs` 获取完整的API文档。

### 主要API

#### 快递公司API

- `GET /api/couriers` - 获取所有快递公司
- `GET /api/couriers/:id` - 获取单个快递公司详情
- `POST /api/couriers` - 创建快递公司
- `PUT /api/couriers/:id` - 更新快递公司
- `DELETE /api/couriers/:id` - 删除快递公司
- `PUT /api/couriers/:id/toggle` - 切换快递公司状态
- `POST /api/couriers/sort` - 更新快递公司排序

#### 发货记录API

- `GET /api/shipping` - 获取发货记录列表
- `GET /api/shipping/:id` - 获取单个发货记录详情
- `POST /api/shipping` - 创建发货记录
- `PUT /api/shipping/:id` - 更新发货记录
- `DELETE /api/shipping/:id` - 删除发货记录
- `POST /api/shipping/batch` - 批量添加发货记录

## 与前端集成

该后端API设计为与React前端无缝集成。确保前端应用正确配置API基础URL并处理响应格式。 
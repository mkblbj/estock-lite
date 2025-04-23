# 快递管理系统

这是一个基于Node.js、Express和React开发的快递管理系统，包含后端API和前端界面。

## 项目结构

- `backend/`: Node.js + Express后端API
- `frontend/`: React前端应用

## 功能特性

- 快递公司管理
  - 添加、编辑、删除快递公司
  - 启用/禁用快递公司
  - 排序快递公司
  
- 发货记录管理
  - 添加、编辑、删除发货记录
  - 批量添加发货记录
  - 多条件筛选和排序
  - 日期范围统计

## 技术栈

### 后端

- Node.js
- Express
- MySQL
- Express Validator (数据验证)

### 前端

- React
- React Router
- Axios
- CSS (自定义样式)

## 安装与运行

### 1. 安装依赖

```bash
# 后端依赖
cd backend
npm install

# 前端依赖
cd ../frontend
npm install
```

### 2. 配置数据库

1. 创建MySQL数据库 `shipping_db`
2. 配置数据库连接信息:
   - 编辑 `backend/.env` 或 `backend/src/config.json` 文件

### 3. 运行迁移脚本

```bash
cd backend
node src/db/migrations.js
```

### 4. 启动应用

```bash
# 启动后端(端口3000)
cd backend
npm run dev

# 启动前端(端口3001)
cd ../frontend
npm start
```

然后在浏览器中访问 http://localhost:3001

## API文档

服务启动后，可通过访问 `http://localhost:3000/api/docs` 查看完整API文档。

## 注意事项

- 确保MySQL服务已启动
- 后端默认运行在3000端口，前端默认运行在3001端口
- 前端开发模式下会自动代理API请求到后端

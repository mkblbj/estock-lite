# UOstock技术栈文档

## 1. 框架概览

UOstock是基于PHP的库存管理系统，采用MVC架构模式，使用Slim Framework作为基础框架，结合现代化的前端技术提供友好的用户界面。

## 2. 后端技术

### 2.1 核心框架与语言
- **PHP 8.2+** - 主要开发语言
- **Slim Framework 4.0** - 轻量级PHP微框架，用于构建RESTful API和Web应用
- **PHP-DI 7.0** - 依赖注入容器，用于服务管理

### 2.2 数据存储
- **SQLite** - 轻量级数据库系统
- **LessQL** - 数据库抽象层，使用定制版本(morris/lessql的dev-php82分支)

### 2.3 视图引擎
- **Blade** - 基于Laravel的模板引擎，适配到Slim框架(berrnd/slim-blade-view)

### 2.4 其他主要后端库
- **gettext/gettext (4.8.10+)** - 国际化和本地化支持
- **eluceo/ical (2.2.0+)** - iCal日历生成
- **erusev/parsedown (1.7+)** - Markdown解析
- **gumlet/php-image-resize (2.0+)** - 图像处理
- **ezyang/htmlpurifier (4.13+)** - HTML内容净化
- **interficieis/php-barcode (2.0.2+)** - 条形码生成
- **guzzlehttp/guzzle (7.0+)** - HTTP客户端
- **mike42/escpos-php (4.0+)** - 热敏打印机支持
- **phpoffice/phpspreadsheet (3.9+)** - Excel电子表格处理

## 3. 前端技术

### 3.1 UI框架
- **Bootstrap 4** - 响应式前端框架
- **Font Awesome 6** - 图标库

### 3.2 JavaScript库
- **jQuery 3.6** - DOM操作和事件处理
- **Chart.js 2.8** - 数据可视化图表
  - **chartjs-plugin-colorschemes** - 图表配色方案
  - **chartjs-plugin-doughnutlabel** - 环形图标签
  - **chartjs-plugin-piechart-outlabels** - 饼图外部标签
  - **chartjs-plugin-trendline** - 趋势线
- **DataTables 1.10** - 增强的表格功能
  - **datatables.net-bs4** - Bootstrap 4样式
  - **datatables.net-colreorder** - 列重排功能
  - **datatables.net-rowgroup** - 行分组功能
  - **datatables.net-select** - 行选择功能
- **Moment.js 2.27** - 日期时间处理
- **FullCalendar 3.10** - 日历组件
- **bootbox 6.0** - 对话框组件
- **toastr 2.1** - 通知提示组件
- **daterangepicker 3.1** - 日期范围选择器
- **tempusdominus-bootstrap-4 5.39** - 日期时间选择器
- **Quagga2 1.2** - 条形码扫描
- **bwip-js 4.5** - 条形码生成
- **Summernote 0.9** - 富文本编辑器

### 3.3 其他前端工具
- **jquery-serializejson 2.9** - 表单序列化为JSON
- **bootstrap-select 1.13** - 增强的下拉选择框
- **animate.css 3.7** - CSS动画库
- **nosleep.js 0.12** - 防止设备屏幕休眠
- **sprintf-js 1.1** - 字符串格式化
- **gettext-translator 3.0** - 客户端本地化支持
- **swagger-ui-dist 5.2** - API文档UI

## 4. 项目架构

### 4.1 目录结构
- **controllers/** - 控制器类，处理HTTP请求
- **services/** - 服务类，封装业务逻辑
- **views/** - Blade模板文件
- **middleware/** - 中间件，处理请求过滤
- **helpers/** - 辅助功能类
- **public/** - 公共资源文件
- **data/** - 数据存储
- **packages/** - Composer依赖
- **localization/** - 本地化文件
- **migrations/** - 数据库迁移
- **docs/** - 项目文档

### 4.2 架构模式
- **MVC架构** - 模型、视图、控制器分离
- **服务层模式** - 业务逻辑封装在服务类中
- **依赖注入** - 使用PHP-DI容器进行依赖管理
- **RESTful API** - 遵循REST设计原则的API
- **中间件链** - 请求通过一系列中间件处理

### 4.3 API架构
- **OpenAPI/Swagger** - API文档和规范
- **API Key认证** - 使用API密钥进行身份验证
- **JSON数据交换** - 使用JSON格式进行数据交换

## 5. 主要功能模块

### 5.1 库存管理
- 产品库存跟踪
- 库存位置管理
- 库存进出记录
- 库存价值计算
- 库存报告生成

### 5.2 购物清单
- 自动生成购物清单
- 购物清单管理
- 购物位置管理

### 5.3 项目进度跟踪
- Git提交记录集成
- 任务管理
- 需求文档管理
- 项目进度可视化

### 5.4 其他功能
- 用户管理和权限控制
- 设备和电池跟踪
- 日历和任务管理
- 数据可视化仪表板
- 条形码生成和扫描

## 6. 开发和部署

### 6.1 开发环境
- **开发模式** - 通过GROCY_MODE环境变量设置
- **错误报告** - 开发模式下显示所有错误

### 6.2 部署选项
- **标准部署** - 直接在PHP环境中运行
- **虚拟机/容器化部署** - 可在虚拟机或Docker容器中运行

### 6.3 安全特性
- **身份验证** - 用户登录和API密钥认证
- **授权** - 基于角色的权限控制
- **CORS支持** - 跨域资源共享配置
- **XSS防护** - 使用HTMLPurifier净化输入

---

*本文档由UO株式会社技术团队维护，最后更新于2025年* 
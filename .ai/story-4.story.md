# Epic-2 - Story-4

数据导出功能

**作为** 管理人员
**我想要** 导出统计数据为不同格式
**以便** 在其他系统中使用或生成报告

## 状态

Draft

## 背景

在完成统计报表查询后，用户通常需要将数据导出为标准格式，用于进一步处理、分析或报告生成。数据导出功能需要支持CSV和Excel等常用格式，并确保导出的数据包含完整的时间范围信息、列标题和统计结果。导出功能应该易于使用，并能够灵活地选择要导出的字段和格式选项。在前后端分离架构下，前端负责提供用户友好的导出选项界面，后端负责生成相应的导出文件并提供下载。

## 估计

故事点数: 2

## 任务

1. - [ ] 实现后端服务
   1. - [ ] 创建ExportService.php服务类
   2. - [ ] 实现CSV生成方法
   3. - [ ] 实现Excel生成方法（使用PHPSpreadsheet库）
   4. - [ ] 添加文件下载功能

2. - [ ] 实现API控制器
   1. - [ ] 扩展Api/ShippingController.php
   2. - [ ] 实现GET /api/shipping/stats/export接口处理导出请求
   3. - [ ] 支持多种导出格式（CSV、Excel）
   4. - [ ] 添加文件流式下载支持

3. - [ ] 实现React前端组件
   1. - [ ] 创建ExportOptions.tsx组件管理导出选项
   2. - [ ] 创建ExportProgress.tsx组件显示导出进度
   3. - [ ] 添加导出按钮到统计组件
   4. - [ ] 支持导出配置保存和重用

4. - [ ] 实现状态管理
   1. - [ ] 扩展StatisticsStore.ts添加导出功能
   2. - [ ] 实现导出选项状态管理
   3. - [ ] 添加导出进度状态管理

5. - [ ] 实现API服务
   1. - [ ] 扩展statisticsService.ts添加导出方法
   2. - [ ] 实现文件下载处理

6. - [ ] 优化大数据量处理
   1. - [ ] 实现大数据量分批处理
   2. - [ ] 添加后台导出任务选项
   3. - [ ] 实现导出完成通知功能

7. - [ ] 优化和测试
   1. - [ ] 添加单元测试
   2. - [ ] 测试不同数据量下的性能
   3. - [ ] 测试不同导出格式的兼容性
   4. - [ ] 优化用户体验

## 约束

- 导出文件必须包含明确的时间范围信息和生成日期
- 导出功能在常规数据量下需要在5秒内完成
- 对于大数据量，需要提供进度指示和后台处理选项
- 导出的Excel文件需要包含适当的格式和样式
- 导出的CSV文件需要使用正确的编码（UTF-8）
- 导出功能需要处理潜在的内存限制问题
- 移动设备上也需要支持导出功能

## API规格

### GET /api/shipping/stats/export
导出统计数据
**请求参数**:
- format: 导出格式（csv或excel）
- start: 开始日期
- end: 结束日期
- groupBy: 分组方式（day、week、month）
- includeDetails: 是否包含详细数据
- filename: 自定义文件名（可选）

**响应**:
文件下载（二进制流）

### POST /api/shipping/stats/export/task
创建后台导出任务（用于大数据量）
**请求**:
```json
{
  "format": "excel",
  "start": "2023-01-01",
  "end": "2023-12-31",
  "groupBy": "day",
  "includeDetails": true,
  "filename": "年度发货统计",
  "notifyEmail": "user@example.com"
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "taskId": "export-123456",
    "status": "processing",
    "estimatedTime": 60
  }
}
```

### GET /api/shipping/stats/export/task/{taskId}
获取导出任务状态
**响应**:
```json
{
  "success": true,
  "data": {
    "taskId": "export-123456",
    "status": "completed",
    "progress": 100,
    "downloadUrl": "/api/shipping/stats/export/download/export-123456",
    "expiresAt": "2023-06-15T12:00:00"
  }
}
```

## 结构

数据导出功能将按以下结构实现：

### 后端文件
- 服务: services/ExportService.php
- 控制器: controllers/Api/ShippingController.php（扩展）
- 任务: jobs/ExportJob.php（用于后台处理）

### 前端文件
- 组件: 
  - react/src/components/statistics/ExportOptions.tsx
  - react/src/components/statistics/ExportProgress.tsx
- 状态管理: react/src/store/StatisticsStore.ts（扩展）
- 服务: react/src/services/statisticsService.ts（扩展）

## 图表

```mermaid
sequenceDiagram
    参与者 用户
    参与者 React前端
    参与者 API控制器
    参与者 StatisticsService
    参与者 ExportService
    参与者 文件系统
    
    用户->>React前端: 点击导出按钮
    React前端->>React前端: 显示导出选项对话框
    用户->>React前端: 选择导出选项并确认
    
    alt 小数据量导出
        React前端->>React前端: 显示加载指示器
        React前端->>API控制器: GET /api/shipping/stats/export（带参数）
        API控制器->>StatisticsService: 获取统计数据
        StatisticsService-->>API控制器: 返回统计数据
        API控制器->>ExportService: 生成导出文件
        ExportService->>ExportService: 格式化数据
        ExportService->>文件系统: 创建临时文件
        文件系统-->>ExportService: 返回文件句柄
        ExportService-->>API控制器: 返回文件路径
        API控制器-->>React前端: 返回文件下载流
        React前端->>React前端: 触发文件下载
        React前端-->>用户: 开始下载文件
    else 大数据量导出
        React前端->>API控制器: POST /api/shipping/stats/export/task
        API控制器->>ExportService: 创建后台导出任务
        ExportService-->>API控制器: 返回任务ID
        API控制器-->>React前端: 返回任务信息
        React前端->>React前端: 显示进度指示器
        
        loop 检查任务状态
            React前端->>API控制器: GET /api/shipping/stats/export/task/{taskId}
            API控制器->>ExportService: 获取任务状态
            ExportService-->>API控制器: 返回任务状态
            API控制器-->>React前端: 返回任务状态
            React前端->>React前端: 更新进度指示器
        end
        
        React前端->>React前端: 任务完成，显示下载链接
        React前端-->>用户: 提供下载链接
        用户->>React前端: 点击下载链接
        React前端->>API控制器: GET /api/shipping/stats/export/download/{taskId}
        API控制器->>ExportService: 获取导出文件
        ExportService-->>API控制器: 返回文件
        API控制器-->>React前端: 返回文件下载流
        React前端->>React前端: 触发文件下载
        React前端-->>用户: 开始下载文件
    end
```

## 开发备注

- 使用PHPSpreadsheet库处理Excel文件生成
- Excel文件需要添加适当的样式，如表头加粗、合适的列宽等
- 小数据量直接同步导出，大数据量使用后台任务处理
- 大数据量导出支持邮件通知和状态查询
- 确保CSV文件使用UTF-8编码和正确的分隔符
- 导出文件名应包含时间范围信息，便于识别
- 导出选项对话框需要简洁直观，同时支持高级选项
- 考虑添加导出模板功能，允许用户保存常用的导出配置
- 注意处理内存消耗，尤其是大数据量导出时
- 临时文件需要定时清理，避免占用过多磁盘空间

## 聊天命令日志

*此部分将在开发过程中记录* 
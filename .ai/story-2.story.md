# Epic-2 - Story-2

时间筛选和发货统计功能

**作为** 管理人员
**我想要** 筛选不同时间段的发货数据并查看统计结果
**以便** 分析不同时间段的发货情况，做出业务决策

## 状态

Draft

## 背景

发货统计的核心功能是根据时间筛选数据并生成统计报表。管理人员需要能够灵活地选择时间范围，包括预设的时间选项（如今天、昨天、本周、上周、本月、上月、去年等）以及自定义时间段。统计结果需要包括每个快递种类的发货数量以及所有种类的合计总数，方便用户快速了解发货总体情况和各快递的分布情况。基于前后端分离架构，我们将使用React实现前端交互，ECharts实现数据可视化，后端提供API接口处理数据查询和统计逻辑。

## 估计

故事点数: 4

## 任务

1. - [ ] 设计发货记录数据库表结构
   1. - [ ] 创建shipping_records表的迁移文件
   2. - [ ] 执行迁移创建表
   3. - [ ] 添加测试数据

2. - [ ] 实现后端模型和服务
   1. - [ ] 创建ShippingRecord.php模型
   2. - [ ] 定义与Courier模型的关系
   3. - [ ] 添加查询范围方法用于时间筛选
   4. - [ ] 创建ShippingStatisticsService.php服务类

3. - [ ] 实现统计服务方法
   1. - [ ] 实现按时间筛选数据的方法
   2. - [ ] 实现计算每个快递种类统计的方法
   3. - [ ] 实现计算汇总数据的方法
   4. - [ ] 实现时间段比较功能

4. - [ ] 实现API控制器
   1. - [ ] 创建Api/ShippingController.php
   2. - [ ] 实现GET /api/shipping/stats接口获取统计数据
   3. - [ ] 实现POST /api/shipping/stats/range接口获取指定时间范围统计
   4. - [ ] 添加缓存机制优化性能

5. - [ ] 实现页面控制器
   1. - [ ] 创建或扩展ShippingController.php
   2. - [ ] 实现stats方法提供基础视图
   3. - [ ] 添加API文档页面

6. - [ ] 实现React前端组件
   1. - [ ] 创建DateRangePicker.tsx组件实现时间选择
   2. - [ ] 创建StatsTable.tsx组件显示统计表格
   3. - [ ] 创建StatsChart.tsx组件显示统计图表
   4. - [ ] 创建StatsFilter.tsx组件实现其他过滤选项

7. - [ ] 实现ECharts图表
   1. - [ ] 创建柱状图展示各快递种类数量
   2. - [ ] 创建饼图展示各快递种类占比
   3. - [ ] 创建折线图展示趋势分析
   4. - [ ] 添加交互式图表控件

8. - [ ] 实现状态管理
   1. - [ ] 创建StatisticsStore.ts管理统计数据状态
   2. - [ ] 实现时间范围选择Action
   3. - [ ] 实现图表类型切换Action
   4. - [ ] 集成错误处理和加载状态

9. - [ ] 实现API服务
   1. - [ ] 创建statisticsService.ts实现API调用
   2. - [ ] 添加数据格式转换逻辑
   3. - [ ] 实现图表数据格式处理

10. - [ ] 优化和测试
    1. - [ ] 添加单元测试
    2. - [ ] 进行端到端测试
    3. - [ ] 优化性能和用户体验
    4. - [ ] 测试大数据量场景

## 约束

- 时间筛选控件必须简单易用，且支持灵活的日期范围选择
- 统计数据API响应时间需要在2秒内完成
- 图表必须清晰展示数据趋势和分布
- 移动设备上需要有良好的响应式体验
- 处理大量数据时需要分页或虚拟滚动优化
- 必须处理无数据的情况并提供友好提示

## 数据模型/架构

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

### GET /api/shipping/stats
获取默认统计数据（当前月份）
**响应**:
```json
{
  "success": true,
  "data": {
    "timeRange": {
      "start": "2023-06-01",
      "end": "2023-06-30",
      "label": "本月"
    },
    "summary": {
      "totalQuantity": 1250,
      "courierCount": 5,
      "dateCount": 22
    },
    "byDate": [
      {"date": "2023-06-01", "quantity": 50},
      {"date": "2023-06-02", "quantity": 65},
      {"date": "2023-06-03", "quantity": 55}
    ],
    "byCourier": [
      {"id": 1, "name": "顺丰速运", "quantity": 350, "percentage": 28},
      {"id": 2, "name": "中通快递", "quantity": 420, "percentage": 33.6},
      {"id": 3, "name": "京东物流", "quantity": 480, "percentage": 38.4}
    ]
  }
}
```

### POST /api/shipping/stats/range
获取指定时间范围的统计数据
**请求**:
```json
{
  "start": "2023-05-01",
  "end": "2023-05-31",
  "compareWith": {
    "start": "2023-04-01",
    "end": "2023-04-30"
  },
  "groupBy": "day"
}
```
**响应**:
```json
{
  "success": true,
  "data": {
    "timeRange": {
      "start": "2023-05-01",
      "end": "2023-05-31",
      "label": "自定义"
    },
    "summary": {
      "totalQuantity": 1480,
      "courierCount": 5,
      "dateCount": 31,
      "comparison": {
        "totalQuantity": 1320,
        "change": 12.12,
        "changeType": "increase"
      }
    },
    "byDate": [...],
    "byCourier": [...],
    "comparison": {
      "byDate": [...],
      "byCourier": [...]
    }
  }
}
```

## 结构

时间筛选和统计功能将按以下结构实现：

### 后端文件
- 模型: models/ShippingRecord.php
- 服务: services/ShippingStatisticsService.php
- 控制器: 
  - controllers/Api/ShippingController.php
  - controllers/ShippingController.php

### 前端文件
- 组件: 
  - react/src/components/statistics/DateRangePicker.tsx
  - react/src/components/statistics/StatsTable.tsx
  - react/src/components/statistics/StatsChart.tsx
  - react/src/components/statistics/StatsFilter.tsx
- 状态管理: react/src/store/StatisticsStore.ts
- 服务: react/src/services/statisticsService.ts
- 工具: 
  - react/src/utils/dateUtils.ts
  - react/src/utils/chartConfig.ts

## 图表

```mermaid
sequenceDiagram
    参与者 用户
    参与者 React前端
    参与者 API控制器
    参与者 StatisticsService
    参与者 ShippingRecord模型
    参与者 数据库
    
    用户->>React前端: 访问统计页面
    React前端->>React前端: 初始化StatisticsStore
    React前端->>API控制器: GET /api/shipping/stats
    API控制器->>StatisticsService: 获取默认统计数据
    StatisticsService->>ShippingRecord模型: 查询时间范围内的记录
    ShippingRecord模型->>数据库: 执行查询
    数据库-->>ShippingRecord模型: 返回记录数据
    ShippingRecord模型-->>StatisticsService: 返回记录集合
    StatisticsService->>StatisticsService: 处理和聚合数据
    StatisticsService-->>API控制器: 返回统计结果
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新StatisticsStore状态
    React前端->>React前端: 渲染ECharts图表和表格
    React前端-->>用户: 显示统计结果
    
    用户->>React前端: 选择时间范围
    React前端->>React前端: 更新时间筛选状态
    React前端->>API控制器: POST /api/shipping/stats/range
    API控制器->>StatisticsService: 请求指定时间范围的统计数据
    StatisticsService->>ShippingRecord模型: 查询指定时间范围的记录
    ShippingRecord模型->>数据库: 执行查询
    数据库-->>ShippingRecord模型: 返回记录数据
    ShippingRecord模型-->>StatisticsService: 返回记录集合
    StatisticsService->>StatisticsService: 处理和聚合数据
    StatisticsService-->>API控制器: 返回处理后的统计数据
    API控制器-->>React前端: 返回JSON响应
    React前端->>React前端: 更新StatisticsStore状态
    React前端->>React前端: 重新渲染ECharts图表和表格
    React前端-->>用户: 更新统计结果显示
```

## 开发备注

- 使用日期选择组件需要支持多种日期格式和范围选择
- 考虑使用缓存来提高频繁查询的性能
- ECharts图表需要配置自适应大小，确保在不同设备上有良好显示
- 考虑添加数据下钻功能，允许用户点击图表查看更详细信息
- 状态管理需要处理多种筛选条件的组合
- 添加数据加载中的状态显示和优雅的错误处理
- 考虑添加图表配置保存功能，让用户可以保存常用的统计视图

## 聊天命令日志

*此部分将在开发过程中记录* 
@php require_frontend_packages(['datatables', 'chartjs']); @endphp

@extends('layout.default')

@section('title', '库存监控面板')

@push('pageScripts')
<script src="{{ $U('/viewjs/dashboard.js?v=', true) }}{{ $version }}"></script>
@endpush

@section('content')
<div class="row">
    <div class="col">
        <h1 class="page-title">库存监控面板</h1>
    </div>
</div>

<!-- 日期范围筛选器 -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa-solid fa-calendar"></i>&nbsp;日期区间</span>
            </div>
            <input type="text" id="daterange-picker" class="form-control" value="全部时间">
        </div>
    </div>
    <div class="col-md-2 ml-auto">
        <button id="refresh-dashboard" class="btn btn-outline-primary btn-block">
            <i class="fa-solid fa-arrows-retweet"></i>&nbsp;刷新数据
        </button>
    </div>
</div>

<!-- 库存概览卡片 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">总商品数</h5>
                <h2 class="display-4">{{ $totalProducts }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">总在库商品数</h5>
                <h2 class="display-4">{{ $productsInStock }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">库存总价值</h5>
                <h2 class="display-4">{{ number_format($totalStockValue, 0) }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">库存不足商品数</h5>
                <h2 class="display-4">{{ $stockMissing }}</h2>
            </div>
        </div>
    </div>
</div>

<!-- 月份增长率和出入库分析 -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">月度库存变动趋势</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="stockTrendChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">商品分类分布</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 库存预警和当前库存排名 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">库存预警</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>产品</th>
                                <th>最小库存量</th>
                                <th>缺少数量</th>
                                <th>单位</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->min_stock_amount }}</td>
                                <td><span class="text-danger">{{ $product->amount_missing }}</span></td>
                                <td>{{ $product->qu_name }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">当前在库商品排名</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>产品</th>
                                <th>在库数量</th>
                                <th>价值</th>
                                <th>单位</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topStockProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->amount }}</td>
                                <td>{{ number_format($product->value, 2) }}</td>
                                <td>{{ $product->qu_name }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 最近入库和出库记录 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">最近入库记录</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>产品</th>
                                <th>数量</th>
                                <th>日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAdditions as $addition)
                            <tr>
                                <td>{{ $addition->name }}</td>
                                <td>{{ $addition->amount }}</td>
                                <td>{{ date('Y-m-d', strtotime($addition->date)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">最近出库记录</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>产品</th>
                                <th>数量</th>
                                <th>日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentConsumptions as $consumption)
                            <tr>
                                <td>{{ $consumption->name }}</td>
                                <td>{{ $consumption->amount }}</td>
                                <td>{{ date('Y-m-d', strtotime($consumption->date)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 库存位置分布 -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">库存位置分布</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="locationChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('pageScripts')
<script>
    // 库存变动趋势图数据
    var trendData = {!! $stockTrend !!};
    
    // 商品分类分布数据
    var categoryData = {!! $categoryDistribution !!};
    
    // 库存位置分布数据
    var locationData = {!! $locationDistribution !!};
</script>
@endpush

<style>
/* 图表容器样式修复 */
.chart-container {
  position: relative;
  height: 300px;
  max-height: 300px;
  width: 100%;
}
</style> 
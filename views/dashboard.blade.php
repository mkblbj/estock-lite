@php require_frontend_packages(['datatables', 'chartjs']); @endphp

@extends('layout.default')

@section('title', $__t('dashboard.title'))

@push('pageScripts')
<script src="{{ $U('/viewjs/dashboard.js?v=', true) }}{{ $version }}"></script>
@endpush

@section('content')
<div class="row">
    <div class="col">
        <h1 class="page-title">{{ $__t('dashboard.title') }}</h1>
    </div>
</div>

<!-- 日期范围筛选器 -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa-solid fa-calendar"></i>&nbsp;{{ $__t('dashboard.date_range') }}</span>
            </div>
            <input type="text" id="daterange-picker" class="form-control" value="{{ $__t('dashboard.all_time') }}">
        </div>
    </div>
    <div class="col-md-2 ml-auto">
        <button id="refresh-dashboard" class="btn btn-outline-primary btn-block">
            <i class="fa-solid fa-arrows-retweet"></i>&nbsp;{{ $__t('dashboard.refresh_data') }}
        </button>
    </div>
</div>

<!-- 库存概览卡片 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">{{ $__t('dashboard.total_products') }}</h5>
                <h2 class="display-4">{{ $totalProducts }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">{{ $__t('dashboard.products_in_stock') }}</h5>
                <h2 class="display-4">{{ $productsInStock }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">{{ $__t('dashboard.total_stock_value') }}</h5>
                <h2 class="display-4">{{ number_format($totalStockValue, 0) }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-muted">{{ $__t('dashboard.missing_products') }}</h5>
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
                <h5 class="mb-0">{{ $__t('dashboard.monthly_stock_trend') }}</h5>
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
                <h5 class="mb-0">{{ $__t('dashboard.category_distribution') }}</h5>
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
                <h5 class="mb-0">{{ $__t('dashboard.stock_alerts') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ $__t('Product') }}</th>
                                <th>{{ $__t('Min. stock amount') }}</th>
                                <th>{{ $__t('dashboard.missing_amount') }}</th>
                                <th>{{ $__t('Quantity units') }}</th>
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
                <h5 class="mb-0">{{ $__t('dashboard.current_stock_ranking') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ $__t('Product') }}</th>
                                <th>{{ $__t('dashboard.in_stock_amount') }}</th>
                                <th>{{ $__t('dashboard.value') }}</th>
                                <th>{{ $__t('Quantity units') }}</th>
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
                <h5 class="mb-0">{{ $__t('dashboard.recent_additions') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ $__t('Product') }}</th>
                                <th>{{ $__t('Amount') }}</th>
                                <th>{{ $__t('dashboard.date') }}</th>
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
                <h5 class="mb-0">{{ $__t('dashboard.recent_consumptions') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ $__t('Product') }}</th>
                                <th>{{ $__t('Amount') }}</th>
                                <th>{{ $__t('dashboard.date') }}</th>
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
                <h5 class="mb-0">{{ $__t('dashboard.location_distribution') }}</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="locationChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 隐藏字段用于数据传递 -->
<div style="display: none;">
    <input type="hidden" id="hidden-trend-data" value='{{ $stockTrend }}'>
    <input type="hidden" id="hidden-category-data" value='{{ $categoryDistribution }}'>
    <input type="hidden" id="hidden-location-data" value='{{ $locationDistribution }}'>
    <input type="hidden" id="hidden-has-data" value="{{ $hasStockTrendData ? 'true' : 'false' }}">
</div>
@stop

@push('pageScripts')
<script>
    // 调试信息
    console.log("{{ $__t('dashboard.loading_data') }}");
    
    // 库存变动趋势图数据
    var trendData;
    try {
        trendData = {!! $stockTrend !!};
        if (typeof trendData === 'string') {
            trendData = JSON.parse(trendData);
        }
    } catch (e) {
        console.error("{{ $__t('dashboard.trend_data_error') }}:", e);
        trendData = [];
    }
    
    // 趋势数据标志
    var hasStockTrendData = {{ $hasStockTrendData ? 'true' : 'false' }};
    
    // 商品分类分布数据
    var categoryData;
    try {
        categoryData = {!! $categoryDistribution !!};
        if (typeof categoryData === 'string') {
            categoryData = JSON.parse(categoryData);
        }
    } catch (e) {
        console.error("{{ $__t('dashboard.category_data_error') }}:", e);
        categoryData = [];
    }
    
    // 库存位置分布数据
    var locationData;
    try {
        locationData = {!! $locationDistribution !!};
        if (typeof locationData === 'string') {
            locationData = JSON.parse(locationData);
        }
    } catch (e) {
        console.error("{{ $__t('dashboard.location_data_error') }}:", e);
        locationData = [];
    }
    
    // 输出调试信息
    console.log("{{ $__t('dashboard.trend_data_loaded') }}:", trendData);
    console.log("{{ $__t('dashboard.trend_data_flag') }}:", hasStockTrendData);
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
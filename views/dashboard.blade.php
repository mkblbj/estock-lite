@php require_frontend_packages(['chartjs', 'daterangepicker']); @endphp

@extends('layout.default')

@section('title', __('Dashboard'))

@section('content')
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Total Items') }}</h5>
                <p class="card-text" id="total-items">{{ $stockOverview['total_items'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Products in Stock') }}</h5>
                <p class="card-text" id="expiring-soon">{{ $stockOverview['products_in_stock'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Total Value') }}</h5>
                <p class="card-text" id="total-value">{{ $stockOverview['total_value'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Items at Risk') }}</h5>
                <p class="card-text" id="items-at-risk">{{ $stockOverview['items_at_risk'] }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Stock Trend') }}</h5>
                <input type="text" id="custom-date-range" class="form-control mb-3" placeholder="选择自定义日期范围">
                <div id="stock-trend-loading" class="text-center py-3">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                    <p class="mt-2">加载中...</p>
                </div>
                <canvas id="stock-trend-chart" style="height: 250px; max-height: 250px; display: none;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Category Distribution') }}</h5>
                <div id="category-distribution-loading" class="text-center py-3">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                    <p class="mt-2">加载中...</p>
                </div>
                <canvas id="category-distribution-chart" style="height: 250px; max-height: 250px; display: none;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Stock Alerts') }}</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Current Amount') }}</th>
                                <th>{{ __('Min Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($stockAlerts) > 0)
                                @foreach($stockAlerts as $alert)
                                <tr class="stock-alert-item" data-product-id="{{ $alert->id }}">
                                    <td>{{ $alert->name }}</td>
                                    <td>{{ $alert->amount }}</td>
                                    <td>{{ $alert->min_stock_amount }}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center text-muted">{{ __('No stock alerts found') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Current Stock') }}</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($productsInStock) > 0)
                                @foreach($productsInStock as $product)
                                <tr class="product-stock-item" data-product-id="{{ $product->product_id }}">
                                    <td>{{ $product->product_name }}</td>
                                    <td>{{ $product->amount }}</td>
                                    <td>{{ number_format($product->value, 2) }}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center text-muted">{{ __('No products in stock') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('pageScripts')
<!-- Chart.js -->
<script src="{{ $U('/packages/chart.js/dist/Chart.min.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/packages/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.min.js?v=', true) }}{{ $version }}"></script>

<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

<!-- Daterangepicker -->
<script src="{{ $U('/packages/daterangepicker/daterangepicker.js?v=', true) }}{{ $version }}"></script>
<link rel="stylesheet" href="{{ $U('/packages/daterangepicker/daterangepicker.css?v=', true) }}{{ $version }}">

<!-- 初始化数据 -->
<script>
    // 确保图表依赖已加载
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
    }
    
    if (typeof moment === 'undefined') {
        console.error('Moment.js not loaded');
    }
    
    // 保存完整数据供日期筛选使用
    window.fullStockTrendData = {!! json_encode($stockTrendData, JSON_NUMERIC_CHECK) !!};
    
    // 默认显示最近14天的数据
    window.stockTrendData = window.fullStockTrendData.slice(-14);
    
    // 限制类别数据只取前7个
    var fullCategoryData = {!! json_encode($categoryDistribution, JSON_NUMERIC_CHECK) !!};
    window.categoryDistributionData = fullCategoryData.slice(0, 7);
    
    // 如果类别超过7个，添加"其他"类别
    if (fullCategoryData.length > 7) {
        var otherCount = 0;
        for (var i = 7; i < fullCategoryData.length; i++) {
            otherCount += parseInt(fullCategoryData[i].count || 0);
        }
        window.categoryDistributionData.push({
            name: '其他',
            count: otherCount
        });
    }
    
    // 添加调试信息
    console.log('Data initialized:', {
        fullStockTrendData: window.fullStockTrendData,
        stockTrendData: window.stockTrendData,
        categoryDistributionData: window.categoryDistributionData
    });
    
    // 在文档加载完成后加载dashboard.js
    $(document).ready(function() {
        $.getScript("{{ $U('/viewjs/dashboard.js?v=', true) }}{{ $version }}");
    });
</script>
@endpush
@endsection
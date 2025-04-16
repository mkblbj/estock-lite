@php 
// 使用系统内置的包加载方式
require_frontend_packages([
    'datatables',
    'chartjs',
    'tempusdominus'
]); 
@endphp

@extends('layout.default')

@section('title', $__t('Courier Overview'))

@section('head_content')
<style>
/* 修复Mac Chrome中图表显示问题 */
.card-body canvas {
    max-width: 100%;
    height: auto !important;
}
.card {
    overflow: hidden;
}
/* 移除导致双滚动条的设置 */
/*.card-body {
    max-height: 400px;
    overflow: auto;
}*/
/* 确保表格内容不超出容器 */
.table-responsive {
    overflow-x: auto;
}
/* 快速选择按钮样式 */
.date-range-preset {
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.date-range-preset.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
/* 响应式调整 */
@media (max-width: 767.98px) {
    .col-md-5, .col-md-7 {
        margin-bottom: 1rem;
    }
}
/* 修复Mac Chrome下select问题 */
select.form-control {
    -webkit-appearance: menulist !important;
    appearance: menulist !important;
    background-image: none !important;
}
/* 确保日期选择器正常显示并贴近按钮 */
.tempusdominus-bootstrap-4 {
    z-index: 1060 !important;
}
/* 优化日期选择器位置，使其贴近按钮 */
.bootstrap-datetimepicker-widget {
    margin-top: 0 !important;
}
/* 减小模态框内部间距，避免不必要的空白 */
.modal-body {
    padding: 1rem;
}
.modal-body .form-control {
    padding: 0.25rem 0.5rem;
}
.modal-body .input-group-text {
    padding: 0.25rem 0.5rem;
}
.modal-body label {
    margin-bottom: 0.25rem;
}
/* 加载中状态样式 */
.loading {
    position: relative;
    pointer-events: none;
}
.loading::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
</style>
@stop

@section('content')
<div class="row">
	<div class="col">
		<div class="title-related-links">
			<h2 class="title">@yield('title') <small class="text-muted">{{ $fromDate }} - {{ $toDate }}</small></h2>
			<div class="float-right">
				<!-- 添加手动刷新按钮 -->
				<button class="btn btn-outline-dark mr-2" type="button" id="refresh-data-button">
					<i class="fa-solid fa-sync-alt"></i> {{ $__t('Refresh') }}
				</button>
				<!-- 筛选按钮 -->
				<button class="btn btn-outline-dark" type="button" data-toggle="modal" data-target="#filterModal">
					<i class="fa-solid fa-filter"></i> {{ $__t('Filter') }}
				</button>
			</div>
		</div>
	</div>
</div>

<!-- 过滤器模态对话框 -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="filterModalLabel"><i class="fa-solid fa-filter"></i> {{ $__t('Filter') }}</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pb-0">
				<div class="row">
					<!-- 左侧：快速选择按钮 -->
					<div class="col-md-5">
						<label><i class="fa-solid fa-clock"></i> {{ $__t('Quick Select') }}</label>
						<div class="d-flex flex-column">
							<button type="button" class="btn btn-outline-secondary mb-2 date-range-preset text-left" data-range="today">{{ $__t('Today') }}</button>
							<button type="button" class="btn btn-outline-secondary mb-2 date-range-preset text-left" data-range="this-week">{{ $__t('This Week') }}</button>
							<button type="button" class="btn btn-outline-secondary mb-2 date-range-preset text-left" data-range="this-month">{{ $__t('This Month') }}</button>
							<button type="button" class="btn btn-outline-secondary mb-2 date-range-preset text-left" data-range="last-month">{{ $__t('Last Month') }}</button>
							<button type="button" class="btn btn-outline-secondary mb-2 date-range-preset text-left" data-range="this-year">{{ $__t('This Year') }}</button>
						</div>
					</div>
					
					<!-- 右侧：日历选择器 -->
					<div class="col-md-7">
						<div class="row">
							<div class="col-md-12 mb-2">
								<label><i class="fa-solid fa-calendar"></i> {{ $__t('From') }}</label>
								<div class="input-group date" id="datetimepicker-from" data-target-input="nearest">
									<input type="text" class="form-control datetimepicker-input" id="date-filter-from" data-target="#datetimepicker-from" value="{{ $fromDate }}">
									<div class="input-group-append" data-target="#datetimepicker-from" data-toggle="datetimepicker">
										<span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
									</div>
								</div>
							</div>
							
							<div class="col-md-12 mb-2">
								<label><i class="fa-solid fa-calendar"></i> {{ $__t('To') }}</label>
								<div class="input-group date" id="datetimepicker-to" data-target-input="nearest">
									<input type="text" class="form-control datetimepicker-input" id="date-filter-to" data-target="#datetimepicker-to" value="{{ $toDate }}">
									<div class="input-group-append" data-target="#datetimepicker-to" data-toggle="datetimepicker">
										<span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
									</div>
								</div>
							</div>
							
							<div class="col-md-12 mb-2">
								<label><i class="fa-solid fa-chart-line"></i> {{ $__t('Interval') }}</label>
								<select class="form-control" id="interval-filter">
									<option value="day" @if($interval == 'day') selected @endif>{{ $__t('Day') }}</option>
									<option value="month" @if($interval == 'month') selected @endif>{{ $__t('Month') }}</option>
									<option value="year" @if($interval == 'year') selected @endif>{{ $__t('Year') }}</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $__t('Cancel') }}</button>
				<button id="filter-apply-button" class="btn btn-primary">{{ $__t('Apply') }}</button>
			</div>
		</div>
	</div>
</div>

<hr class="my-2">

<!-- 1. 调整为顶部显示Detailed data，调整为类似Total summary的表格样式 -->
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa-solid fa-table"></i> {{ $__t('Detailed data') }}
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm table-striped">
						<thead>
							<tr>
								<th>{{ $__t(ucfirst($interval)) }}</th>
								@foreach($courierTypes as $type)
									@if($type->active == 1)
									<th class="text-right">{{ $type->name }}</th>
									@endif
								@endforeach
								<th class="text-right">{{ $__t('Total') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($statistics as $stat)
							<tr>
								<td>{{ $stat['interval_key'] }}</td>
								@foreach($courierTypes as $type)
									@if($type->active == 1)
										@php
											$found = false;
											foreach($stat['couriers'] as $courier) {
												if ($courier['courier_id'] == $type->id) {
													echo '<td class="text-right">' . $courier['count'] . '</td>';
													$found = true;
													break;
												}
											}
											if (!$found) {
												echo '<td class="text-right">0</td>';
											}
										@endphp
									@endif
								@endforeach
								<td class="text-right font-weight-bold">{{ $stat['total'] }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 2. 中间显示统计图表 -->
<div class="row mt-3">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa-solid fa-chart-bar"></i> {{ $__t('Courier statistics by') }} {{ $__t(ucfirst($interval)) }}
			</div>
			<div class="card-body" style="height: 400px;">
				<canvas id="courier-statistics-chart"></canvas>
			</div>
		</div>
	</div>
</div>

<!-- 3. 底部显示Total summary和饼图，各占一半 -->
<div class="row mt-3">
	<div class="col-12 col-xl-6">
		<div class="card h-100">
			<div class="card-header">
				<i class="fa-solid fa-table"></i> {{ $__t('Total summary') }}
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm table-striped">
						<thead>
							<tr>
								<th>{{ $__t('Courier type') }}</th>
								<th class="text-right">{{ $__t('Count') }}</th>
								<th class="text-right">{{ $__t('Percentage') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($totalsByType['couriers'] as $courier)
							<tr>
								<td>{{ $courier['courier_name'] }}</td>
								<td class="text-right">{{ $courier['total_count'] }}</td>
								<td class="text-right">{{ number_format($courier['total_count'] / $totalsByType['total'] * 100, 1) }}%</td>
							</tr>
							@endforeach
							<tr class="table-primary font-weight-bold">
								<td>{{ $__t('Total') }}</td>
								<td class="text-right">{{ $totalsByType['total'] }}</td>
								<td class="text-right">100%</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	
	<div class="col-12 col-xl-6">
		<div class="card h-100">
			<div class="card-header">
				<i class="fa-solid fa-chart-pie"></i> {{ $__t('Total by courier type') }}
			</div>
			<div class="card-body">
				<div style="position: relative; height: 350px; width: 100%;">
					<canvas id="courier-pie-chart"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
@stop

@section('scripts')
<script>
	// 手动刷新按钮点击事件
	$(document).ready(function() {
		// 不需要在这里添加点击事件，已在overview.js中完全实现
	});
</script>
@stop 
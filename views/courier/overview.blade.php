@php require_frontend_packages(['chart.js']); @endphp

@extends('layout.default')

@section('title', '快递统计概览')

@section('content')
<div class="row">
	<div class="col">
		<div class="title-related-links">
			<h2 class="title">@yield('title')</h2>
			<div class="float-right">
				<div class="dropdown">
					<button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-toggle="dropdown">
						<i class="fa-solid fa-filter"></i> {{ $__t('Filter') }}
					</button>
					<div class="dropdown-menu dropdown-menu-right">
						<div class="dropdown-item">
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fa-solid fa-calendar"></i> {{ $__t('From') }}</span>
								</div>
								<input type="text" class="form-control datepicker" id="date-filter-from" value="{{ $fromDate }}">
							</div>
						</div>
						<div class="dropdown-item">
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fa-solid fa-calendar"></i> {{ $__t('To') }}</span>
								</div>
								<input type="text" class="form-control datepicker" id="date-filter-to" value="{{ $toDate }}">
							</div>
						</div>
						<div class="dropdown-item">
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fa-solid fa-chart-line"></i> {{ $__t('Interval') }}</span>
								</div>
								<select class="form-control" id="interval-filter">
									<option value="day" @if($interval == 'day') selected @endif>{{ $__t('Day') }}</option>
									<option value="week" @if($interval == 'week') selected @endif>{{ $__t('Week') }}</option>
									<option value="month" @if($interval == 'month') selected @endif>{{ $__t('Month') }}</option>
									<option value="year" @if($interval == 'year') selected @endif>{{ $__t('Year') }}</option>
								</select>
							</div>
						</div>
						<div class="dropdown-divider"></div>
						<div class="dropdown-item">
							<button id="filter-apply-button" class="btn btn-primary btn-sm w-100">{{ $__t('Apply') }}</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<hr class="my-2">

<div class="row">
	<div class="col-12 col-xl-6">
		<div class="card">
			<div class="card-header">
				<i class="fa-solid fa-chart-bar"></i> {{ $__t('Courier statistics by') }} {{ $__t(ucfirst($interval)) }}
			</div>
			<div class="card-body">
				<canvas id="courier-statistics-chart"></canvas>
			</div>
		</div>
	</div>
	
	<div class="col-12 col-xl-6">
		<div class="card">
			<div class="card-header">
				<i class="fa-solid fa-chart-pie"></i> {{ $__t('Total by courier type') }}
			</div>
			<div class="card-body">
				<canvas id="courier-pie-chart"></canvas>
			</div>
		</div>
	</div>
</div>

<div class="row mt-3">
	<div class="col-12">
		<div class="card">
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
</div>

<div class="row mt-3">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa-solid fa-table"></i> {{ $__t('Detailed data') }}
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table id="courier-detailed-table" class="table table-sm table-striped">
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
@stop 
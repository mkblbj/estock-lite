@extends('layout.default')

@section('title', '快递发件记录管理')

@section('content')
<div class="row">
	<div class="col-12 col-md-6 col-xl-4 pb-3">
		<h2 class="title">@yield('title')</h2>

		<hr class="my-2">

		<form id="courier-entry-form" novalidate>
			<div class="form-group">
				<label for="entry_date">{{ $__t('Date') }}</label>
				<div class="input-group date">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
					</div>
					<input type="text" class="form-control datepicker" required id="entry_date" name="entry_date" value="{{ $currentDate }}">
					<div class="invalid-feedback">{{ $__t('A date is required') }}</div>
				</div>
			</div>

			<div class="form-group">
				<label for="courier_type_id">{{ $__t('Courier type') }}</label>
				<select class="form-control" required id="courier_type_id" name="courier_type_id">
					<option value="">{{ $__t('Please select') }}</option>
					@foreach($courierTypes as $courierType)
						@if($courierType->active == 1)
						<option value="{{ $courierType->id }}">{{ $courierType->name }}</option>
						@endif
					@endforeach
				</select>
				<div class="invalid-feedback">{{ $__t('A courier type is required') }}</div>
			</div>

			<div class="form-group">
				<label for="count">{{ $__t('Count') }}</label>
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa-solid fa-box"></i></span>
					</div>
					<input type="number" class="form-control" required id="count" name="count" min="0" value="0">
					<div class="invalid-feedback">{{ $__t('A valid count is required') }}</div>
				</div>
				<small class="form-text text-muted">{{ $__t('Enter the number of packages sent') }}</small>
			</div>

			<button id="save-courier-entry-button" class="btn btn-success">{{ $__t('Save') }}</button>
		</form>
	</div>

	<div class="col-12 col-md-6 col-xl-8">
		<div class="title-related-links">
			<h2 class="title">
				{{ $__t('Recent entries') }}
			</h2>
			<div class="float-right">
				<button class="btn btn-outline-dark d-md-none mt-2 order-1 order-md-3" type="button" data-toggle="collapse" data-target="#table-filter-row" aria-expanded="false" aria-controls="table-filter-row">
					<i class="fa-solid fa-filter"></i>
				</button>
			</div>
		</div>

		<hr class="my-2">

		<div class="row collapse d-md-flex" id="table-filter-row">
			<div class="col-12 col-md-6 col-xl-3">
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa-solid fa-search"></i></span>
					</div>
					<input type="text" id="search" class="form-control" placeholder="{{ $__t('Search') }}">
				</div>
			</div>

			<div class="col-12 col-md-6 col-xl-3">
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa-solid fa-filter"></i></span>
					</div>
					<select id="courier-type-filter" class="form-control">
						<option value="">{{ $__t('All courier types') }}</option>
						@foreach($courierTypes as $courierType)
							@if($courierType->active == 1)
							<option value="{{ $courierType->id }}">{{ $courierType->name }}</option>
							@endif
						@endforeach
					</select>
				</div>
			</div>
			
			<div class="col-12 col-md-6 col-xl-5">
				<div class="float-right">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">{{ $__t('Date range') }}</span>
						</div>
						<input type="text" class="form-control datepicker" id="date-filter-from">
						<div class="input-group-prepend input-group-append">
							<span class="input-group-text">{{ $__t('to') }}</span>
						</div>
						<input type="text" class="form-control datepicker" id="date-filter-to">
						<div class="input-group-append">
							<button id="filter-apply-button" class="btn btn-outline-primary">{{ $__t('Apply') }}</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col">
				<table id="courier-entries-table" class="table table-sm table-striped nowrap w-100">
					<thead>
						<tr>
							<th class="border-top-0">{{ $__t('Date') }}</th>
							<th class="border-top-0">{{ $__t('Courier type') }}</th>
							<th class="border-top-0">{{ $__t('Count') }}</th>
							<th class="border-top-0"></th>
						</tr>
					</thead>
					<tbody class="d-none">
						<!-- 数据将通过API动态加载 -->
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="courier-entry-edit-modal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">{{ $__t('Edit courier entry') }}</h4>
				<button type="button" class="close" data-dismiss="modal">
					<span>&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form id="courier-entry-edit-form" novalidate>
					<div class="form-group">
						<label for="edit-entry_date">{{ $__t('Date') }}</label>
						<div class="input-group date">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
							</div>
							<input type="text" class="form-control datepicker" required id="edit-entry_date" name="entry_date">
							<div class="invalid-feedback">{{ $__t('A date is required') }}</div>
						</div>
					</div>

					<div class="form-group">
						<label for="edit-courier_type_id">{{ $__t('Courier type') }}</label>
						<select class="form-control" required id="edit-courier_type_id" name="courier_type_id">
							<option value="">{{ $__t('Please select') }}</option>
							@foreach($courierTypes as $courierType)
								@if($courierType->active == 1)
								<option value="{{ $courierType->id }}">{{ $courierType->name }}</option>
								@endif
							@endforeach
						</select>
						<div class="invalid-feedback">{{ $__t('A courier type is required') }}</div>
					</div>

					<div class="form-group">
						<label for="edit-count">{{ $__t('Count') }}</label>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fa-solid fa-box"></i></span>
							</div>
							<input type="number" class="form-control" required id="edit-count" name="count" min="0">
							<div class="invalid-feedback">{{ $__t('A valid count is required') }}</div>
						</div>
					</div>

					<input type="hidden" id="edit-courier-entry-id" name="id" value="">
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $__t('Cancel') }}</button>
				<button type="button" id="save-courier-entry-edit-button" class="btn btn-primary">{{ $__t('Save') }}</button>
			</div>
		</div>
	</div>
</div>
@stop 
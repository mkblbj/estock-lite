@php require_frontend_packages(['datatables']); @endphp

@extends('layout.default')

@section('title', '快递类型管理')

@section('content')
<div class="row">
	<div class="col">
		<div class="title-related-links">
			<h2 class="title">
				@yield('title')
			</h2>
			<div class="float-right">
				<button class="btn btn-outline-dark d-md-none mt-2 order-1 order-md-3" type="button" data-toggle="collapse" data-target="#table-filter-row" aria-expanded="false" aria-controls="table-filter-row">
					<i class="fa-solid fa-filter"></i>
				</button>
				<button class="btn btn-outline-dark d-md-none mt-2 order-1 order-md-3" type="button" data-toggle="collapse" data-target="#related-links" aria-expanded="false" aria-controls="related-links">
					<i class="fa-solid fa-ellipsis-v"></i>
				</button>
				<div class="related-links collapse d-md-flex order-2 width-xs-sm-100" id="related-links">
					<!-- Add按钮已移至过滤行中 -->
				</div>
			</div>
		</div>
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
	<div class="col-12 col-md-6 col-xl-3 d-flex align-items-center">
		<!-- 移除Show disabled复选框 -->
		<a class="btn btn-primary" href="javascript:void(0)" id="add-courier-type-button">
			{{ $__t('Add') }}
		</a>
	</div>
</div>

<div class="row mt-3">
	<div class="col">
		<table id="courier-types-table" class="table table-hover">
			<thead>
				<tr>
					<th class="border-top-0" style="width: 120px">{{ $__t('Actions') }}</th>
					<th class="border-top-0">{{ $__t('Name') }}</th>
					<th class="border-top-0">{{ $__t('Description') }}</th>
					<th class="border-top-0 d-none">{{ $__t('Active') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($courierTypes as $courierType)
				<tr class="@if($courierType->active == 0) text-muted @endif">
					<td>
						<a class="btn btn-sm btn-info edit-courier-type" href="javascript:void(0)" data-courier-type-id="{{ $courierType->id }}" data-courier-type-name="{{ $courierType->name }}" data-courier-type-description="{{ $courierType->description }}" data-courier-type-active="{{ $courierType->active }}">
							<i class="fa-solid fa-edit"></i>
						</a>
						<a class="btn btn-sm btn-danger courier-type-delete-button" href="javascript:void(0)" data-courier-type-id="{{ $courierType->id }}" data-courier-type-name="{{ $courierType->name }}" data-courier-type-active="{{ $courierType->active }}">
							<i class="fa-solid fa-trash"></i>
						</a>
					</td>
					<td>
						{{ $courierType->name }}
					</td>
					<td>
						{{ $courierType->description }}
					</td>
					<td class="d-none">
						{{ $courierType->active }}
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>

<div class="modal fade" id="courier-type-add-modal" tabindex="-1" role="dialog" aria-labelledby="add-modal-title" aria-modal="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="add-modal-title">{{ $__t('Add courier type') }}</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="{{ $__t('Close') }}">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form id="courier-type-form" novalidate>
					<div class="form-group">
						<label for="name">{{ $__t('Name') }}</label>
						<input type="text" class="form-control" required id="name" name="name">
						<div class="invalid-feedback">{{ $__t('A name is required') }}</div>
					</div>

					<div class="form-group">
						<label for="description">{{ $__t('Description') }}</label>
						<input type="text" class="form-control" id="description" name="description">
					</div>

					<div class="form-group">
						<div class="custom-control custom-checkbox">
							<input type="checkbox" class="form-check-input custom-control-input" id="active" name="active" value="1" checked>
							<label class="form-check-label custom-control-label" for="active">{{ $__t('Active') }}</label>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $__t('Cancel') }}</button>
				<button type="button" id="save-courier-type-button" class="btn btn-primary">{{ $__t('Save') }}</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="courier-type-edit-modal" tabindex="-1" role="dialog" aria-labelledby="edit-modal-title" aria-modal="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="edit-modal-title">{{ $__t('Edit courier type') }}</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="{{ $__t('Close') }}">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form id="courier-type-edit-form" novalidate>
					<div class="form-group">
						<label for="edit-name">{{ $__t('Name') }}</label>
						<input type="text" class="form-control" required id="edit-name" name="name">
						<div class="invalid-feedback">{{ $__t('A name is required') }}</div>
					</div>

					<div class="form-group">
						<label for="edit-description">{{ $__t('Description') }}</label>
						<input type="text" class="form-control" id="edit-description" name="description">
					</div>

					<div class="form-group">
						<div class="custom-control custom-checkbox">
							<input type="checkbox" class="form-check-input custom-control-input" id="edit-active" name="active" value="1">
							<label class="form-check-label custom-control-label" for="edit-active">{{ $__t('Active') }}</label>
						</div>
					</div>

					<input type="hidden" id="edit-courier-type-id" name="id" value="">
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $__t('Cancel') }}</button>
				<button type="button" id="save-courier-type-edit-button" class="btn btn-primary">{{ $__t('Save') }}</button>
			</div>
		</div>
	</div>
</div>
@stop 
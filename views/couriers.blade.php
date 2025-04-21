@php require_frontend_packages(['datatables']); @endphp

@extends('layout.default')

@section('title', $__t('快递类型管理'))

@section('content')
<div class="row">
    <div class="col">
        <div class="title-related-links border-bottom mb-2 py-1">
            <h2 class="title">@yield('title')</h2>
            <div class="float-right">
                <button class="btn btn-outline-dark d-md-none mt-2"
                    type="button"
                    data-toggle="collapse"
                    data-target="#table-filter-row">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <button class="btn btn-outline-dark d-md-none mt-2"
                    type="button"
                    data-toggle="collapse"
                    data-target="#related-links">
                    <i class="fa-solid fa-ellipsis-v"></i>
                </button>
            </div>
            <div class="related-links collapse d-md-flex"
                id="related-links">
                <button id="add-courier-button" class="btn btn-primary responsive-button m-1 mt-md-0 mb-md-0 float-right">
                    {{ $__t('添加快递类型') }}
                </button>
            </div>
        </div>
    </div>
</div>

<hr class="my-2">

<div class="row collapse d-md-flex"
    id="table-filter-row">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
            </div>
            <input type="text"
                id="search"
                class="form-control"
                placeholder="{{ $__t('搜索') }}">
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="form-check custom-control custom-checkbox">
            <input class="form-check-input custom-control-input"
                type="checkbox"
                id="show-inactive">
            <label class="form-check-label custom-control-label"
                for="show-inactive">
                {{ $__t('显示已禁用') }}
            </label>
        </div>
    </div>
    <div class="col">
        <div class="float-right">
            <button id="clear-filter-button"
                class="btn btn-sm btn-outline-info"
                data-toggle="tooltip"
                title="{{ $__t('清除筛选') }}">
                <i class="fa-solid fa-filter-circle-xmark"></i>
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <table id="couriers-table"
            class="table table-striped table-hover table-resizable">
            <thead>
                <tr>
                    <th class="border-right d-print-none">
                        <a class="text-muted change-table-columns-visibility-button"
                            data-toggle="tooltip"
                            title="{{ $__t('表格选项') }}"
                            data-table-selector="#couriers-table"
                            href="#"><i class="fa-solid fa-eye"></i>
                        </a>
                    </th>
                    <th>{{ $__t('名称') }}</th>
                    <th>{{ $__t('代码') }}</th>
                    <th>{{ $__t('备注') }}</th>
                    <th>{{ $__t('状态') }}</th>
                    <th>{{ $__t('排序') }}</th>
                    <th>{{ $__t('操作') }}</th>
                </tr>
            </thead>
            <tbody class="d-none">
                <!-- 数据将通过API加载 -->
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('pageScripts')
<script>
    // 页面初始化后加载数据
    $(document).ready(function () {
        // 初始化DataTable
        var couriersTable = $('#couriers-table').DataTable({
            'order': [[1, 'asc']],
            'columnDefs': [
                { 'orderable': false, 'targets': 0 },
                { 'orderable': false, 'targets': 6 },
                { 'searchable': false, "targets": 0 },
                { 'searchable': false, "targets": 6 }
            ].concat($.fn.dataTable.defaults.columnDefs)
        });
        
        // 从API加载数据
        function loadCouriers() {
            fetch('/api/couriers')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 0) {
                        couriersTable.clear();
                        
                        data.data.forEach(courier => {
                            couriersTable.row.add([
                                '', // 表格选项列
                                courier.name,
                                courier.code,
                                courier.remark || '',
                                courier.is_active ? 
                                    '<span class="badge badge-success">' + '{{ $__t("启用") }}' + '</span>' : 
                                    '<span class="badge badge-danger">' + '{{ $__t("禁用") }}' + '</span>',
                                courier.sort_order,
                                `<div class="btn-group">
                                    <button class="btn btn-sm btn-info edit-courier-button" data-id="${courier.id}">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm ${courier.is_active ? 'btn-warning' : 'btn-success'} toggle-courier-button" data-id="${courier.id}" data-active="${courier.is_active ? 1 : 0}">
                                        <i class="fa-solid ${courier.is_active ? 'fa-times' : 'fa-check'}"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-courier-button" data-id="${courier.id}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>`
                            ]);
                        });
                        
                        couriersTable.draw();
                        $('#couriers-table tbody').removeClass('d-none');
                    } else {
                        toastr.error('{{ $__t("加载快递类型失败") }}: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('{{ $__t("加载快递类型失败") }}');
                });
        }
        
        // 初始加载数据
        loadCouriers();
        
        // 处理搜索
        $('#search').on('keyup', function() {
            couriersTable.search($(this).val()).draw();
        });
        
        // 清除筛选按钮
        $('#clear-filter-button').on('click', function() {
            $('#search').val('');
            $('#show-inactive').prop('checked', false);
            couriersTable.search('').draw();
        });
        
        // 显示/隐藏已禁用的快递类型
        $('#show-inactive').on('change', function() {
            if ($(this).is(':checked')) {
                couriersTable.column(4).search('').draw();
            } else {
                couriersTable.column(4).search('启用').draw();
            }
        });
        
        // 添加快递类型按钮
        $('#add-courier-button').on('click', function() {
            // TODO: 实现添加快递类型的功能
            toastr.info('{{ $__t("添加快递类型功能待实现") }}');
        });
        
        // 编辑按钮事件委托
        $('#couriers-table').on('click', '.edit-courier-button', function() {
            const courierId = $(this).data('id');
            // TODO: 实现编辑快递类型的功能
            toastr.info('{{ $__t("编辑快递类型功能待实现") }}: ' + courierId);
        });
        
        // 启用/禁用按钮事件委托
        $('#couriers-table').on('click', '.toggle-courier-button', function() {
            const courierId = $(this).data('id');
            const isActive = $(this).data('active') === 1;
            // TODO: 实现启用/禁用快递类型的功能
            toastr.info('{{ $__t("切换快递类型状态功能待实现") }}: ' + courierId + ', ' + (isActive ? '禁用' : '启用'));
        });
        
        // 删除按钮事件委托
        $('#couriers-table').on('click', '.delete-courier-button', function() {
            const courierId = $(this).data('id');
            // TODO: 实现删除快递类型的功能
            toastr.info('{{ $__t("删除快递类型功能待实现") }}: ' + courierId);
        });
    });
</script>
@endpush 
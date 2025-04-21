{{-- 
    响应式Bootstrap 4表格模板组件
    使用方法:
    @include('components.tabletemplate', [
        'id' => 'my-table', // 表格ID
        'headers' => ['列1', '列2', '列3'], // 表头数组
        'data' => $dataArray, // 数据数组
        'keys' => ['key1', 'key2', 'key3'], // 数据键名（与headers对应）
        'rowIdKey' => 'id', // 可选，用于生成tr的id，默认为'id'
        'stacked' => true, // 可选，在移动设备上使用堆叠布局，默认为true
    ])
--}}

<div class="table-responsive">
    <table id="{{ $id ?? 'responsive-table' }}" class="table table-striped table-hover table-resizable {{ isset($stacked) && $stacked ? 'table-responsive-stacked' : '' }}">
        <thead>
            <tr>
                @foreach($headers as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if(isset($data) && count($data) > 0)
                @foreach($data as $item)
                <tr @if(isset($rowIdKey) && isset($item[$rowIdKey])) id="row-{{ $item[$rowIdKey] }}" @endif>
                    @foreach($keys as $index => $key)
                    <td data-label="{{ $headers[$index] }}">
                        @if(isset($formatters) && isset($formatters[$key]))
                            {!! $formatters[$key]($item[$key], $item) !!}
                        @else
                            {{ $item[$key] }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center">{{ $__t('No data available') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- 表格下方显示的记录计数 --}}
@if(isset($data) && count($data) > 0)
<div class="row mt-1">
    <div class="col">
        <span class="text-muted small">
            {{ $__t('%s entries', count($data)) }}
        </span>
    </div>
</div>
@endif 
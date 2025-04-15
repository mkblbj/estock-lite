@if(empty($requirements))
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> 未找到需求文档，请检查项目配置或手动添加文档。
</div>
@else
<!-- 文档选择器 -->
<div class="btn-group mb-3 w-100">
    @foreach($requirements as $index => $doc)
    <button class="btn @if($index === 0) btn-primary @else btn-outline-primary @endif doc-selector" data-target="doc-{{ $index }}">
        {{ $doc['title'] }}
    </button>
    @endforeach
</div>

<!-- 文档内容预览 -->
<div class="markdown-docs">
    @foreach($requirements as $index => $doc)
    <div id="preview-doc-{{ $index }}" class="markdown-preview @if($index !== 0) d-none @endif">
        {!! $doc['html'] !!}
    </div>
    @endforeach
</div>
@endif 
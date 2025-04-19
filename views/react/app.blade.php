@extends('layout.default')

@section('title', 'React应用')

@section('viewJsFiles')
{{-- 禁用默认JS文件加载 --}}
@endsection

@section('content')
<div id="react-root"></div>
@endsection

@push('scripts')
<script src="{{ $U('/reactapp/dist/assets/index.js?v=', true) }}{{ $version }}" defer></script>
<link href="{{ $U('/reactapp/dist/assets/index.css?v=', true) }}{{ $version }}" rel="stylesheet">
@endpush 
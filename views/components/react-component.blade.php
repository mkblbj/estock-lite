@php
$componentId = $id ?? uniqid('react-');
$componentName = $component ?? 'DefaultComponent';
$componentProps = $props ?? [];
@endphp

<div 
  id="{{ $componentId }}" 
  class="react-component" 
  data-component="{{ $componentName }}" 
  data-props="{{ json_encode($componentProps) }}"
></div>

@push('scripts')
<script src="{{ $U('/reactapp/dist/assets/index.js?v=', true) }}{{ $version }}" defer></script>
<link href="{{ $U('/reactapp/dist/assets/index.css?v=', true) }}{{ $version }}" rel="stylesheet">
@endpush 
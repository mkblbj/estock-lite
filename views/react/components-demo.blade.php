@extends('layout.default')

@section('content')
<div class="container py-3">
    <h1 class="mb-4">React 组件演示</h1>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    按钮组件
                </div>
                <div class="card-body">
                    @include('components.react-component', [
                        'id' => 'button-demo',
                        'component' => 'Button',
                        'props' => ['children' => '默认按钮']
                    ])
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    卡片组件
                </div>
                <div class="card-body">
                    @php
                    $cardProps = [
                        'children' => [
                            [
                                'type' => 'Header',
                                'props' => [
                                    'children' => [
                                        [
                                            'type' => 'Title',
                                            'props' => ['children' => '卡片标题']
                                        ],
                                        [
                                            'type' => 'Description',
                                            'props' => ['children' => '这是卡片的描述文本']
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'type' => 'Content',
                                'props' => ['children' => '这是卡片的内容区域']
                            ]
                        ]
                    ];
                    @endphp
                    
                    @include('components.react-component', [
                        'id' => 'card-demo',
                        'component' => 'CardDemo',
                        'props' => $cardProps
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
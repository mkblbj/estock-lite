@extends('layout.default')

@section('title', '项目流程进展跟踪')

@section('viewJsFiles')
<script src="{{ $U('/node_modules/simplemde/dist/simplemde.min.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/node_modules/chart.js/dist/Chart.min.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/viewjs/projectprogress.js?v=', true) }}{{ $version }}"></script>
@stop

@section('viewCssFiles')
<link href="{{ $U('/node_modules/simplemde/dist/simplemde.min.css?v=', true) }}{{ $version }}" rel="stylesheet">
<style>
/* Git提交记录样式 */
.graph-cell {
    width: 100px;
}
.graph-container {
    position: relative;
}
.graph-line {
    position: relative;
    width: 20px;
    height: 30px;
}
.graph-line:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    width: 2px;
    background-color: #4e73df;
    transform: translateX(-50%);
}
.graph-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 10px;
    height: 10px;
    background-color: #4e73df;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: 1;
}
.first-dot {
    background-color: #e74a3b;
}
.branch-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    background-color: #4e73df;
}
.tag-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    background-color: #f6c23e;
}
.commit-row {
    cursor: pointer;
    transition: background-color 0.2s;
}
.commit-row:hover {
    background-color: rgba(78, 115, 223, 0.05);
}
.commit-row.active {
    background-color: rgba(78, 115, 223, 0.1);
}
.commit-subject-cell {
    overflow: hidden;
}
.commit-hash {
    color: #858796;
}
.avatar-placeholder {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #e3e6f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #5a5c69;
}
.file-changes {
    margin-left: 20px;
}
.file-item {
    margin: 2px 0;
    font-family: monospace;
    font-size: 0.85rem;
}
.file-path {
    margin-left: 5px;
    word-break: break-all;
}

/* 原有样式 */
.git-commit {
    border-left: 4px solid #4e73df;
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f8f9fc;
    border-radius: 0 4px 4px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.git-commit .commit-header {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 5px;
    margin-bottom: 5px;
}
.git-commit .commit-subject {
    font-weight: bold;
}
.git-commit .commit-details {
    color: #6c757d;
    font-size: 0.85rem;
}
.git-commit .file-list {
    margin-top: 5px;
    font-family: monospace;
    font-size: 0.9rem;
}
.git-commit .file-list .file-item {
    padding: 2px 0;
}
.file-item .status-A { color: #28a745; }
.file-item .status-M { color: #ffc107; }
.file-item .status-D { color: #dc3545; }
.file-item .status-R { color: #17a2b8; }

.progress-tasks .task-item {
    background-color: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    position: relative;
}
.progress-tasks .task-item .task-name {
    font-weight: 600;
    margin-bottom: 5px;
}
.progress-tasks .task-item .task-status {
    position: absolute;
    right: 10px;
    top: 10px;
}
.progress-tasks .task-item .progress {
    height: 10px;
}
.status-completed { color: #28a745; }
.status-in_progress { color: #ffc107; }
.status-pending { color: #6c757d; }

.editor-toolbar, .CodeMirror {
    border-color: #e3e6f0;
}
.editor-toolbar a {
    color: #5a5c69 !important;
}
.CodeMirror {
    height: 400px;
}

/* 分页样式 */
.pagination-container {
    border-top: 1px solid #e3e6f0;
    background-color: #f8f9fc;
}
.pagination {
    margin-left: auto;
}
.pagination .page-link {
    color: #4e73df;
    padding: 0.5rem 0.75rem;
}
.pagination .page-item.active .page-link {
    background-color: #4e73df;
    border-color: #4e73df;
    color: white;
}
.pagination .page-item.disabled .page-link {
    color: #858796;
}
.per-page-selector {
    margin-left: 10px;
    display: inline-block;
}
</style>
@stop

@section('content')
<div class="row">
    <div class="col">
        <h1 class="page-header">项目流程进展跟踪</h1>
    </div>
</div>

@if(isset($successMessage))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ $successMessage }}
    <button type="button" class="close" data-dismiss="alert">
        <span>&times;</span>
    </button>
</div>
@endif

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#git-commits">Git提交记录</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#requirements">需求文档</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#progress">项目进度</a>
    </li>
</ul>

<div class="tab-content">
    <!-- Git提交记录选项卡 -->
    <div class="tab-pane fade show active" id="git-commits">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa fa-code-branch"></i> 提交历史记录
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" id="refresh-git-history">
                                <i class="fa fa-sync"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if(empty($gitCommits))
                            <div class="alert alert-info m-3">没有找到Git提交记录</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="15%" class="pl-3">Branch</th>
                                            <th width="45%">Commit</th>
                                            <th width="20%">Time</th>
                                            <th width="20%">Author</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gitCommits as $index => $commit)
                                            <tr class="commit-row" data-commit-hash="{{ $commit['hash'] }}">
                                                <td class="graph-cell pl-3">
                                                    <div class="graph-container d-flex">
                                                        <div class="graph-line">
                                                            @if($index == 0)
                                                                <span class="graph-dot first-dot"></span>
                                                            @else
                                                                <span class="graph-dot"></span>
                                                            @endif
                                                        </div>
                                                        <div class="ml-2">
                                                            @if(!empty($commit['branches']))
                                                                @foreach($commit['branches'] as $branch)
                                                                    <span class="badge badge-primary branch-badge">{{ $branch }}</span>
                                                                @endforeach
                                                            @endif
                                                            @if(!empty($commit['tags']))
                                                                @foreach($commit['tags'] as $tag)
                                                                    <span class="badge badge-warning tag-badge">{{ $tag }}</span>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="commit-subject-cell">
                                                    <div class="commit-subject font-weight-bold">{{ $commit['subject'] }}</div>
                                                    <div class="commit-hash text-muted small">{{ $commit['short_hash'] }}</div>
                                                </td>
                                                <td class="commit-date-cell">{{ $commit['date'] }}</td>
                                                <td class="commit-author-cell">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-placeholder mr-2">
                                                            <i class="fa fa-user"></i>
                                                        </div>
                                                        <div>{{ $commit['author'] }}</div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="commit-details-row d-none" id="details-{{ $commit['short_hash'] }}">
                                                <td colspan="4" class="p-0">
                                                    <div class="commit-details-container p-3 bg-light">
                                                        <div class="file-changes mb-2">
                                                            <div class="file-changes-title font-weight-bold mb-1">
                                                                <i class="fa fa-file-alt"></i> 文件变更 ({{ count($commit['files']) }})
                                                            </div>
                                                            <div class="file-list">
                                                                @foreach($commit['files'] as $file)
                                                                    <div class="file-item">
                                                                        @if($file['status'] == 'A')
                                                                            <span class="badge badge-success">添加</span>
                                                                        @elseif($file['status'] == 'M')
                                                                            <span class="badge badge-warning">修改</span>
                                                                        @elseif($file['status'] == 'D')
                                                                            <span class="badge badge-danger">删除</span>
                                                                        @elseif($file['status'] == 'R')
                                                                            <span class="badge badge-info">重命名</span>
                                                                        @else
                                                                            <span class="badge badge-secondary">{{ $file['status'] }}</span>
                                                                        @endif
                                                                        <span class="file-path">{{ $file['path'] }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- 分页控件 -->
                            @if($pagination['total_pages'] > 1)
                            <div class="pagination-container p-3 d-flex justify-content-between align-items-center">
                                <div class="pagination-info d-flex align-items-center">
                                    显示 {{ count($gitCommits) }} 条记录，共 {{ $pagination['total'] }} 条
                                    <div class="per-page-selector">
                                        <select id="per-page-select" class="custom-select custom-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                            <option value="10" {{ $pagination['per_page'] == 10 ? 'selected' : '' }}>10条/页</option>
                                            <option value="20" {{ $pagination['per_page'] == 20 ? 'selected' : '' }}>20条/页</option>
                                            <option value="50" {{ $pagination['per_page'] == 50 ? 'selected' : '' }}>50条/页</option>
                                            <option value="100" {{ $pagination['per_page'] == 100 ? 'selected' : '' }}>100条/页</option>
                                        </select>
                                    </div>
                                </div>
                                <nav aria-label="提交历史分页">
                                    <ul class="pagination mb-0">
                                        <!-- 上一页按钮 -->
                                        <li class="page-item {{ $pagination['page'] <= 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $U('/projectprogress?page=' . ($pagination['page'] - 1) . '&per_page=' . $pagination['per_page']) }}" aria-label="上一页">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <!-- 页码 -->
                                        @php
                                            $startPage = max(1, $pagination['page'] - 2);
                                            $endPage = min($pagination['total_pages'], $startPage + 4);
                                            if ($endPage - $startPage < 4 && $pagination['total_pages'] > 4) {
                                                $startPage = max(1, $endPage - 4);
                                            }
                                        @endphp

                                        @if($startPage > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $U('/projectprogress?page=1&per_page=' . $pagination['per_page']) }}">1</a>
                                            </li>
                                            @if($startPage > 2)
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#">...</a>
                                                </li>
                                            @endif
                                        @endif

                                        @for($i = $startPage; $i <= $endPage; $i++)
                                            <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $U('/projectprogress?page=' . $i . '&per_page=' . $pagination['per_page']) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        @if($endPage < $pagination['total_pages'])
                                            @if($endPage < $pagination['total_pages'] - 1)
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#">...</a>
                                                </li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $U('/projectprogress?page=' . $pagination['total_pages'] . '&per_page=' . $pagination['per_page']) }}">{{ $pagination['total_pages'] }}</a>
                                            </li>
                                        @endif

                                        <!-- 下一页按钮 -->
                                        <li class="page-item {{ $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $U('/projectprogress?page=' . ($pagination['page'] + 1) . '&per_page=' . $pagination['per_page']) }}" aria-label="下一页">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 需求文档选项卡 -->
    <div class="tab-pane fade" id="requirements">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-file-alt"></i> 需求文档
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ $U('/projectprogress/save-requirements') }}">
                            <input type="hidden" name="page" value="{{ $pagination['page'] ?? 1 }}">
                            <input type="hidden" name="per_page" value="{{ $pagination['per_page'] ?? 20 }}">
                            <div class="form-group">
                                <textarea id="markdown-editor" name="markdown_content">{{ $requirements }}</textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> 保存文档</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 项目进度选项卡 -->
    <div class="tab-pane fade" id="progress">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-tasks"></i> 项目任务进度
                    </div>
                    <div class="card-body">
                        <div class="progress-tasks">
                            @foreach($progressTasks as $task)
                                <div class="task-item" data-task-id="{{ $task['id'] }}">
                                    <div class="task-header">
                                        <div class="task-name">{{ $task['name'] }}</div>
                                        <div class="task-status status-{{ $task['status'] }}">
                                            @if($task['status'] == 'completed')
                                                <i class="fa fa-check-circle"></i> 已完成
                                            @elseif($task['status'] == 'in_progress')
                                                <i class="fa fa-spinner fa-spin"></i> 进行中
                                            @else
                                                <i class="fa fa-clock"></i> 待处理
                                            @endif
                                        </div>
                                    </div>
                                    <div class="progress mt-2">
                                        <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $task['percentage'] }}%;" 
                                            aria-valuenow="{{ $task['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $task['percentage'] }}%
                                        </div>
                                    </div>
                                    <div class="task-actions mt-2">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary update-progress" data-task-id="{{ $task['id'] }}" data-status="{{ $task['status'] }}" data-percentage="{{ $task['percentage'] }}">
                                                <i class="fa fa-edit"></i> 更新进度
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-chart-pie"></i> 项目整体进度
                    </div>
                    <div class="card-body">
                        <canvas id="progress-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 更新进度的模态框 -->
<div class="modal fade" id="update-progress-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">更新任务进度</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="update-progress-form">
                    <input type="hidden" id="update-task-id" name="task_id">
                    
                    <div class="form-group">
                        <label for="update-status">任务状态</label>
                        <select class="form-control" id="update-status" name="status">
                            <option value="pending">待处理</option>
                            <option value="in_progress">进行中</option>
                            <option value="completed">已完成</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="update-percentage">完成百分比</label>
                        <input type="range" class="custom-range" id="update-percentage" name="percentage" min="0" max="100" step="5">
                        <div class="text-center" id="percentage-value">0%</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="save-progress">保存</button>
            </div>
        </div>
    </div>
</div>
@stop

<script>
// 处理每页显示记录数变更
function changePerPage(perPage) {
    var currentUrl = new URL(window.location.href);
    var params = new URLSearchParams(currentUrl.search);
    
    // 设置每页记录数并重置页码为1
    params.set('per_page', perPage);
    params.set('page', 1);
    
    // 构建新URL并跳转
    currentUrl.search = params.toString();
    window.location.href = currentUrl.toString();
}
</script> 
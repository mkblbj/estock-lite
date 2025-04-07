@extends('layout.default')

@section('title', '项目流程进展跟踪')

@section('viewJsFiles')
<script src="{{ $U('/node_modules/simplemde/dist/simplemde.min.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/viewjs/projectprogress.js?v=', true) }}{{ $version }}"></script>
@stop

@push('pageStyles')
<style>
/* 项目选择样式 */
.projects-container {
    margin-bottom: 20px;
    overflow-x: auto; /* 添加水平滚动条 */
    width: 100%;
}
.projects-table {
    width: 100%;
    min-width: 768px; /* 设置最小宽度，确保在小屏幕上也能正确显示 */
    max-width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
    table-layout: fixed;
    margin: 0 auto;
}
.project-row {
    transition: all 0.3s;
    position: relative;
}
.project-row:hover {
    background-color: rgba(78, 115, 223, 0.05);
}
.project-row.active {
    background-color: rgba(78, 115, 223, 0.1);
}
.project-row td {
    padding: 10px 12px;
    border-top: 1px solid #e3e6f0;
    border-bottom: 1px solid #e3e6f0;
    vertical-align: middle;
}
.project-row td:first-child {
    border-left: 1px solid #e3e6f0;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
}
.project-row td:last-child {
    border-right: 1px solid #e3e6f0;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
}
.project-row.active td {
    border-color: #4e73df;
}
/* 删除项目图标样式 */
.project-icon {
    display: none;
}
.project-name-btn {
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.2rem;
    border: 1px solid #4e73df;
    background-color: #4e73df;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    width: 92%;
    display: inline-block;
    text-align: center;
}
.project-name-btn:hover {
    background-color: #2e59d9;
    border-color: #2e59d9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.project-name-btn:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}
.project-name-btn.active {
    background-color: #2e59d9;
    border-color: #2e59d9;
}
.branch-badge {
    display: inline-block;
    padding: 3px 8px;
    background-color: #e3e6f0;
    color: #5a5c69;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 92%;
    border: none;
    text-align: center;
}
.branch-badge i {
    color: #4e73df;
    margin-right: 5px;
}
.commit-info {
    font-size: 0.85rem;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.3;
    display: block;
    word-break: break-all;
}
.project-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    flex-wrap: wrap;
}
.stat-item {
    display: flex;
    align-items: center;
    color: #858796;
    font-size: 0.8rem;
    white-space: nowrap;
    padding: 0 3px;
    margin-bottom: 2px;
}
.stat-item i {
    margin-right: 5px;
    color: #4e73df;
}
.info-badge {
    background-color: #f1f1f1;
    padding: 3px 6px;
    border-radius: 12px;
    display: inline-block;
    text-align: center;
    min-width: 60px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}
.project-details {
    padding: 15px;
    background-color: #f8f9fc;
    border-radius: 4px;
    margin-top: 8px;
    border: 1px solid #e3e6f0;
}
.project-commit {
    font-size: 0.85rem;
    padding: 8px 10px;
    background-color: white;
    border-radius: 4px;
    border-left: 3px solid #4e73df;
    margin: 10px 0;
}
.details-toggle {
    transition: all 0.2s;
}
.details-toggle i {
    transition: transform 0.3s;
}
.details-toggle.expanded i {
    transform: rotate(180deg);
}

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
.tag-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    background-color: #f6c23e;
    margin-right: 2px;
    white-space: nowrap;
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

/* 自定义分支颜色 */
.badge-indigo {
    background-color: #6610f2;
    color: #fff;
}
.badge-purple {
    background-color: #6f42c1;
    color: #fff;
}
.badge-pink {
    background-color: #e83e8c;
    color: #fff;
}
.badge-orange {
    background-color: #fd7e14;
    color: #fff;
}
.badge-teal {
    background-color: #20c997;
    color: #fff;
}
.badge-cyan {
    background-color: #17a2b8;
    color: #fff;
}
.badge-gray {
    background-color: #6c757d;
    color: #fff;
}
.badge-indigo-light {
    background-color: #c8a9fa;
    color: #212529;
}
.badge-purple-light {
    background-color: #d2c0ed;
    color: #212529;
}
.badge-pink-light {
    background-color: #f2b8d1;
    color: #212529;
}
.badge-orange-light {
    background-color: #ffd2a8;
    color: #212529;
}
.badge-teal-light {
    background-color: #a9e9d9;
    color: #212529;
}
.badge-cyan-light {
    background-color: #a6e7ef;
    color: #212529;
}
.badge-dark {
    background-color:rgb(76, 81, 86);
    color: #fff;
}

/* Markdown预览样式 */
.markdown-preview {
    background-color: #fff;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    overflow-x: auto; /* 添加水平滚动条 */
}
.markdown-body {
    /* 使用github-markdown样式 */
}

.card-header {
    padding: .75rem 1rem;
}

/* 响应式调整 */
@media (max-width: 992px) {
    .project-name-btn {
        font-size: 0.8rem;
        padding: 0.2rem 0.4rem;
        width: 100%;
    }

    .branch-badge {
        padding: 2px 6px;
        font-size: 0.7rem;
    }

    .commit-info {
        font-size: 0.8rem;
    }

    .info-badge {
        padding: 2px 4px;
        min-width: 50px;
        font-size: 0.75rem;
    }

    .stat-item {
        padding: 0 2px;
    }
}

/* 任务项目样式开始 */
/* 项目进度样式 */
.progress-tasks {
    overflow-y: visible;
    padding-right: 5px;
}

/* 项目标题样式 */
.page-header h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2e3951;
    margin-bottom: 1.5rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* 任务项样式 */
.task-item {
    border-left: 4px solid #eee;
    padding: 15px !important;
    margin-bottom: 15px !important;
    background-color: #f8f9fc;
    border-radius: 0 4px 4px 0;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    position: relative;
}

/* 任务分割线 */
.task-item:not(:last-child)::after {
    display: none;
}

.task-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* 优先级颜色 */
.task-item[data-priority="1"] {
    border-left-color: #17a2b8;
}

.task-item[data-priority="2"] {
    border-left-color: #ffc107;
}

.task-item[data-priority="3"] {
    border-left-color: #dc3545;
}

/* 优先级标签样式 */
.priority-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-right: 10px;
    display: inline-block;
}

.priority-1 {
    background-color: #17a2b8;
    color: white;
}

.priority-2 {
    background-color: #ffc107;
    color: #212529;
}

.priority-3 {
    background-color: #dc3545;
    color: white;
}

/* 任务元数据样式 */
.task-meta {
    padding: 8px 0 !important;
    margin: 0 !important;
    border-top: none !important;
    background-color: transparent !important;
}

.task-meta span {
    margin-right: 20px !important;
    display: inline-block;
    padding: 3px 0;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    padding: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.task-name {
    font-size: 1.1rem;
    color: #2e3951;
    font-weight: 600;
}

.task-status {
    font-size: 0.8rem;
    padding: 3px 8px;
    border-radius: 12px;
    background-color: #eee;
    color: #555;
}

.status-pending {
    background-color: #e9ecef;
    color: #495057;
}

.status-in_progress {
    background-color: #cce5ff;
    color: #0062cc;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

/* 任务操作按钮样式 */
.task-actions {
    padding: 8px 0 0 0 !important;
    margin: 8px 0 0 0 !important;
    background-color: transparent !important;
    border-top: 1px solid rgba(0,0,0,0.05);
    display: flex;
    gap: 8px;
}

.task-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
}

/* 进度条样式 */
.progress {
    height: 8px !important;
    margin: 10px 0 !important;
    background-color: #e9ecef;
    border-radius: 4px !important;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.progress-bar {
    transition: width 0.3s ease;
    font-size: 0;  /* 隐藏文字 */
    line-height: 0;
}

/* 进度条颜色 */
.progress-bar[aria-valuenow="0"] {
    background-color: #e9ecef;
    color: #495057;
    text-shadow: none;
}

.progress-bar[aria-valuenow^="1"],
.progress-bar[aria-valuenow^="2"],
.progress-bar[aria-valuenow^="3"],
.progress-bar[aria-valuenow^="4"] {
    background-color: #ffc107;
    color: #212529;
    text-shadow: 0 1px 1px rgba(255,255,255,0.5);
}

.progress-bar[aria-valuenow^="5"],
.progress-bar[aria-valuenow^="6"],
.progress-bar[aria-valuenow^="7"],
.progress-bar[aria-valuenow^="8"],
.progress-bar[aria-valuenow^="9"] {
    background-color: #0d6efd;
    color: white;
}

.progress-bar[aria-valuenow="100"] {
    background-color: #198754;
    color: white;
}

.deadline-badge, .assigned-badge {
    color: #6c757d;
}
/* 任务项目样式结束 */
</style>
@endpush

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

<!-- 项目选择区域 -->
<div class="projects-container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
            <div>
                <i class="fa fa-folder"></i> <span style="font-size: 0.95rem;">选择项目</span>
                @if(!empty($selectedProject))
                <span class="badge badge-pill badge-primary ml-2">{{ $selectedProject }}</span>
                @endif
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#projectsCollapse" aria-expanded="false" aria-controls="projectsCollapse">
                    <i class="fa fa-chevron-down"></i> 展开/收起
                </button>
            </div>
        </div>
        <div class="collapse show" id="projectsCollapse">
            <div class="card-body p-2">
                <table class="projects-table">
                    <tbody>
                        @foreach($allProjects as $projectKey => $project)
                        <tr class="project-row {{ $selectedProject == $projectKey ? 'active' : '' }}" data-project="{{ $projectKey }}">
                            <td width="16%">
                                <button class="btn btn-sm btn-primary project-name-btn {{ $selectedProject == $projectKey ? 'active' : '' }}" onclick="selectProject('{{ $projectKey }}')">
                                    {{ $project['name'] }}
                                </button>
                            </td>
                            <td width="14%">
                                <span class="badge badge-{{ $project['branch'] == 'master' || $project['branch'] == 'main' ? 'primary' : ($project['branch'] == 'develop' || $project['branch'] == 'dev' ? 'info' : 'success') }} branch-badge">
                                    <i class="fa fa-code-branch"></i> {{ $project['branch'] }}
                                </span>
                            </td>
                            <td width="38%">
                                <div class="commit-info text-truncate" title="{{ $project['last_commit'] }}" style="max-width: 100%;">
                                    {{ \Illuminate\Support\Str::limit($project['last_commit'], 80) }}
                                </div>
                            </td>
                            <td width="24%">
                                <div class="project-stats">
                                    <div class="stat-item">
                                        <i class="fa fa-clock"></i> <span class="info-badge">{{ $project['last_commit_date'] }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fa fa-history"></i> <span class="info-badge">{{ $project['commits_count'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td width="8%" class="text-center">
                                <button class="btn btn-sm btn-outline-primary details-toggle" type="button" data-toggle="collapse" data-target="#project-details-{{ $projectKey }}" aria-expanded="false">
                                    <i class="fa fa-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row">
                            <td colspan="6" class="p-0">
                                <div class="collapse" id="project-details-{{ $projectKey }}">
                                    <div class="project-details">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5>最新提交</h5>
                                                <div class="project-commit overflow-hidden">
                                                    {{ \Illuminate\Support\Str::limit($project['last_commit'], 150) }}
                                                </div>
                                                <div class="mt-3">
                                                    <h5>作者信息</h5>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-placeholder mr-2">
                                                            <i class="fa fa-user"></i>
                                                        </div>
                                                        <div>{{ $project['last_commit_author'] ?? '未知' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>项目统计</h5>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        总提交数
                                                        <span class="badge badge-primary badge-pill">{{ $project['commits_count'] }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        最后活动
                                                        <span class="badge badge-primary badge-pill">{{ $project['last_commit_date'] }}</span>
                                                    </li>
                                                </ul>
                                                <div class="mt-3 text-right">
                                                    <button class="btn btn-sm btn-primary" onclick="selectProject('{{ $projectKey }}')">
                                                        <i class="fa fa-eye"></i> 查看此项目
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
                            <i class="fa fa-code-branch"></i> 提交历史记录({{ $selectedProject }})&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<span class="text-muted" style="font-size: 1rem;">点击可查看修改详情</span>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" id="refresh-git-history" onclick="refreshGitHistory()">
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
                                                                    <span class="badge badge-{{ $branch['color'] }} branch-badge">{{ $branch['name'] }}</span>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa fa-file-alt"></i> 需求文档 <span class="badge badge-pill badge-primary ml-2">{{ $selectedProject }}</span>
                        </div>
                        <div class="btn-group">
                            @foreach($requirements as $key => $doc)
                                <button class="btn btn-sm {{ $loop->first ? 'btn-primary' : 'btn-outline-primary' }} doc-selector" data-target="{{ $key }}">
                                    {{ $doc['title'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-body">
                        @if(empty($requirements))
                            <div class="alert alert-info">
                                没有找到需求文档。请检查项目根目录中是否有README.md文件，或在docs目录中添加包含"requirement"的Markdown文档。
                            </div>
                        @else
                            @foreach($requirements as $key => $doc)
                                <div class="markdown-preview {{ $loop->first ? '' : 'd-none' }}" id="preview-{{ $key }}">
                                    <div class="markdown-body">
                                        {!! \Parsedown::instance()->text(e($doc['content'])) !!}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 项目进度选项卡 -->
    <div class="tab-pane fade" id="progress">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa fa-tasks"></i> 项目任务进度
                        </div>
                        <div>
                            <button class="btn btn-sm btn-info" id="view-project-history">
                                <i class="fa fa-history"></i> 任务历史记录
                            </button>
                            <button class="btn btn-sm btn-success" id="add-task-btn">
                                <i class="fa fa-plus"></i> 添加任务
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="progress-tasks">
                            @if(empty($progressTasks))
                                <div class="alert alert-info">
                                    暂无任务记录，请点击"添加任务"按钮创建任务。
                                </div>
                            @else
                                @foreach($progressTasks as $task)
                                    <div class="task-item {{ isset($task['overdue']) && $task['overdue'] ? 'task-overdue' : '' }}" 
                                        data-task-id="{{ $task['id'] }}"
                                        data-priority="{{ $task['priority'] ?? 0 }}"
                                        data-status="{{ $task['status'] }}"
                                        data-percentage="{{ $task['percentage'] }}"
                                        data-assigned-to="{{ $task['assigned_to'] ?? '' }}"
                                        data-deadline="{{ $task['deadline'] ?? '' }}">
                                        <div class="task-header d-flex justify-content-between">
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
                                        @if(!empty($task['description']))
                                            <div class="task-description text-muted small mt-1">
                                                {{ $task['description'] }}
                                            </div>
                                        @endif
                                        <div class="task-meta d-flex justify-content-start align-items-center small text-muted">
                                            <div>
                                                @if(isset($task['priority']) && $task['priority'] > 0)
                                                    <span class="priority-badge priority-{{ $task['priority'] }}">
                                                        <i class="fa fa-flag"></i> 
                                                        @if($task['priority'] == 1)
                                                            重要
                                                        @elseif($task['priority'] == 2)
                                                            紧急
                                                        @elseif($task['priority'] == 3)
                                                            关键
                                                        @endif
                                                    </span>
                                                @endif
                                                @if(isset($task['deadline']) && !empty($task['deadline']))
                                                    <span class="deadline-badge">
                                                        <i class="fa fa-calendar"></i> {{ $task['deadline'] }}
                                                    </span>
                                                @endif
                                                @if(isset($task['assigned_to']) && !empty($task['assigned_to']))
                                                    <span class="assigned-badge">
                                                        <i class="fa fa-user"></i> {{ $task['assigned_to'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="progress mt-2">
                                            <div class="progress-bar" 
                                                role="progressbar" style="width: {{ $task['percentage'] }}%;" 
                                                aria-valuenow="{{ $task['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $task['percentage'] }}%
                                            </div>
                                        </div>
                                        <!-- 任务操作按钮 -->
                                        <div class="task-actions">
                                            <button type="button" class="btn btn-outline-primary edit-task" data-task-id="{{ $task['id'] }}" 
                                                data-name="{{ $task['name'] }}"
                                                data-description="{{ $task['description'] }}"
                                                data-status="{{ $task['status'] }}"
                                                data-percentage="{{ $task['percentage'] }}"
                                                data-priority="{{ $task['priority'] }}"
                                                data-deadline="{{ $task['deadline'] }}"
                                                data-assigned-to="{{ $task['assigned_to'] }}">
                                                <i class="fa fa-edit"></i> 编辑
                                            </button>
                                            <button type="button" class="btn btn-outline-info update-progress" data-task-id="{{ $task['id'] }}" 
                                                data-status="{{ $task['status'] }}"
                                                data-percentage="{{ $task['percentage'] }}">
                                                <i class="fa fa-chart-line"></i> 更新进度
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-task" data-task-id="{{ $task['id'] }}">
                                                <i class="fa fa-trash"></i> 删除
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
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
                        <div class="text-center mb-4">
                            <div class="progress-circle mx-auto position-relative" style="width: 200px; height: 200px;">
                                <canvas id="progress-chart" width="200" height="200" data-completed-percentage="{{ $taskStatistics['total_percentage'] ?? 0 }}"></canvas>
                                <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <h2 class="mb-0">{{ $taskStatistics['total_percentage'] ?? 0 }}%</h2>
                                    <div class="text-muted">完成度</div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 mb-0">{{ $taskStatistics['completed'] ?? 0 }}</div>
                                <div class="small text-muted">已完成</div>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0">{{ $taskStatistics['in_progress'] ?? 0 }}</div>
                                <div class="small text-muted">进行中</div>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0">{{ $taskStatistics['pending'] ?? 0 }}</div>
                                <div class="small text-muted">待处理</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 即将到期的任务 -->
                @if(is_object($upcomingTasks) && $upcomingTasks->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-warning text-white">
                        <i class="fa fa-calendar-alt"></i> 即将到期的任务
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($upcomingTasks as $task)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $task->name }}</strong>
                                        <div class="small text-muted">
                                            <i class="fa fa-calendar"></i> {{ $task->deadline }}
                                        </div>
                                    </div>
                                    <span class="badge badge-warning">
                                        @php
                                        $deadline = new DateTime($task->deadline);
                                        $today = new DateTime('today');
                                        $diff = $deadline->diff($today)->days;
                                        echo $diff == 0 ? '今天' : '还剩'.$diff.'天';
                                        @endphp
                                    </span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                
                <!-- 已过期的任务 -->
                @if(is_object($overdueTasks) && $overdueTasks->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-danger text-white">
                        <i class="fa fa-exclamation-triangle"></i> 已过期的任务
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($overdueTasks as $task)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $task->name }}</strong>
                                        <div class="small text-muted">
                                            <i class="fa fa-calendar"></i> {{ $task->deadline }}
                                        </div>
                                    </div>
                                    <span class="badge badge-danger">
                                        @php
                                        $deadline = new DateTime($task->deadline);
                                        $today = new DateTime('today');
                                        $diff = $today->diff($deadline)->days;
                                        echo '已超期'.$diff.'天';
                                        @endphp
                                    </span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 任务管理的模态框 -->
<div class="modal fade" id="task-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="task-modal-title">管理任务</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="task-form">
                    <input type="hidden" id="task-id" name="task_id" value="0">
                    <input type="hidden" name="project" value="{{ $selectedProject }}">
                    
                    <div class="form-group">
                        <label for="task-name">任务名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="task-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-description">任务描述</label>
                        <textarea class="form-control" id="task-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="task-status">任务状态</label>
                            <select class="form-control" id="task-status" name="status">
                                <option value="pending">待处理</option>
                                <option value="in_progress">进行中</option>
                                <option value="completed">已完成</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="task-priority">优先级</label>
                            <select class="form-control" id="task-priority" name="priority">
                                <option value="0">普通</option>
                                <option value="1">重要</option>
                                <option value="2">紧急</option>
                                <option value="3">关键</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="task-deadline">截止日期</label>
                            <input type="date" class="form-control" id="task-deadline" name="deadline">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="task-assigned-to">指派给</label>
                            <input type="text" class="form-control" id="task-assigned-to" name="assigned_to">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-percentage">完成百分比: <span id="percentage-value">0%</span></label>
                        <input type="range" class="custom-range" id="task-percentage" name="percentage" min="0" max="100" step="5" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="save-task">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 任务历史记录模态框 -->
<div class="modal fade" id="history-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">任务历史记录</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 id="history-task-name" class="mb-3"></h6>
                <div id="history-timeline" class="timeline">
                    <!-- 历史记录将通过AJAX加载 -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">加载中...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 更新进度的模态框（保留向后兼容） -->
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
                    <input type="hidden" id="update-task-name" name="name">
                    <input type="hidden" name="project" value="{{ $selectedProject }}">
                    
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
                        <div class="text-center" id="update-percentage-value">0%</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="update-priority">优先级</label>
                        <select class="form-control" id="update-priority" name="priority">
                            <option value="0">普通</option>
                            <option value="1">重要</option>
                            <option value="2">紧急</option>
                            <option value="3">关键</option>
                        </select>
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
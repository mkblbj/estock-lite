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
    <div class="task-meta d-flex justify-content-between small text-muted mt-1">
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
                <span class="mr-2">
                    <i class="fa fa-calendar"></i> {{ $task['deadline'] }}
                </span>
            @endif
            @if(isset($task['assigned_to']) && !empty($task['assigned_to']))
                <span>
                    <i class="fa fa-user"></i> {{ $task['assigned_to'] }}
                </span>
            @endif
        </div>
    </div>
    <div class="task-progress mt-2">
        <div class="progress">
            <div class="progress-bar" 
                role="progressbar"
                style="width: {{ $task['percentage'] }}%"
                aria-valuenow="{{ $task['percentage'] }}"
                aria-valuemin="0"
                aria-valuemax="100">{{ $task['percentage'] }}%</div>
        </div>
    </div>
    <!-- 任务操作按钮 -->
    <div class="task-actions">
        <button type="button" class="btn btn-outline-primary edit-task" data-task-id="{{ $task['id'] }}" 
            data-name="{{ $task['name'] }}"
            data-description="{{ $task['description'] }}"
            data-status="{{ $task['status'] }}"
            data-percentage="{{ $task['percentage'] }}"
            data-priority="{{ $task['priority'] ?? 0 }}"
            data-deadline="{{ $task['deadline'] ?? '' }}"
            data-assigned-to="{{ $task['assigned_to'] ?? '' }}">
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
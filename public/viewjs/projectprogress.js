/**
 * 项目流程进展跟踪页面的JavaScript功能
 */
$(document).ready(function() {
    // 图表相关代码延迟加载，确保 Chart.js 已经加载完成
    setTimeout(function() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js 库未能正确加载，项目进度图表功能将不可用。');
        } else {
            console.log('Chart.js 已加载，可以初始化图表。');
            
            // 如果当前在进度标签页，立即初始化图表
            if ($('#progress').hasClass('active')) {
                initProgressChart();
            }
        }
    }, 500);

    // 需求文档切换功能
    $('.doc-selector').on('click', function() {
        var targetId = $(this).data('target');
        
        // 更新按钮状态
        $('.doc-selector').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        
        // 切换显示对应的文档
        $('.markdown-preview').addClass('d-none');
        $('#preview-' + targetId).removeClass('d-none');
    });

    // 项目详情展开/折叠功能
    $('.details-toggle').on('click', function() {
        var $this = $(this);
        
        // 切换展开状态类
        $this.toggleClass('expanded');
        
        // 获取目标折叠区域
        var targetId = $this.data('target');
        
        // 延迟一点点以配合Bootstrap的collapse效果
        setTimeout(function() {
            // 检查目标区域是否展开
            var isExpanded = $(targetId).hasClass('show');
            
            // 如果展开，添加expanded类；否则移除
            if (isExpanded) {
                $this.addClass('expanded');
            } else {
                $this.removeClass('expanded');
            }
        }, 350);
    });
    
    // 高亮当前选中的项目行
    highlightSelectedProject();
    
    // 项目选择功能
    $('.project-name-btn').on('click', function() {
        var projectName = $(this).closest('tr').data('project');
        selectProject(projectName);
    });

    // Git提交记录交互功能
    // 点击提交行展开/收起文件变更详情
    $(document).on('click', '.commit-row', function() {
        var hash = $(this).data('commit-hash');
        var shortHash = $(this).find('.commit-hash').text();
        var detailsRow = $('#details-' + shortHash);
        
        if (detailsRow.hasClass('d-none')) {
            // 隐藏其他已展开的详情
            $('.commit-details-row').not(detailsRow).addClass('d-none');
            $('.commit-row').not(this).removeClass('active');
            
            // 显示当前详情
            detailsRow.removeClass('d-none');
            $(this).addClass('active');
        } else {
            // 收起当前详情
            detailsRow.addClass('d-none');
            $(this).removeClass('active');
        }
    });
    
    // 刷新Git提交历史
    $('#refresh-git-history').on('click', function() {
        refreshGitHistory();
    });
    
    // 每页显示记录数变更处理
    $('#per-page-select').on('change', function() {
        changePerPage($(this).val());
    });
    
    // 初始化当页面有哈希值时自动展开对应的提交记录
    function initCommitDetails() {
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            var commitRow = $('.commit-row[data-commit-hash="' + hash + '"]');
            if (commitRow.length) {
                commitRow.click();
                
                // 滚动到该提交记录
                $('html, body').animate({
                    scrollTop: commitRow.offset().top - 100
                }, 500);
            }
        }
    }
    
    // 高亮当前选中的项目行
    function highlightSelectedProject() {
        // 获取当前URL中的project参数
        var currentUrl = new URL(window.location.href);
        var selectedProject = currentUrl.searchParams.get('project');
        
        if (selectedProject) {
            // 高亮对应的项目行
            $('.project-row[data-project="' + selectedProject + '"]').addClass('active');
            $('.project-name-btn[data-project="' + selectedProject + '"]').addClass('active');
            
            // 自动展开当前选中项目的详情
            $('#project-details-' + selectedProject).addClass('show');
            $('[data-target="#project-details-' + selectedProject + '"]').addClass('expanded');
        }
    }
    
    // 页面加载完成后初始化提交详情
    $(window).on('load', function() {
        initCommitDetails();
    });

    // 仅当标签页显示时初始化进度图表
    $('#progress').on('shown.bs.tab', function() {
        // 确保 Chart 已定义
        if (typeof Chart !== 'undefined') {
            initProgressChart();
        } else {
            console.warn('Chart.js 未加载，无法初始化图表');
        }
    });
    
    // 使用事件委托绑定添加任务按钮点击事件
    $(document).on('click', '#add-task-btn', function(e) {
        // 阻止默认行为
        e.preventDefault();
        
        // 重置表单
        $('#task-form')[0].reset();
        $('#task-id').val(0);
        $('#task-modal-title').text('添加新任务');
        $('#percentage-value').text('0%');
        
        // 显示模态框
        $('#task-modal').modal('show');
    });
    
    // 处理更新进度按钮点击事件
    $(document).on('click', '.update-progress', function() {
        var taskId = $(this).data('task-id');
        var status = $(this).data('status');
        var percentage = $(this).data('percentage');
        
        // 设置模态框中的值
        $('#update-task-id').val(taskId);
        $('#update-status').val(status);
        $('#update-percentage').val(percentage);
        $('#percentage-value').text(percentage + '%');
        
        // 显示模态框
        $('#update-progress-modal').modal('show');
    });
    
    // 更新百分比显示
    $('#update-percentage').on('input', function() {
        var percentage = $(this).val();
        $('#percentage-value').text(percentage + '%');
    });
    
    // 保存进度更新
    $('#save-progress').on('click', function() {
        var taskId = $('#update-task-id').val();
        var status = $('#update-status').val();
        var percentage = $('#update-percentage').val();
        
        // 发送AJAX请求保存更新
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/update-progress',
            type: 'POST',
            data: {
                task_id: taskId,
                status: status,
                percentage: percentage
            },
            success: function(response) {
                if (response.success) {
                    // 更新UI
                    var $taskItem = $('.task-item[data-task-id="' + taskId + '"]');
                    
                    // 更新进度条
                    $taskItem.find('.progress-bar').css('width', percentage + '%')
                        .attr('aria-valuenow', percentage)
                        .text(percentage + '%');
                    
                    // 更新状态文本和图标
                    var statusText = '';
                    var statusIcon = '';
                    if (status === 'completed') {
                        statusText = '已完成';
                        statusIcon = 'check-circle';
                    } else if (status === 'in_progress') {
                        statusText = '进行中';
                        statusIcon = 'spinner fa-spin';
                    } else {
                        statusText = '待处理';
                        statusIcon = 'clock';
                    }
                    
                    $taskItem.find('.task-status')
                        .removeClass('status-pending status-in_progress status-completed')
                        .addClass('status-' + status)
                        .html('<i class="fa fa-' + statusIcon + '"></i> ' + statusText);
                    
                    // 更新按钮数据属性
                    $taskItem.find('.update-progress')
                        .data('status', status)
                        .data('percentage', percentage);
                    
                    // 关闭模态框
                    $('#update-progress-modal').modal('hide');
                    
                    // 更新图表
                    if ($('#progress').hasClass('active')) {
                        initProgressChart();
                    }
                } else {
                    alert('保存进度失败，请重试');
                }
            },
            error: function() {
                alert('保存进度失败，请重试');
            }
        });
    });
});

/**
 * 项目任务管理JavaScript
 */
$(function() {
    // 任务百分比滑块更新显示
    $(document).on('input', '#task-percentage', function() {
        $('#percentage-value').text($(this).val() + '%');
        
        // 如果百分比是100%，自动将状态设置为已完成
        if ($(this).val() == 100) {
            $('#task-status').val('completed');
        } 
        // 如果百分比大于0且小于100，自动将状态设置为进行中
        else if ($(this).val() > 0) {
            $('#task-status').val('in_progress');
        }
        // 如果百分比是0，自动将状态设置为待处理
        else {
            $('#task-status').val('pending');
        }
    });
    
    // 旧版本的百分比滑块更新显示（保留向后兼容）
    $(document).on('input', '#update-percentage', function() {
        $('#update-percentage-value').text($(this).val() + '%');
    });

    // 点击编辑任务按钮
    $(document).on('click', '.update-progress', function() {
        var taskId = $(this).data('task-id');
        var status = $(this).data('status');
        var percentage = $(this).data('percentage');
        var name = $(this).data('name');
        var description = $(this).data('description');
        var priority = $(this).data('priority');
        var deadline = $(this).data('deadline');
        var assignedTo = $(this).data('assigned-to');
        
        // 填充表单数据
        $('#task-id').val(taskId);
        $('#task-name').val(name);
        $('#task-description').val(description);
        $('#task-status').val(status);
        $('#task-percentage').val(percentage);
        $('#percentage-value').text(percentage + '%');
        $('#task-priority').val(priority);
        $('#task-deadline').val(deadline);
        $('#task-assigned-to').val(assignedTo);
        
        // 设置模态框标题
        $('#task-modal-title').text('编辑任务');
        
        // 显示模态框
        $('#task-modal').modal('show');
    });

    // 保存任务
    $(document).on('click', '#save-task', function() {
        // 表单验证
        if (!$('#task-name').val().trim()) {
            alert('请输入任务名称');
            return;
        }
        
        // 提交表单
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/update-progress',
            type: 'POST',
            data: $('#task-form').serialize(),
            success: function(response) {
                if (response.success) {
                    // 关闭模态框
                    $('#task-modal').modal('hide');
                    
                    // 刷新页面
                    location.reload();
                } else {
                    alert('保存失败: ' + response.message);
                }
            },
            error: function(xhr) {
                var errorMsg = '保存失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });

    // 查看任务历史
    $(document).on('click', '.view-history', function() {
        var taskId = $(this).data('task-id');
        var taskName = $(this).closest('.task-item').find('.task-name').text();
        
        // 设置任务名称
        $('#history-task-name').text(taskName);
        
        // 清空历史记录容器
        $('#history-timeline').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="sr-only">加载中...</span></div></div>');
        
        // 显示模态框
        $('#history-modal').modal('show');
        
        // 加载任务历史
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/tasks/' + taskId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var history = response.history;
                    var html = '';
                    
                    if (history.length === 0) {
                        html = '<div class="text-center py-3">暂无历史记录</div>';
                    } else {
                        for (var i = 0; i < history.length; i++) {
                            var record = history[i];
                            var statusText = '';
                            var statusClass = '';
                            
                            if (record.status === 'completed') {
                                statusText = '已完成';
                                statusClass = 'success';
                            } else if (record.status === 'in_progress') {
                                statusText = '进行中';
                                statusClass = 'info';
                            } else {
                                statusText = '待处理';
                                statusClass = 'secondary';
                            }
                            
                            html += '<div class="timeline-item mb-3">';
                            html += '<div class="timeline-date small text-muted">' + record.timestamp + '</div>';
                            html += '<div class="timeline-content p-2 border rounded">';
                            html += '<div class="d-flex justify-content-between">';
                            html += '<span class="badge badge-' + statusClass + '">' + statusText + '</span>';
                            html += '<span class="badge badge-primary">' + record.percentage + '%</span>';
                            html += '</div>';
                            if (record.changed_by) {
                                html += '<div class="small text-muted mt-1">更新者: ' + record.changed_by + '</div>';
                            }
                            html += '</div>';
                            html += '</div>';
                        }
                    }
                    
                    $('#history-timeline').html(html);
                } else {
                    $('#history-timeline').html('<div class="text-center py-3 text-danger">加载失败</div>');
                }
            },
            error: function() {
                $('#history-timeline').html('<div class="text-center py-3 text-danger">加载失败</div>');
            }
        });
    });

    // 删除任务
    $(document).on('click', '.delete-task', function() {
        var taskId = $(this).data('task-id');
        var selectedProject = $('#task-form input[name="project"]').val();
        
        if (confirm('确定要删除这个任务吗？此操作不可恢复。')) {
            $.ajax({
                url: window.GROCY_BASEURL + '/projectprogress/tasks/' + taskId + '?project=' + selectedProject,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        // 刷新页面
                        location.reload();
                    } else {
                        alert('删除失败: ' + response.message);
                    }
                },
                error: function(xhr) {
                    var errorMsg = '删除失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                }
            });
        }
    });

    // 兼容旧版本的保存进度按钮（保留向后兼容）
    $(document).on('click', '#save-progress', function() {
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/update-progress',
            type: 'POST',
            data: $('#update-progress-form').serialize(),
            success: function(response) {
                if (response.success) {
                    // 关闭模态框
                    $('#update-progress-modal').modal('hide');
                    
                    // 刷新页面
                    location.reload();
                } else {
                    alert('保存失败: ' + response.message);
                }
            },
            error: function() {
                alert('保存失败');
            }
        });
    });
    
    // 项目选择功能
    $(document).on('click', '.project-name-btn', function() {
        var projectName = $(this).data('project');
        selectProject(projectName);
    });
    
    // 刷新Git历史按钮
    $(document).on('click', '#refresh-git-history', function() {
        refreshGitHistory();
    });
    
    // 每页显示记录数变更
    $(document).on('change', '#per-page-select', function() {
        var perPage = $(this).val();
        changePerPage(perPage);
    });
});

/**
 * 初始化项目进度图表
 */
function initProgressChart() {
    var chartElement = document.getElementById('progress-chart');
    if (!chartElement) return;
    
    // 检查 Chart 是否已定义
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js 库未加载，跳过图表初始化');
        return;
    }
    
    var ctx = chartElement.getContext('2d');
    // 从页面获取统计数据
    var completedPercentage = parseInt($(chartElement).data('completed-percentage') || 0);
    var remainingPercentage = 100 - completedPercentage;
    
    var progressChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [completedPercentage, remainingPercentage],
                backgroundColor: ['#4e73df', '#eaecf4'],
                borderWidth: 0
            }]
        },
        options: {
            cutoutPercentage: 80,
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            tooltips: {
                enabled: false
            }
        }
    });
}

/**
 * 项目选择功能
 */
function selectProject(projectName) {
    var currentUrl = new URL(window.location.href);
    var params = new URLSearchParams(currentUrl.search);
    
    // 设置项目名称并重置页码为1
    params.set('project', projectName);
    params.set('page', 1);
    
    // 构建新URL并跳转
    currentUrl.search = params.toString();
    window.location.href = currentUrl.toString();
}

/**
 * 处理每页显示记录数变更
 */
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

/**
 * 刷新Git提交历史
 */
function refreshGitHistory() {
    // 显示加载中提示
    var refreshBtn = document.getElementById('refresh-git-history');
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 加载中...';
    
    // 重新加载页面，保留当前URL参数
    var currentUrl = new URL(window.location.href);
    
    // 添加时间戳参数，避免浏览器缓存
    var timestamp = new Date().getTime();
    currentUrl.searchParams.set('_', timestamp);
    
    window.location.href = currentUrl.toString();
}

// 确保 GROCY_BASEURL 定义
if (typeof window.GROCY_BASEURL === 'undefined') {
    window.GROCY_BASEURL = '';
} 
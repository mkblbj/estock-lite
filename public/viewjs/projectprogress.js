/**
 * 项目流程进展跟踪页面的JavaScript功能
 */
$(document).ready(function() {
    // 初始化进度图表
    initProgressChart();
    
    // 初始化任务列表
    refreshTasksList();
    
    // 初始化项目统计信息
    refreshProjectStatistics();

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
    
    // 项目选择功能（使用事件委托）
    $(document).on('click', '.project-name-btn', function(e) {
        e.preventDefault();
        var projectName = $(this).data('project');
        if (projectName) {
            console.log('点击项目按钮，项目名称:', projectName);
            selectProject(projectName);
        } else {
            console.error('无法获取项目名称');
        }
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
        
        // 检查URL是否带有标签页锚点
        if (window.location.hash) {
            // 激活指定的标签页
            $('a[href="' + window.location.hash + '"]').tab('show');
        }
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
    $(document).on('click', '.update-progress', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var taskId = $(this).data('task-id');
        var status = $(this).data('status');
        var percentage = $(this).data('percentage');
        var priority = $(this).closest('.task-item').data('priority');
        
        // 获取任务名称
        var taskName = $(this).closest('.task-item').find('.task-name').text().trim();
        
        // 设置模态框中的值
        $('#update-task-id').val(taskId);
        $('#update-task-name').val(taskName);
        $('#update-status').val(status);
        $('#update-percentage').val(percentage);
        $('#update-percentage-value').text(percentage + '%');
        $('#update-priority').val(priority);
        
        // 显示模态框
        $('#update-progress-modal').modal('show');
    });
    
    // 处理编辑任务按钮点击事件
    $(document).on('click', '.edit-task', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
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
    
    // 更新百分比显示
    $('#update-percentage').on('input', function() {
        var percentage = $(this).val();
        $('#update-percentage-value').text(percentage + '%');
        
        // 如果百分比是100%，自动将状态设置为已完成
        if (percentage == 100) {
            $('#update-status').val('completed');
        } 
        // 如果百分比大于0且小于100，自动将状态设置为进行中
        else if (percentage > 0) {
            $('#update-status').val('in_progress');
        }
        // 如果百分比是0，自动将状态设置为待处理
        else {
            $('#update-status').val('pending');
        }
    });
    
    // 保存进度更新
    $('#save-progress').on('click', function() {
        var taskId = $('#update-task-id').val();
        
        // 使用表单序列化来发送所有字段
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/update-progress',
            type: 'POST',
            data: $('#update-progress-form').serialize(),
            success: function(response) {
                if (response.success) {
                    // 调试信息 - 检查统计数据
                    console.log('更新进度响应:', response);
                    
                    // 关闭模态框
                    $('#update-progress-modal').modal('hide');
                    
                    // 显示成功消息
                    showSuccessMessage('任务进度已更新');
                    
                    // 使用任务列表更新替代手动更新单个元素，确保所有任务状态和数据正确刷新
                    refreshTasksList();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                alert('保存进度失败，请重试');
            }
        });
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
                    // 调试信息
                    console.log('保存任务响应:', response);
                    
                    // 关闭模态框
                    $('#task-modal').modal('hide');
                    
                    // 更新任务列表，但不刷新整个页面
                    refreshTasksList();
                    
                    // 使用单独请求更新统计数据
                    refreshProjectStatistics();
                    
                    // 显示成功消息
                    showSuccessMessage('任务已保存');
                } else {
                    toastr.error(response.message || '保存任务失败，请重试');
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

    // 查看项目任务历史
    $(document).on('click', '#view-project-history', function() {
        var selectedProject = $('#task-form input[name="project"]').val();
        
        // 清空历史记录容器
        $('#history-timeline').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="sr-only">加载中...</span></div></div>');
        
        // 更新模态框标题
        $('#history-task-name').text(selectedProject + ' 项目任务历史');
        
        // 显示模态框
        $('#history-modal').modal('show');
        
        // 加载项目所有任务历史
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/project-task-history?project=' + selectedProject,
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
                            
                            html += '<div class="timeline-item mb-3 p-2 border rounded">';
                            html += '<div class="d-flex justify-content-between align-items-center">';
                            html += '<strong>' + record.task_name + '</strong>';
                            html += '<span class="badge badge-' + statusClass + '">' + statusText + '</span>';
                            html += '</div>';
                            html += '<div class="d-flex justify-content-between align-items-center mt-1">';
                            html += '<div class="small text-muted">' + record.timestamp + '</div>';
                            html += '<span class="badge badge-primary">' + record.percentage + '%</span>';
                            html += '</div>';
                            if (record.changed_by) {
                                html += '<div class="small text-muted">更新者: ' + record.changed_by + '</div>';
                            }
                            html += '</div>';
                        }
                    }
                    
                    $('#history-timeline').html(html);
                } else {
                    $('#history-timeline').html('<div class="text-center py-3 text-danger">加载失败: ' + (response.message || '未知错误') + '</div>');
                }
            },
            error: function() {
                $('#history-timeline').html('<div class="text-center py-3 text-danger">加载失败: 服务器连接错误</div>');
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
                        // 调试信息
                        console.log('删除任务响应:', response);
                        
                        // 显示成功消息
                        showSuccessMessage('任务已删除');
                        
                        // 更新任务列表，但不刷新整个页面
                        refreshTasksList();
                        
                        // 使用单独请求更新统计数据
                        refreshProjectStatistics();
                    } else {
                        toastr.error(response.message || '删除任务失败，请重试');
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
    console.log('选择项目:', projectName);
    
    // 显示加载提示
    var loadingHtml = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">加载中...</div></div>';
    $('.progress-tasks').html(loadingHtml);
    $('#git-commits .card-body').html(loadingHtml);
    $('#requirements .card-body').html(loadingHtml);
    
    // 更新项目选择状态
    $('.project-row').removeClass('active');
    $('.project-row[data-project="' + projectName + '"]').addClass('active');
    $('.project-name-btn').removeClass('active');
    $('.project-name-btn[data-project="' + projectName + '"]').addClass('active');
    
    // 更新表单中的项目值
    $('#task-form input[name="project"]').val(projectName);
    $('#update-progress-form input[name="project"]').val(projectName);
    
    // 更新URL，但不刷新页面
    var currentUrl = new URL(window.location.href);
    var params = new URLSearchParams(currentUrl.search);
    params.set('project', projectName);
    params.set('page', 1);
    window.history.pushState({}, '', '?' + params.toString());
    
    // 更新页面标题和项目名称显示
    $('.card-header .badge-primary').text(projectName);
    
    // 自动切换到Git提交记录标签页
    $('a[href="#git-commits"]').tab('show');
    
    // 使用 Promise.all 并行加载所有数据
    Promise.all([
        // 刷新任务列表
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/tasks-partial?project=' + projectName,
            type: 'GET'
        }).catch(function(error) {
            console.error('加载任务列表失败:', error);
            return '<div class="alert alert-danger">加载任务列表失败，请刷新页面重试</div>';
        }),
        
        // 刷新项目统计信息
        $.ajax({
            url: window.GROCY_BASEURL + '/projectprogress/statistics?project=' + projectName,
            type: 'GET'
        }).catch(function(error) {
            console.error('加载统计信息失败:', error);
            return { success: false, message: '加载统计信息失败' };
        })
    ]).then(function(results) {
        // 更新任务列表
        if (results[0]) {
            $('.progress-tasks').html(results[0]);
        }
        
        // 更新统计信息
        if (results[1] && results[1].success) {
            updateTaskStatistics(results[1].statistics);
        }
        
        // 展开当前项目的详情
        $('.project-row').each(function() {
            var $row = $(this);
            var $details = $($row.find('.details-toggle').data('target'));
            if ($row.data('project') === projectName) {
                $details.addClass('show');
                $row.find('.details-toggle').addClass('expanded');
            } else {
                $details.removeClass('show');
                $row.find('.details-toggle').removeClass('expanded');
            }
        });
        
        // 自动触发刷新Git提交记录和需求文档
        refreshGitHistory();
        refreshRequirements();
    }).catch(function(error) {
        console.error('项目数据加载失败:', error);
        toastr.error('加载项目数据时发生错误，请刷新页面重试');
    });
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

/**
 * 刷新任务列表部分
 */
function refreshTasksList() {
    // 获取项目名称
    var selectedProject = $('#task-form input[name="project"]').val();
    
    // 显示加载中提示
    $('.progress-tasks').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">加载中...</div></div>');
    
    // 发送AJAX请求获取最新任务列表
    $.ajax({
        url: window.GROCY_BASEURL + '/projectprogress/tasks-partial?project=' + selectedProject,
        type: 'GET',
        success: function(response) {
            // 更新任务列表内容
            $('.progress-tasks').html(response);
            
            // 任务列表更新后，重新绑定任务元素上的数据
            $('.task-item').each(function() {
                var $item = $(this);
                var taskId = $item.data('task-id');
                var taskName = $item.find('.task-name').text().trim();
                var status = $item.data('status');
                var percentage = $item.data('percentage');
                var priority = $item.data('priority');
                var assignedTo = $item.data('assigned-to');
                
                // 确保编辑和更新进度按钮上的数据属性正确
                $item.find('.edit-task').data({
                    'task-id': taskId,
                    'name': taskName,
                    'status': status,
                    'percentage': percentage,
                    'priority': priority,
                    'assigned-to': assignedTo
                });
                
                $item.find('.update-progress').data({
                    'task-id': taskId,
                    'status': status,
                    'percentage': percentage
                });
            });
            
            // 更新进度图表
            refreshProjectStatistics();
        },
        error: function() {
            $('.progress-tasks').html('<div class="alert alert-danger">加载任务列表失败，请刷新页面重试</div>');
        }
    });
}

/**
 * 刷新项目统计数据，不刷新整个页面
 */
function refreshProjectStatistics() {
    // 获取项目名称
    var selectedProject = $('#task-form input[name="project"]').val();
    
    console.log('正在请求最新统计数据，项目:', selectedProject);
    
    // 发送AJAX请求获取最新统计数据
    $.ajax({
        url: window.GROCY_BASEURL + '/projectprogress/statistics?project=' + selectedProject,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('获取最新统计数据成功:', response);
            
            if (response.success && response.statistics) {
                // 更新统计数据
                var stats = response.statistics;
                
                console.log('更新页面显示的统计数据:', stats);
                
                // 更新总进度百分比
                var completedPercentage = stats.completed_percentage;
                if (completedPercentage !== null && completedPercentage !== undefined) {
                    // 更新图表数据
                    var chartElement = document.getElementById('progress-chart');
                    if (chartElement) {
                        $(chartElement).data('completed-percentage', completedPercentage);
                        initProgressChart();
                    }
                    
                    // 更新进度文本
                    $('.progress-circle .position-absolute h2').text(completedPercentage + '%');
                } else {
                    console.warn('统计数据中的completed_percentage为null或undefined');
                }
                
                // 更新其他统计数据
                $('.total-tasks-count').text(stats.total_count !== null ? stats.total_count : 0);
                $('.completed-tasks-count').text(stats.completed_count !== null ? stats.completed_count : 0);
                $('.in-progress-tasks-count').text(stats.in_progress_count !== null ? stats.in_progress_count : 0);
                $('.pending-tasks-count').text(stats.pending_count !== null ? stats.pending_count : 0);
            } else {
                console.warn('获取统计数据失败或数据无效:', response);
                if (response.success === false && response.message) {
                    console.error('错误信息:', response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('获取项目统计数据失败:', status, error);
            console.log('XHR状态:', xhr.status, xhr.statusText);
            console.log('响应文本:', xhr.responseText);
        }
    });
}

/**
 * 更新任务统计信息
 */
function updateTaskStatistics(statistics) {
    if (!statistics) return;
    
    console.log('执行updateTaskStatistics:', statistics);
    
    // 防止null值导致显示问题
    var completedPercentage = statistics.completed_percentage;
    if (completedPercentage === null || completedPercentage === undefined) {
        console.warn('完成百分比为null，不更新图表');
        return; // 如果百分比为null，不进行更新
    }
    
    // 更新图表
    var chartElement = document.getElementById('progress-chart');
    if (chartElement) {
        $(chartElement).data('completed-percentage', completedPercentage);
        if (typeof Chart !== 'undefined') {
            initProgressChart();
        }
    }
    
    // 更新页面上显示的总进度百分比
    $('.progress-circle .position-absolute h2').text(completedPercentage + '%');
    
    // 更新其他统计数据 - 使用0作为null值的替代
    $('.total-tasks-count').text(statistics.total_count !== null ? statistics.total_count : 0);
    $('.completed-tasks-count').text(statistics.completed_count !== null ? statistics.completed_count : 0);
    $('.in-progress-tasks-count').text(statistics.in_progress_count !== null ? statistics.in_progress_count : 0);
    $('.pending-tasks-count').text(statistics.pending_count !== null ? statistics.pending_count : 0);
}

/**
 * 显示成功消息
 */
function showSuccessMessage(message) {
    var alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
        message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>';
    
    // 添加到页面顶部
    var alertContainer = $('.alert-container');
    if (alertContainer.length === 0) {
        alertContainer = $('<div class="alert-container"></div>');
        $('#progress-chart-container').before(alertContainer);
    }
    
    alertContainer.html(alertHtml);
    
    // 3秒后自动消失
    setTimeout(function() {
        $('.alert').alert('close');
    }, 3000);
}

/**
 * 刷新需求文档
 */
function refreshRequirements() {
    // 显示加载中提示
    var refreshBtn = document.getElementById('refresh-requirements');
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 加载中...';
    }
    
    // 获取当前项目
    var selectedProject = $('#task-form input[name="project"]').val();
    
    // 发送AJAX请求获取最新的需求文档
    $.ajax({
        url: window.GROCY_BASEURL + '/projectprogress/requirements-partial?project=' + selectedProject + '&_=' + new Date().getTime(),
        type: 'GET',
        success: function(response) {
            // 更新需求文档内容
            $('#requirements .card-body').html(response);
            
            // 恢复刷新按钮状态
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fa fa-sync"></i> 刷新';
            }
            
            // 重新绑定文档选择器事件
            $('.doc-selector').on('click', function() {
                var targetId = $(this).data('target');
                
                // 更新按钮状态
                $('.doc-selector').removeClass('btn-primary').addClass('btn-outline-primary');
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                
                // 切换显示对应的文档
                $('.markdown-preview').addClass('d-none');
                $('#preview-' + targetId).removeClass('d-none');
            });
        },
        error: function(xhr) {
            console.error('刷新需求文档失败:', xhr);
            $('#requirements .card-body').html('<div class="alert alert-danger">刷新需求文档失败，请重试</div>');
            
            // 恢复刷新按钮状态
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fa fa-sync"></i> 刷新';
            }
        }
    });
}

// 确保 GROCY_BASEURL 定义
if (typeof window.GROCY_BASEURL === 'undefined') {
    window.GROCY_BASEURL = '';
} 
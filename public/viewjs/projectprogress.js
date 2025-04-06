/**
 * 项目流程进展跟踪页面的JavaScript功能
 */
$(document).ready(function() {
    // 初始化Markdown编辑器
    var simplemde = new SimpleMDE({
        element: document.getElementById("markdown-editor"),
        spellChecker: false,
        autosave: {
            enabled: true,
            unique_id: "project_requirements",
            delay: 1000,
        },
        toolbar: ["bold", "italic", "heading", "|", 
                 "quote", "unordered-list", "ordered-list", "|", 
                 "link", "image", "table", "|", 
                 "preview", "side-by-side", "fullscreen"]
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
        // 显示加载中提示
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 加载中...');
        
        // 重新加载页面
        window.location.reload();
    });

    // 初始化项目进度图表
    function initProgressChart() {
        var ctx = document.getElementById('progress-chart').getContext('2d');
        
        // 收集数据
        var taskNames = [];
        var taskPercentages = [];
        var taskColors = [];
        
        $('.task-item').each(function() {
            var taskName = $(this).find('.task-name').text();
            var percentage = parseInt($(this).find('.progress-bar').attr('aria-valuenow'));
            var status = $(this).find('.task-status').attr('class').split(' ')[1].replace('status-', '');
            
            taskNames.push(taskName);
            taskPercentages.push(percentage);
            
            // 根据状态设置颜色
            if (status === 'completed') {
                taskColors.push('#28a745');
            } else if (status === 'in_progress') {
                taskColors.push('#ffc107');
            } else {
                taskColors.push('#6c757d');
            }
        });
        
        // 创建饼图
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: taskNames,
                datasets: [{
                    data: taskPercentages,
                    backgroundColor: taskColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue) {
                                return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue/total) * 100) + 0.5);
                            return data.labels[tooltipItem.index] + ': ' + percentage + '%';
                        }
                    }
                }
            }
        });
    }
    
    // 页面加载完成后初始化图表
    $('#progress').on('shown.bs.tab', function() {
        initProgressChart();
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
            url: 'projectprogress/update-progress',
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
$(document).ready(function() {
    // 仪表盘初始化
    Dashboard.Init();
    
    // 初始化图表
    Dashboard.InitCharts();
    
    // 刷新按钮点击事件 - 确保只绑定一次
    $("#refresh-dashboard").off('click').on('click', function() {
        Dashboard.RefreshData(false); // 手动刷新
    });
});

// 定义全局翻译函数，用于JavaScript中使用翻译
function __t(text) {
    // 调试输出
    console.log('翻译请求:', text);
    
    // 检查翻译对象是否存在
    if (typeof window.__localizationStrings === 'undefined' || window.__localizationStrings === null) {
        console.error('翻译对象不存在!');
        return text;
    }
    
    // 由于在PHP中，__t('dashboard.purchases')风格的键在JSON中实际存储为原始形式
    // 即 key="dashboard.purchases" 而不是 key="dashboard_purchases"
    // 因此我们直接使用完整键名查找
    
    if (window.__localizationStrings[text] !== undefined) {
        console.log('找到翻译 [直接匹配]:', text, '->', window.__localizationStrings[text]);
        return window.__localizationStrings[text];
    }
    
    // 如果找不到，尝试一些备选策略
    
    // 1. 尝试移除前缀（如果有的话）
    if (text.includes('.')) {
        const parts = text.split('.');
        const simpleKey = parts[parts.length - 1];
        if (window.__localizationStrings[simpleKey] !== undefined) {
            console.log('找到翻译 [移除前缀]:', text, '->', window.__localizationStrings[simpleKey]);
            return window.__localizationStrings[simpleKey];
        }
    }
    
    // 2. 如果是点号形式的键，尝试使用下划线替换点号
    if (text.includes('.')) {
        const underscoredKey = text.replace(/\./g, '_');
        if (window.__localizationStrings[underscoredKey] !== undefined) {
            console.log('找到翻译 [下划线替换]:', text, '->', window.__localizationStrings[underscoredKey]);
            return window.__localizationStrings[underscoredKey];
        }
    }
    
    // 3. 检查键是否存在但值为null或空字符串
    if (text in window.__localizationStrings) {
        if (window.__localizationStrings[text] === null || window.__localizationStrings[text] === '') {
            console.log('键存在但值为空:', text);
            return text;
        }
    }
    
    // 未找到任何匹配
    console.warn('未找到任何匹配的翻译:', text);
    
    // 将该键添加到已知缺失的翻译列表（可用于调试）
    if (!window.__missingTranslations) {
        window.__missingTranslations = new Set();
    }
    window.__missingTranslations.add(text);
    
    // 回退到原始文本
    return text;
}

var Dashboard = {
    // 自动刷新定时器ID
    autoRefreshTimer: null,
    
    // 初始化函数
    Init: function() {
        // 定时刷新（30秒）
        Dashboard.InitAutoRefresh();
        
        // 初始化日期选择器
        Dashboard.InitDateRangePicker();
    },
    
    // 初始化自动刷新
    InitAutoRefresh: function() {
        // 清除可能存在的旧定时器
        if (Dashboard.autoRefreshTimer) {
            clearInterval(Dashboard.autoRefreshTimer);
        }
        
        // 每30秒刷新一次仪表盘数据
        Dashboard.autoRefreshTimer = setInterval(function() {
            Dashboard.RefreshData(true); // 自动刷新
        }, 30000);
    },
    
    // 初始化图表
    InitCharts: function() {
        try {
            console.log(__t('dashboard.loading_data'));
            console.log("趋势数据:", trendData);
            console.log("hasStockTrendData:", hasStockTrendData);
            
            // 检查是否有趋势数据
            if (typeof hasStockTrendData !== 'undefined' && !hasStockTrendData) {
                console.warn(__t('dashboard.no_stock_trend_data'));
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-info text-center">' + __t('dashboard.no_stock_records') + '</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 检查trendData是否存在且为数组
            if (!trendData || !Array.isArray(trendData) || trendData.length === 0) {
                console.error(__t('dashboard.trend_data_error') + ":", trendData);
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-danger text-center">' + __t('dashboard.invalid_trend_data_format') + '</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 库存变动趋势图
            const trendLabels = trendData.map(item => item.date);
            console.log(__t('dashboard.trend_chart_labels') + ":", trendLabels);
            
            // 确保数值转换正确，处理可能的字符串或null值
            const purchaseData = trendData.map(item => {
                if (!item || typeof item.purchases === 'undefined') return 0;
                const val = parseFloat(item.purchases);
                return isNaN(val) ? 0 : val;
            });
            
            const consumptionData = trendData.map(item => {
                if (!item || typeof item.consumptions === 'undefined') return 0;
                const val = parseFloat(item.consumptions);
                return isNaN(val) ? 0 : val;
            });
            
            console.log(__t('dashboard.purchase_data') + ":", purchaseData);
            console.log(__t('dashboard.consumption_data') + ":", consumptionData);
            
            // 检查是否所有数据都为0，如果是，则显示提示
            const allZero = purchaseData.every(val => val === 0) && consumptionData.every(val => val === 0);
            if (allZero) {
                console.warn(__t('dashboard.all_trend_data_zero'));
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-info text-center">' + __t('dashboard.no_trend_data_changes') + '</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 检查趋势图容器是否存在
            const trendCanvas = document.getElementById('stockTrendChart');
            if (!trendCanvas) {
                console.error(__t('dashboard.trend_chart_container_not_found'));
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 尝试销毁现有图表实例（如果存在）
            if (window.trendChartInstance) {
                try {
                    window.trendChartInstance.destroy();
                } catch (e) {
                    console.warn(__t('dashboard.destroy_chart_failed') + ":", e);
                }
            }
            
            // 创建图表
            try {
                console.log(__t('dashboard.creating_trend_chart'));
                window.trendChartInstance = new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: window.chartTranslations && window.chartTranslations['purchases'] || __t('dashboard.purchases'),
                                data: purchaseData,
                                backgroundColor: 'rgba(40, 167, 169, 0.2)',
                                borderColor: 'rgba(40, 167, 169, 1)',
                                borderWidth: 2,
                                tension: 0.4
                            },
                            {
                                label: window.chartTranslations && window.chartTranslations['consumptions'] || __t('dashboard.consumptions'),
                                data: consumptionData,
                                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 2,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y.toFixed(2);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log(__t('dashboard.trend_chart_created_successfully'));
            } catch (chartError) {
                console.error(__t('dashboard.trend_chart_creation_failed') + ":", chartError);
                const parent = trendCanvas.parentNode;
                parent.innerHTML = '<div class="alert alert-danger text-center">' + __t('dashboard.chart_initialization_failed') + ': ' + chartError.message + '</div>';
            }
            
            // 初始化其他图表
            this.InitCategoryChart();
            this.InitLocationChart();
        } catch (error) {
            console.error(__t('dashboard.chart_initialization_error') + ":", error);
        }
    },
    
    // 初始化分类图表
    InitCategoryChart: function() {
        if (categoryData && Array.isArray(categoryData) && categoryData.length > 0) {
            const categoryLabels = categoryData.map(item => item.category);
            const categoryValues = categoryData.map(item => item.count);
            const categoryColors = Dashboard.GenerateColors(categoryData.length);
            
            const categoryChart = new Chart(document.getElementById('categoryChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryValues,
                        backgroundColor: categoryColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: window.chartTranslations && window.chartTranslations['Category Distribution'] || __t('dashboard.category_distribution')
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const productCountText = window.chartTranslations && window.chartTranslations['Product Count'] || __t('dashboard.product_count');
                                    return `${label}: ${value} ${productCountText}`;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            console.warn(__t('dashboard.category_data_invalid'));
            const categoryContainer = document.getElementById('categoryChart');
            if (categoryContainer) {
                const parent = categoryContainer.parentNode;
                const noDataText = window.chartTranslations && window.chartTranslations['No data available'] || __t('dashboard.no_stock_trend_data');
                parent.innerHTML = '<div class="alert alert-warning text-center">' + noDataText + '</div>';
            }
        }
    },
    
    // 初始化位置图表
    InitLocationChart: function() {
        if (locationData && Array.isArray(locationData) && locationData.length > 0) {
            const locationLabels = locationData.map(item => item.location);
            const locationValues = locationData.map(item => item.count);
            const locationColors = Dashboard.GenerateColors(locationData.length);
            
            const locationChart = new Chart(document.getElementById('locationChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: locationLabels,
                    datasets: [{
                        label: window.chartTranslations && window.chartTranslations['Product Count'] || __t('dashboard.product_count'),
                        data: locationValues,
                        backgroundColor: locationColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: window.chartTranslations && window.chartTranslations['Product Count'] || __t('dashboard.product_count')
                            }
                        },
                        x: {
                            title: {
                                display: true, 
                                text: window.chartTranslations && window.chartTranslations['Location Distribution'] || __t('dashboard.location_distribution')
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: window.chartTranslations && window.chartTranslations['Location Distribution'] || __t('dashboard.location_distribution')
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ${value}`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        } else {
            console.warn(__t('dashboard.location_data_invalid'));
            const locationContainer = document.getElementById('locationChart');
            if (locationContainer) {
                const parent = locationContainer.parentNode;
                const noDataText = window.chartTranslations && window.chartTranslations['No data available'] || __t('dashboard.no_stock_trend_data');
                parent.innerHTML = '<div class="alert alert-warning text-center">' + noDataText + '</div>';
            }
        }
    },
    
    // 生成随机颜色
    GenerateColors: function(count) {
        const colors = [
            'rgba(40, 167, 169, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(201, 203, 207, 0.8)'
        ];
        
        // 如果颜色不够，随机生成
        while(colors.length < count) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
        }
        
        return colors.slice(0, count);
    },
    
    // 跟踪AJAX请求，避免重复请求
    isRefreshing: false,
    
    // 刷新仪表盘数据
    RefreshData: function(isAuto) {
        // 如果已经在刷新中，则不重复刷新
        if (Dashboard.isRefreshing) {
            return;
        }
        
        // 标记为正在刷新
        Dashboard.isRefreshing = true;
        
        // 如果是自动刷新，则不显示加载指示器
        if (!isAuto) {
            // 添加刷新指示器
            $("#refresh-dashboard").html('<i class="fa-solid fa-spinner fa-spin"></i>&nbsp;' + __t('dashboard.refreshing'));
        }
        
        // 使用AJAX重新获取数据
        $.ajax({
            url: U('/dashboard'),
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                // 提取新的图表数据
                const newDoc = new DOMParser().parseFromString(response, 'text/html');
                
                // 提取新的趋势图数据
                try {
                    console.log(__t('dashboard.extracting_chart_data'));
                    
                    // 方法1：从新页面的脚本标签中提取数据
                    const scriptTags = newDoc.querySelectorAll('script');
                    let newTrendData = null;
                    let newHasStockTrendData = false;
                    let newCategoryData = null;
                    let newLocationData = null;
                    
                    // 添加隐藏字段到页面以传递数据
                    let updatedFromHiddenFields = false;
                    
                    // 尝试从隐藏字段获取数据（备选方案）
                    try {
                        const hiddenTrendData = newDoc.querySelector('#hidden-trend-data');
                        const hiddenCategoryData = newDoc.querySelector('#hidden-category-data');
                        const hiddenLocationData = newDoc.querySelector('#hidden-location-data');
                        const hiddenHasData = newDoc.querySelector('#hidden-has-data');
                        
                        if (hiddenTrendData && hiddenCategoryData && hiddenLocationData) {
                            console.log(__t('dashboard.found_hidden_data_fields'));
                            
                            try {
                                newTrendData = JSON.parse(hiddenTrendData.value);
                                newCategoryData = JSON.parse(hiddenCategoryData.value);
                                newLocationData = JSON.parse(hiddenLocationData.value);
                                newHasStockTrendData = hiddenHasData ? hiddenHasData.value === 'true' : false;
                                
                                updatedFromHiddenFields = true;
                                console.log(__t('dashboard.data_updated_from_hidden_fields'));
                            } catch (e) {
                                console.error(__t('dashboard.parsing_hidden_fields_failed') + ":", e);
                            }
                        }
                    } catch (e) {
                        console.error(__t('dashboard.getting_data_from_hidden_fields_failed') + ":", e);
                    }
                    
                    // 如果没有从隐藏字段获取数据，尝试从脚本标签提取
                    if (!updatedFromHiddenFields) {
                        console.log(__t('dashboard.try_extract_from_script_tags'));
                        // 遍历所有脚本标签，查找数据定义
                        for (let i = 0; i < scriptTags.length; i++) {
                            const scriptContent = scriptTags[i].textContent;
                            
                            // 提取trendData
                            if (scriptContent.includes('var trendData') || 
                                scriptContent.includes('trendData =')) {
                                console.log(__t('dashboard.found_new_trend_data'));
                                
                                // 更强大的正则表达式，支持各种格式
                                const trendRegex = /var\s+trendData\s*=\s*([^;]*);/s;
                                const match = scriptContent.match(trendRegex);
                                
                                if (match && match[1]) {
                                    try {
                                        // 尝试JSON解析
                                        newTrendData = JSON.parse(match[1]);
                                        console.log(__t('dashboard.trend_data_parsed_successfully'));
                                    } catch (e) {
                                        console.error(__t('dashboard.json_parsing_failed') + ":", match[1]);
                                        // 尝试eval作为备选（仅在安全环境中）
                                        try {
                                            newTrendData = eval('(' + match[1] + ')');
                                            console.log(__t('dashboard.trend_data_parsed_via_eval'));
                                        } catch (evalError) {
                                            console.error(__t('dashboard.eval_parsing_failed') + ":", evalError);
                                        }
                                    }
                                }
                                
                                // 提取hasStockTrendData
                                const hasTrendDataRegex = /var\s+hasStockTrendData\s*=\s*(true|false)/;
                                const hasTrendDataMatch = scriptContent.match(hasTrendDataRegex);
                                if (hasTrendDataMatch && hasTrendDataMatch[1]) {
                                    newHasStockTrendData = hasTrendDataMatch[1] === 'true';
                                    console.log(__t('dashboard.found_trend_data_flag') + ":", newHasStockTrendData);
                                }
                                
                                // 提取categoryData
                                const categoryRegex = /var\s+categoryData\s*=\s*([^;]*);/s;
                                const categoryMatch = scriptContent.match(categoryRegex);
                                if (categoryMatch && categoryMatch[1]) {
                                    try {
                                        newCategoryData = JSON.parse(categoryMatch[1]);
                                    } catch (e) {
                                        try {
                                            newCategoryData = eval('(' + categoryMatch[1] + ')');
                                        } catch (evalError) {}
                                    }
                                }
                                
                                // 提取locationData
                                const locationRegex = /var\s+locationData\s*=\s*([^;]*);/s;
                                const locationMatch = scriptContent.match(locationRegex);
                                if (locationMatch && locationMatch[1]) {
                                    try {
                                        newLocationData = JSON.parse(locationMatch[1]);
                                    } catch (e) {
                                        try {
                                            newLocationData = eval('(' + locationMatch[1] + ')');
                                        } catch (evalError) {}
                                    }
                                }
                                
                                break;
                            }
                        }
                    }
                    
                    // 更新全局变量
                    if (newTrendData) {
                        trendData = newTrendData;
                    }
                    if (typeof newHasStockTrendData === 'boolean') {
                        hasStockTrendData = newHasStockTrendData;
                    }
                    if (newCategoryData) {
                        categoryData = newCategoryData;
                    }
                    if (newLocationData) {
                        locationData = newLocationData;
                    }
                    
                    // 重新初始化图表
                    Dashboard.InitCharts();
                } catch (e) {
                    console.error(__t('dashboard.extracting_and_updating_chart_data_failed') + ":", e);
                }
                
                // 更新统计卡片
                const cards = newDoc.querySelectorAll('.display-4');
                document.querySelectorAll('.display-4').forEach((card, index) => {
                    if (cards[index]) {
                        card.textContent = cards[index].textContent;
                    }
                });
                
                // 更新表格数据
                const tables = newDoc.querySelectorAll('table.table');
                document.querySelectorAll('table.table').forEach((table, index) => {
                    if (tables[index]) {
                        table.innerHTML = tables[index].innerHTML;
                    }
                });
                
                // 只有在手动刷新时才恢复按钮状态和显示成功消息
                if (!isAuto) {
                    // 恢复刷新按钮
                    $("#refresh-dashboard").html('<i class="fa-solid fa-sync-alt"></i>&nbsp;' + __t('dashboard.refresh_data'));
                    
                    // 显示成功消息 - 只在手动刷新时显示
                    toastr.success(__t('dashboard.dashboard_data_updated'));
                }
                
                // 标记刷新完成
                Dashboard.isRefreshing = false;
            },
            error: function() {
                // 只有在手动刷新时才恢复按钮状态和显示错误消息
                if (!isAuto) {
                    // 恢复刷新按钮
                    $("#refresh-dashboard").html('<i class="fa-solid fa-sync-alt"></i>&nbsp;' + __t('dashboard.refresh_data'));
                    
                    // 显示错误消息
                    toastr.error(__t('dashboard.refresh_data_failed'));
                }
                
                // 标记刷新完成
                Dashboard.isRefreshing = false;
            }
        });
    },
    
    // 初始化日期范围选择器
    InitDateRangePicker: function() {
        // 如果日期选择器存在
        if ($("#daterange-picker").length) {
            // 确保只绑定一次事件
            $("#daterange-picker").off('click').on('click', function() {
                toastr.info(__t('dashboard.date_filter_future_feature'));
            });
        }
    }
}; 
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
            console.log("开始初始化图表");
            console.log("趋势数据:", trendData);
            console.log("hasStockTrendData:", hasStockTrendData);
            
            // 检查是否有趋势数据
            if (typeof hasStockTrendData !== 'undefined' && !hasStockTrendData) {
                console.warn('没有库存趋势数据可显示');
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-info text-center">暂无出入库记录数据</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 检查trendData是否存在且为数组
            if (!trendData || !Array.isArray(trendData) || trendData.length === 0) {
                console.error('趋势数据无效:', trendData);
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-danger text-center">趋势数据格式无效</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 库存变动趋势图
            const trendLabels = trendData.map(item => item.date);
            console.log("趋势图标签:", trendLabels);
            
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
            
            console.log("入库数据:", purchaseData);
            console.log("出库数据:", consumptionData);
            
            // 检查是否所有数据都为0，如果是，则显示提示
            const allZero = purchaseData.every(val => val === 0) && consumptionData.every(val => val === 0);
            if (allZero) {
                console.warn('趋势数据全为0');
                const trendContainer = document.getElementById('stockTrendChart');
                if (trendContainer) {
                    const parent = trendContainer.parentNode;
                    parent.innerHTML = '<div class="alert alert-info text-center">暂无趋势数据变化，所有值均为0</div>';
                }
                
                // 继续初始化其他图表
                this.InitCategoryChart();
                this.InitLocationChart();
                return;
            }
            
            // 检查趋势图容器是否存在
            const trendCanvas = document.getElementById('stockTrendChart');
            if (!trendCanvas) {
                console.error('找不到趋势图容器元素');
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
                    console.warn('销毁旧图表实例失败:', e);
                }
            }
            
            // 创建图表
            try {
                console.log("开始创建趋势图");
                window.trendChartInstance = new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: '入库',
                                data: purchaseData,
                                backgroundColor: 'rgba(40, 167, 169, 0.2)',
                                borderColor: 'rgba(40, 167, 169, 1)',
                                borderWidth: 2,
                                tension: 0.4
                            },
                            {
                                label: '出库',
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
                console.log("趋势图创建成功");
            } catch (chartError) {
                console.error('创建趋势图失败:', chartError);
                const parent = trendCanvas.parentNode;
                parent.innerHTML = '<div class="alert alert-danger text-center">图表初始化失败: ' + chartError.message + '</div>';
            }
            
            // 初始化其他图表
            this.InitCategoryChart();
            this.InitLocationChart();
        } catch (error) {
            console.error('初始化图表时发生错误:', error);
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
                        }
                    }
                }
            });
        } else {
            console.warn('分类数据无效或为空');
            const categoryContainer = document.getElementById('categoryChart');
            if (categoryContainer) {
                const parent = categoryContainer.parentNode;
                parent.innerHTML = '<div class="alert alert-warning text-center">分类数据加载失败</div>';
            }
        }
    },
    
    // 初始化位置图表
    InitLocationChart: function() {
        if (locationData && Array.isArray(locationData) && locationData.length > 0) {
            // 过滤掉数量为0的位置，但至少保留前10个位置
            let filteredLocationData = [...locationData];
            if (filteredLocationData.length > 10) {
                // 按数量排序
                filteredLocationData.sort((a, b) => b.count - a.count);
                // 只保留有库存的位置和前10个位置
                filteredLocationData = filteredLocationData.filter((item, index) => item.count > 0 || index < 10);
            }
            
            const locationLabels = filteredLocationData.map(item => item.location);
            const locationValues = filteredLocationData.map(item => item.count);
            const locationColors = locationValues.map(value => {
                // 为不同的库存数量设置不同的颜色深度
                const baseColor = 'rgba(54, 162, 235, ';
                const opacity = Math.max(0.3, Math.min(0.9, 0.3 + (value / Math.max(...locationValues, 1)) * 0.6));
                return baseColor + opacity + ')';
            });
            
            const locationChart = new Chart(document.getElementById('locationChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: locationLabels,
                    datasets: [{
                        label: '商品数量',
                        data: locationValues,
                        backgroundColor: locationColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: filteredLocationData.length > 5 ? 'y' : 'x', // 位置较多时使用水平条形图
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const item = filteredLocationData[context.dataIndex];
                                    let labels = [];
                                    labels.push(`商品数量: ${item.count || 0}`);
                                    
                                    // 添加额外信息（如果存在）
                                    if (item.stock_entries !== undefined) {
                                        labels.push(`库存记录数: ${item.stock_entries || 0}`);
                                    }
                                    if (item.total_amount !== undefined) {
                                        labels.push(`总数量: ${(item.total_amount || 0).toFixed(2)}`);
                                    }
                                    
                                    return labels;
                                }
                            }
                        }
                    }
                }
            });
            
            // 添加图表下方的说明信息和图例前，先清除可能存在的旧信息
            const chartParent = document.getElementById('locationChart').parentNode;
            
            // 移除所有已有的图表信息元素
            const existingInfoElements = chartParent.querySelectorAll('.location-chart-info');
            existingInfoElements.forEach(element => {
                element.remove();
            });
            
            // 创建新的信息元素
            const infoElement = document.createElement('div');
            infoElement.className = 'text-muted small text-center mt-2 location-chart-info';
            
            // 计算有库存的位置数量
            const locationsWithStock = filteredLocationData.filter(item => item.count > 0).length;
            
            infoElement.innerHTML = `
                <div>显示 ${filteredLocationData.length} 个位置，共 ${locationData.length} 个位置，其中 ${locationsWithStock} 个位置有库存</div>
                <div class="mt-1"><small>注: 图表显示每个位置的不同商品数量，悬停查看详细信息</small></div>
            `;
            chartParent.appendChild(infoElement);
        } else {
            console.warn('位置数据无效或为空');
            const locationContainer = document.getElementById('locationChart');
            if (locationContainer) {
                const parent = locationContainer.parentNode;
                parent.innerHTML = '<div class="alert alert-warning text-center">位置数据加载失败</div>';
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
            $("#refresh-dashboard").html('<i class="fa-solid fa-spinner fa-spin"></i>&nbsp;刷新中...');
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
                    console.log("开始提取图表数据");
                    
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
                            console.log("找到隐藏数据字段");
                            
                            try {
                                newTrendData = JSON.parse(hiddenTrendData.value);
                                newCategoryData = JSON.parse(hiddenCategoryData.value);
                                newLocationData = JSON.parse(hiddenLocationData.value);
                                newHasStockTrendData = hiddenHasData ? hiddenHasData.value === 'true' : false;
                                
                                updatedFromHiddenFields = true;
                                console.log("已从隐藏字段更新数据");
                            } catch (e) {
                                console.error("解析隐藏字段数据失败:", e);
                            }
                        }
                    } catch (e) {
                        console.error("从隐藏字段获取数据失败:", e);
                    }
                    
                    // 如果没有从隐藏字段获取数据，尝试从脚本标签提取
                    if (!updatedFromHiddenFields) {
                        console.log("尝试从脚本标签提取数据");
                        // 遍历所有脚本标签，查找数据定义
                        for (let i = 0; i < scriptTags.length; i++) {
                            const scriptContent = scriptTags[i].textContent;
                            
                            // 提取trendData
                            if (scriptContent.includes('var trendData') || 
                                scriptContent.includes('trendData =')) {
                                console.log("找到新的趋势数据");
                                
                                // 更强大的正则表达式，支持各种格式
                                const trendRegex = /var\s+trendData\s*=\s*([^;]*);/s;
                                const match = scriptContent.match(trendRegex);
                                
                                if (match && match[1]) {
                                    try {
                                        // 尝试JSON解析
                                        newTrendData = JSON.parse(match[1]);
                                        console.log("成功解析趋势数据");
                                    } catch (e) {
                                        console.error("JSON解析失败，原始内容:", match[1]);
                                        // 尝试eval作为备选（仅在安全环境中）
                                        try {
                                            newTrendData = eval('(' + match[1] + ')');
                                            console.log("通过eval解析趋势数据成功");
                                        } catch (evalError) {
                                            console.error("eval解析也失败:", evalError);
                                        }
                                    }
                                }
                                
                                // 提取hasStockTrendData
                                const hasTrendDataRegex = /var\s+hasStockTrendData\s*=\s*(true|false)/;
                                const hasTrendDataMatch = scriptContent.match(hasTrendDataRegex);
                                if (hasTrendDataMatch && hasTrendDataMatch[1]) {
                                    newHasStockTrendData = hasTrendDataMatch[1] === 'true';
                                    console.log("找到趋势数据标志:", newHasStockTrendData);
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
                    console.error("提取和更新图表数据失败:", e);
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
                    $("#refresh-dashboard").html('<i class="fa-solid fa-sync-alt"></i>&nbsp;刷新数据');
                    
                    // 显示成功消息 - 只在手动刷新时显示
                    toastr.success('仪表盘数据已更新');
                }
                
                // 标记刷新完成
                Dashboard.isRefreshing = false;
            },
            error: function() {
                // 只有在手动刷新时才恢复按钮状态和显示错误消息
                if (!isAuto) {
                    // 恢复刷新按钮
                    $("#refresh-dashboard").html('<i class="fa-solid fa-sync-alt"></i>&nbsp;刷新数据');
                    
                    // 显示错误消息
                    toastr.error('刷新数据失败，请稍后再试');
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
                toastr.info('日期选择功能将在未来版本实现');
            });
        }
    }
}; 
// 当文档加载完成后执行
$(document).ready(function()
{
	console.log('Dashboard.js loaded');

	// 检查必要的依赖是否加载
	if (typeof Chart === 'undefined')
	{
		console.error('Chart.js not loaded');
		showChartError('stock-trend-loading', '无法加载Chart.js库');
		showChartError('category-distribution-loading', '无法加载Chart.js库');
		return;
	}

	if (typeof moment === 'undefined')
	{
		console.error('Moment.js not loaded');
		showChartError('stock-trend-loading', '无法加载Moment.js库');
		showChartError('category-distribution-loading', '无法加载Moment.js库');
		return;
	}

	// 初始化日期范围选择器（如果库已加载）
	if (typeof $.fn.daterangepicker === 'undefined')
	{
		console.error('Daterangepicker not loaded');
		$('#custom-date-range').hide();
		$('.btn-group').append('<span class="ml-2 text-danger">日期选择器加载失败</span>');
	}
	else
	{
		// 设置默认日期范围（最近14天）
		var end = moment();
		var start = moment().subtract(14, 'days');

		$('#custom-date-range').daterangepicker({
			startDate: start,
			endDate: end,
			locale: {
				format: 'YYYY-MM-DD',
				applyLabel: '应用',
				cancelLabel: '取消',
				customRangeLabel: '自定义范围'
			},
			ranges: {
				'今天': [moment(), moment()],
				'昨天': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'最近7天': [moment().subtract(6, 'days'), moment()],
				'最近30天': [moment().subtract(29, 'days'), moment()],
				'本月': [moment().startOf('month'), moment().endOf('month')],
				'上月': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			}
		}, function(start, end, label)
		{
			console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
			updateChartWithDateRange(start, end);
		});

		// 显示当前选择的日期范围
		$('#custom-date-range').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
	}

	// 快捷选择日期范围
	window.setDateRange = function(days)
	{
		var end = moment();
		var start = moment().subtract(days - 1, 'days');
		updateChartWithDateRange(start, end);

		// 如果daterangepicker已加载，更新显示
		if ($.fn.daterangepicker && $('#custom-date-range').data('daterangepicker'))
		{
			$('#custom-date-range').data('daterangepicker').setStartDate(start);
			$('#custom-date-range').data('daterangepicker').setEndDate(end);
		}
	};

	// 更新图表数据
	function updateChartWithDateRange(start, end)
	{
		if (!window.fullStockTrendData || !Array.isArray(window.fullStockTrendData))
		{
			console.error('Full stock trend data not available');
			return;
		}

		var filteredData = window.fullStockTrendData.filter(function(item)
		{
			var itemDate = moment(item.date);
			return itemDate.isBetween(start, end, null, '[]');
		});

		// 如果没有数据，显示错误消息
		if (filteredData.length === 0)
		{
			console.warn('No data available for selected date range');
			showChartError('stock-trend-loading', '所选日期范围内无数据');
			$('#stock-trend-chart').hide();
			return;
		}

		window.stockTrendData = filteredData;
		initStockTrendChart();
	}

	// 检查数据是否存在
	if (!window.stockTrendData || !Array.isArray(window.stockTrendData) || window.stockTrendData.length === 0)
	{
		console.error('Stock trend data not found or empty');
		showChartError('stock-trend-loading', '暂无库存趋势数据');
		return;
	}

	if (!window.categoryDistributionData || !Array.isArray(window.categoryDistributionData) || window.categoryDistributionData.length === 0)
	{
		console.error('Category distribution data not found or empty');
		showChartError('category-distribution-loading', '暂无分类分布数据');
		return;
	}

	// 设置Chart.js全局默认值
	Chart.defaults.global.defaultFontFamily = "'Roboto', 'Helvetica', 'Arial', sans-serif";
	Chart.defaults.global.defaultFontSize = 12;
	Chart.defaults.global.animation.duration = 500;
	Chart.defaults.global.elements.line.tension = 0.1;
	Chart.defaults.global.elements.line.borderWidth = 2;
	Chart.defaults.global.elements.point.radius = 3;

	// 初始化库存趋势图表（使用setTimeout延迟渲染，避免阻塞UI）
	setTimeout(function()
	{
		initStockTrendChart();

		// 延迟200ms后渲染第二个图表
		setTimeout(function()
		{
			initCategoryDistributionChart();
		}, 200);
	}, 100);

	// 更新仪表盘数据
	function refreshDashboardData()
	{
		Grocy.Api.Get('dashboard/data',
			function(result)
			{
				$('#total-items').text(result.stockOverview.total_items);
				$('#total-value').text(result.stockOverview.total_value);
				$('#items-at-risk').text(result.stockOverview.items_at_risk);
				$('#expiring-soon').text(result.stockOverview.products_in_stock);
			},
			function(xhr)
			{
				console.error('Failed to refresh dashboard data:', xhr);
			}
		);
	}

	// 显示图表错误
	function showChartError(loadingId, message)
	{
		$('#' + loadingId).html('<div class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i><p class="mt-2">' + message + '</p></div>');
	}

	// 初始化库存趋势图表
	function initStockTrendChart()
	{
		if (!$('#stock-trend-chart').length) return;

		try
		{
			var ctx = document.getElementById('stock-trend-chart').getContext('2d');

			// 准备数据
			var dates = window.stockTrendData.map(function(item)
			{
				return moment(item.date).format('MM-DD');
			});

			var amounts = window.stockTrendData.map(function(item)
			{
				return parseFloat(item.total_amount);
			});

			// 简化的图表配置
			var stockTrendChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: dates,
					datasets: [{
						label: '库存数量',
						data: amounts,
						borderColor: 'rgb(75, 192, 192)',
						backgroundColor: 'rgba(75, 192, 192, 0.1)',
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero: true
							}
						}]
					},
					legend: {
						display: false
					}
				}
			});

			// 隐藏加载提示，显示图表
			$('#stock-trend-loading').hide();
			$('#stock-trend-chart').show();

			console.log('Stock trend chart initialized');
		} catch (error)
		{
			console.error('Failed to initialize stock trend chart:', error);
			showChartError('stock-trend-loading', '图表初始化失败');
		}
	}

	// 初始化商品分类分布图表
	function initCategoryDistributionChart()
	{
		if (!$('#category-distribution-chart').length) return;

		try
		{
			var ctx = document.getElementById('category-distribution-chart').getContext('2d');

			// 准备数据
			var labels = window.categoryDistributionData.map(function(item)
			{
				return item.name || '未分类';
			});

			var counts = window.categoryDistributionData.map(function(item)
			{
				return parseInt(item.count);
			});

			var colors = [
				'#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56', '#9966FF',
				'#FF9F40', '#8AC249'
			];

			// 简化的图表配置
			var categoryDistributionChart = new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: labels,
					datasets: [{
						data: counts,
						backgroundColor: colors
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: {
						position: 'right',
						labels: {
							boxWidth: 12,
							padding: 10
						}
					},
					cutoutPercentage: 70
				}
			});

			// 隐藏加载提示，显示图表
			$('#category-distribution-loading').hide();
			$('#category-distribution-chart').show();

			console.log('Category distribution chart initialized');
		} catch (error)
		{
			console.error('Failed to initialize category distribution chart:', error);
			showChartError('category-distribution-loading', '图表初始化失败');
		}
	}

	// 初始加载
	refreshDashboardData();

	// 每60秒刷新一次数据
	setInterval(refreshDashboardData, 60000);

}); 
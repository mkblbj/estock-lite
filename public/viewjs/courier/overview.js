/**
 * 快递统计概览页面的JS功能
 */
 
// 确保页面完全加载后再初始化
$(document).ready(function() {
	console.log("页面初始化开始");
	
	// 初始化日期选择器 - 使用setTimeout确保所有库都已加载
	setTimeout(function() {
		try {
			if (typeof $.fn.datetimepicker === 'function') {
				console.log("初始化日期选择器");
				$('#datetimepicker-from, #datetimepicker-to').each(function() {
					$(this).datetimepicker({
						format: 'YYYY-MM-DD',
						locale: moment.locale(),
						useCurrent: false,
						icons: {
							time: 'fa-solid fa-clock',
							date: 'fa-solid fa-calendar',
							up: 'fa-solid fa-arrow-up',
							down: 'fa-solid fa-arrow-down',
							previous: 'fa-solid fa-chevron-left',
							next: 'fa-solid fa-chevron-right',
							today: 'fa-solid fa-calendar-check',
							clear: 'fa-solid fa-trash',
							close: 'fa-solid fa-check'
						}
					});
				});
			} else {
				console.error("datetimepicker 插件未加载，使用普通文本输入框代替");
				// 使用普通文本输入框作为后备方案
				$('#date-filter-from, #date-filter-to').attr('type', 'date');
			}
		} catch (e) {
			console.error("初始化日期选择器时出错:", e);
			// 使用普通文本输入框作为后备方案
			$('#date-filter-from, #date-filter-to').attr('type', 'date');
		}
		
		// 为图表容器设置合适的高度，防止溢出
		$('.card-body').has('canvas').each(function() {
			$(this).css('height', '300px');
		});
		
		// 初始化详细数据表格
		try {
			if (typeof $.fn.DataTable === 'function') {
				console.log("初始化数据表格");
				$("#courier-detailed-table").DataTable({
					"order": [[0, "desc"]],
					"language": IsJsonString(__t('datatables_localization')) ? JSON.parse(__t('datatables_localization')) : {},
					"paging": false,
					responsive: true
				});
			} else {
				console.error("DataTable 插件未加载");
			}
		} catch (e) {
			console.error("初始化数据表格时出错:", e);
		}
		
		// 初始化统计图表
		try {
			if (typeof Chart !== 'undefined') {
				console.log("初始化图表");
				// 使用后端变量注入数据，PHP会将变量转为适当的JSON
				$.ajax({
					url: window.location.href,
					type: 'GET',
					dataType: 'json',
					data: {
						format: 'json'
					},
					success: function(response) {
						// 设置数据属性
						$("#courier-statistics-chart").attr("data-statistics", JSON.stringify(response.statistics || []));
						$("#courier-pie-chart").attr("data-totals", JSON.stringify(response.totalsByType || {}));
						
						// 初始化图表
						RenderCharts();
					}
				});
			} else {
				console.error("Chart.js 库未正确加载");
				$("#courier-statistics-chart, #courier-pie-chart").each(function() {
					$(this).after('<div class="alert alert-warning">图表加载失败: Chart.js 库未正确加载</div>');
				});
			}
		} catch (e) {
			console.error("初始化图表时出错:", e);
		}
	}, 500); // 延迟500毫秒，确保所有库都已加载
	
	// 应用筛选
	$("#filter-apply-button").on("click", function() {
		const fromDate = $("#date-filter-from").val();
		const toDate = $("#date-filter-to").val();
		const interval = $("#interval-filter").val();
		
		window.location.href = `?from_date=${fromDate}&to_date=${toDate}&interval=${interval}`;
	});
});

// 渲染图表
function RenderCharts() {
	// 准备数据
	const barChartData = PrepareBarChartData();
	const pieChartData = PreparePieChartData();
	
	// 渲染条形图
	RenderBarChart(barChartData);
	
	// 渲染饼图
	RenderPieChart(pieChartData);
}

// 准备条形图数据
function PrepareBarChartData() {
	const statistics = JSON.parse($("#courier-statistics-chart").attr("data-statistics") || "[]");
	const labels = [];
	const datasets = [];
	
	// 获取所有快递类型
	const courierTypes = new Map();
	statistics.forEach(stat => {
		labels.push(stat.interval_key);
		
		stat.couriers.forEach(courier => {
			if (!courierTypes.has(courier.courier_id)) {
				courierTypes.set(courier.courier_id, {
					id: courier.courier_id,
					name: courier.courier_name,
					data: []
				});
			}
		});
	});
	
	// 填充数据
	statistics.forEach(stat => {
		courierTypes.forEach((type, id) => {
			const courier = stat.couriers.find(c => c.courier_id === id);
			type.data.push(courier ? courier.count : 0);
		});
	});
	
	// 创建数据集
	const colors = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#858796", "#5a5c69"];
	let i = 0;
	
	courierTypes.forEach((type) => {
		datasets.push({
			label: type.name,
			data: type.data,
			backgroundColor: colors[i % colors.length],
			borderColor: colors[i % colors.length],
			borderWidth: 1
		});
		i++;
	});
	
	return {
		labels: labels,
		datasets: datasets
	};
}

// 准备饼图数据
function PreparePieChartData() {
	const totalsByType = JSON.parse($("#courier-pie-chart").attr("data-totals") || "{}");
	const labels = [];
	const data = [];
	const backgroundColor = [];
	
	const colors = [
		"#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#858796", "#5a5c69",
		"#8e5ea2", "#3cba9f", "#e8c3b9", "#c45850", "#ff9f40", "#ff6384", "#4bc0c0"
	];
	
	// 处理数据，确保couriers是数组
	if (totalsByType.couriers && Array.isArray(totalsByType.couriers)) {
		totalsByType.couriers.forEach((courier, i) => {
			labels.push(courier.courier_name);
			data.push(courier.total_count);
			backgroundColor.push(colors[i % colors.length]);
		});
	} else if (totalsByType.couriers) {
		// 如果不是数组，可能是对象，尝试转换
		const couriersArray = Object.values(totalsByType.couriers);
		couriersArray.forEach((courier, i) => {
			if (courier.courier_name) {
				labels.push(courier.courier_name);
				data.push(courier.total_count);
				backgroundColor.push(colors[i % colors.length]);
			}
		});
	}
	
	return {
		labels: labels,
		datasets: [{
			data: data,
			backgroundColor: backgroundColor
		}]
	};
}

// 渲染条形图
function RenderBarChart(data) {
	// 检查Chart对象是否存在
	if (typeof Chart === 'undefined') {
		console.error("Chart.js 库未正确加载，无法渲染条形图");
		return;
	}

	const statistics = JSON.parse($("body").attr("data-statistics") || "[]");
	
	const ctx = document.getElementById("courier-statistics-chart").getContext("2d");
	const myChart = new Chart(ctx, {
		type: "bar",
		data: data,
		options: {
			scales: {
				yAxes: [{
					ticks: {
						beginAtZero: true,
						precision: 0
					},
					stacked: true
				}],
				xAxes: [{
					stacked: true
				}]
			},
			legend: {
				position: "bottom",
				labels: {
					boxWidth: 12, // 减小图例框的大小
					padding: 8    // 减小图例项的内边距
				}
			},
			responsive: true,
			maintainAspectRatio: false, // 允许图表高度自适应
			tooltips: {
				mode: "index",
				intersect: false,
				callbacks: {
					label: function(tooltipItem, data) {
						let label = data.datasets[tooltipItem.datasetIndex].label || "";
						if (label) {
							label += ": ";
						}
						label += tooltipItem.yLabel;
						return label;
					}
				}
			}
		}
	});
}

// 渲染饼图
function RenderPieChart(data) {
	// 检查Chart对象是否存在
	if (typeof Chart === 'undefined') {
		console.error("Chart.js 库未正确加载，无法渲染饼图");
		return;
	}

	const ctx = document.getElementById("courier-pie-chart").getContext("2d");
	const myChart = new Chart(ctx, {
		type: "pie",
		data: data,
		options: {
			responsive: true,
			maintainAspectRatio: false, // 允许图表高度自适应
			legend: {
				position: "bottom",
				labels: {
					boxWidth: 12, // 减小图例框的大小
					padding: 8    // 减小图例项的内边距
				}
			},
			tooltips: {
				callbacks: {
					label: function(tooltipItem, data) {
						const dataset = data.datasets[tooltipItem.datasetIndex];
						const total = dataset.data.reduce((acc, val) => acc + val, 0);
						const value = dataset.data[tooltipItem.index];
						const percentage = Math.round((value / total) * 100);
						return data.labels[tooltipItem.index] + ": " + value + " (" + percentage + "%)";
					}
				}
			}
		}
	});
} 
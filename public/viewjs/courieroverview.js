/**
 * 快递统计概览页面的JS功能
 */
 
$(function() {
	// 初始化日期选择器
	$(".datepicker").datepicker({
		format: "yyyy-mm-dd",
		autoclose: true,
		language: moment.locale(),
		todayHighlight: true,
		orientation: "bottom auto",
		calendarWeeks: true
	});
	
	// 初始化统计图表
	RenderCharts();
	
	// 初始化详细数据表格
	$("#courier-detailed-table").DataTable({
		"order": [[0, "desc"]],
		"language": datatables_i18n_config,
		"paging": false,
		responsive: true
	});
	
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
	
	if (totalsByType.couriers) {
		totalsByType.couriers.forEach((courier, i) => {
			labels.push(courier.courier_name);
			data.push(courier.total_count);
			backgroundColor.push(colors[i % colors.length]);
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
				position: "bottom"
			},
			responsive: true,
			maintainAspectRatio: false,
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
	const ctx = document.getElementById("courier-pie-chart").getContext("2d");
	const myChart = new Chart(ctx, {
		type: "pie",
		data: data,
		options: {
			responsive: true,
			maintainAspectRatio: false,
			legend: {
				position: "bottom"
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

// 设置图表数据
$(document).ready(function() {
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
}); 
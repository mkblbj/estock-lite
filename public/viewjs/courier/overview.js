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
				
				// 设置默认日期值
				const today = moment();
				const thirtyDaysAgo = moment().subtract(30, 'days');
				
				// 如果日期字段为空或格式不正确，设置默认值
				if (!isValidDate($("#date-filter-from").val())) {
					$("#date-filter-from").val(thirtyDaysAgo.format('YYYY-MM-DD'));
				}
				
				if (!isValidDate($("#date-filter-to").val())) {
					$("#date-filter-to").val(today.format('YYYY-MM-DD'));
				}
				
				// 确保移除旧的实例
				try {
					$('#datetimepicker-from, #datetimepicker-to').datetimepicker('destroy');
				} catch (e) {
					console.log("没有旧的datetimepicker实例需要销毁");
				}
				
				// 初始化日期选择器
				$('#datetimepicker-from, #datetimepicker-to').each(function() {
					$(this).datetimepicker({
						format: 'YYYY-MM-DD',
						locale: moment.locale(),
						useCurrent: false,
						showTodayButton: true,
						showClear: true,
						showClose: true,
						keepOpen: true, // 保持日历打开直到选择日期
						debug: true, // 打开调试模式
						widgetPositioning: {
							horizontal: 'auto',
							vertical: 'bottom'
						},
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
				
				// 当日期选择器改变时设置值
				$("#datetimepicker-from").on("change.datetimepicker", function (e) {
					console.log("From日期选择器变更", e.date ? e.date.format('YYYY-MM-DD') : "日期被清除");
					if (e.date) {
						$('#datetimepicker-to').datetimepicker('minDate', e.date);
					} else {
						// 如果日期被清除，重置为默认值
						$("#date-filter-from").val(thirtyDaysAgo.format('YYYY-MM-DD'));
						$('#datetimepicker-from').datetimepicker('date', thirtyDaysAgo);
					}
				});
				
				$("#datetimepicker-to").on("change.datetimepicker", function (e) {
					console.log("To日期选择器变更", e.date ? e.date.format('YYYY-MM-DD') : "日期被清除");
					if (e.date) {
						$('#datetimepicker-from').datetimepicker('maxDate', e.date);
					} else {
						// 如果日期被清除，重置为默认值
						$("#date-filter-to").val(today.format('YYYY-MM-DD'));
						$('#datetimepicker-to').datetimepicker('date', today);
					}
				});
				
				// 确保日历图标点击可以正常打开选择器 - 直接在模板中处理
				
				// 设置最小/最大日期限制
				const fromDate = moment($("#date-filter-from").val(), 'YYYY-MM-DD');
				const toDate = moment($("#date-filter-to").val(), 'YYYY-MM-DD');
				
				if (fromDate.isValid() && toDate.isValid()) {
					$('#datetimepicker-to').datetimepicker('minDate', fromDate);
					$('#datetimepicker-from').datetimepicker('maxDate', toDate);
				}
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
				
				// 检查页面中是否已有数据
				const existingStats = $("#courier-statistics-chart").attr("data-statistics");
				const existingTotals = $("#courier-pie-chart").attr("data-totals");
				
				console.log("页面现有数据检查:", {
					"统计数据存在": !!existingStats,
					"类型统计数据存在": !!existingTotals
				});
				
				// 如果页面中已经有数据，直接使用
				if (existingStats && existingTotals) {
					console.log("使用页面已有数据渲染图表");
					RenderCharts();
					return;
				}
				
				// 使用后端变量注入数据，PHP会将变量转为适当的JSON
				$.ajax({
					url: window.location.href,
					type: 'GET',
					dataType: 'json',
					data: {
						format: 'json',
						from_date: normalizeDate($("#date-filter-from").val(), -30), // 默认30天前
						to_date: normalizeDate($("#date-filter-to").val()), // 默认今天
						interval: $("#interval-filter").val() || 'day'
					},
					success: function(response) {
						console.log("AJAX成功，收到数据:", response);
						
						// 输出更详细的调试信息
						if (response.debug) {
							console.log("服务器调试信息:", response.debug);
						}
						
						// 验证响应数据结构
						if (!response) {
							console.error("响应数据为空");
							showNoDataMessage("服务器返回空数据");
							return;
						}
						
						if (!response.statistics) {
							console.error("响应数据缺少statistics字段:", response);
							showNoDataMessage("服务器返回的数据格式不正确: 缺少统计数据");
							return;
						}
						
						if (!response.totalsByType) {
							console.error("响应数据缺少totalsByType字段:", response);
							showNoDataMessage("服务器返回的数据格式不正确: 缺少类型统计数据");
							return;
						}
						
						// 设置数据属性
						$("#courier-statistics-chart").attr("data-statistics", JSON.stringify(response.statistics || []));
						$("#courier-pie-chart").attr("data-totals", JSON.stringify(response.totalsByType || {}));
						
						// 显示服务器端任何警告或错误
						if (response.debug && response.debug.warnings) {
							response.debug.warnings.forEach(warning => {
								console.warn("服务器警告:", warning);
							});
						}
						
						// 检查是否有数据
						if (response.statistics.length === 0) {
							console.warn("没有统计数据");
							$("#courier-statistics-chart").after('<div class="alert alert-info">没有符合条件的快递统计数据</div>');
						} else {
							console.log(`接收到${response.statistics.length}个时间段的统计数据`);
						}
						
						if (!response.totalsByType.couriers || response.totalsByType.couriers.length === 0) {
							console.warn("没有类型统计数据");
							$("#courier-pie-chart").after('<div class="alert alert-info">没有符合条件的快递类型数据</div>');
						} else {
							console.log(`接收到${response.totalsByType.couriers.length}个快递类型的统计数据`);
						}
						
						// 统计数据和类型数据都为空时，显示提示
						if (response.statistics.length === 0 && 
							(!response.totalsByType.couriers || response.totalsByType.couriers.length === 0)) {
							showNoDataMessage("没有统计数据，请尝试以下方法：<br>" +
								"1. 检查筛选条件中的日期范围<br>" +
								"2. 确保有活跃的快递类型<br>" +
								"3. 添加快递记录数据");
							return;
						}
						
						// 初始化图表
						RenderCharts();
					},
					error: function(xhr, status, error) {
						console.error("AJAX请求失败:", status, error);
						console.error("响应内容:", xhr.responseText);
						
						// 显示错误信息
						showNoDataMessage("数据加载失败: " + (error || "网络错误"));
						
						// 尝试直接从API获取数据
						console.log("尝试通过API获取数据");
						$.ajax({
							url: "/api/courier/statistics",
							type: "GET",
							dataType: "json",
							data: {
								from_date: normalizeDate($("#date-filter-from").val(), -30),
								to_date: normalizeDate($("#date-filter-to").val()),
								interval: $("#interval-filter").val() || 'day'
							},
							success: function(apiResponse) {
								console.log("直接API请求成功:", apiResponse);
								
								if (!apiResponse || apiResponse.length === 0) {
									console.warn("API没有返回数据");
									showNoDataMessage("当前时间范围内没有快递统计数据，请调整时间范围或添加数据");
									return;
								}
								
								// 构造模拟的响应数据
								var mockResponse = {
									statistics: apiResponse,
									totalsByType: {
										couriers: [],
										total: 0
									}
								};
								
								// 统计每种类型的总数
								var typeMap = {};
								apiResponse.forEach(function(day) {
									day.couriers.forEach(function(courier) {
										if (!typeMap[courier.courier_id]) {
											typeMap[courier.courier_id] = {
												courier_id: courier.courier_id,
												courier_name: courier.courier_name,
												total_count: 0
											};
										}
										typeMap[courier.courier_id].total_count += parseInt(courier.count);
										mockResponse.totalsByType.total += parseInt(courier.count);
									});
								});
								
								mockResponse.totalsByType.couriers = Object.values(typeMap);
								
								// 使用模拟数据渲染图表
								$("#courier-statistics-chart").attr("data-statistics", JSON.stringify(mockResponse.statistics));
								$("#courier-pie-chart").attr("data-totals", JSON.stringify(mockResponse.totalsByType));
								
								// 初始化图表
								RenderCharts();
							},
							error: function(apiXhr, apiStatus, apiError) {
								console.error("直接API请求也失败:", apiStatus, apiError);
								showNoDataMessage("无法加载数据: " + (apiError || "未知错误") + "。请确保已添加快递数据。");
							}
						});
					}
				});
			} else {
				console.error("Chart.js 库未正确加载");
				showNoDataMessage("图表加载失败: Chart.js 库未正确加载");
			}
		} catch (e) {
			console.error("初始化图表时出错:", e);
			showNoDataMessage("图表初始化错误: " + e.message);
		}
	}, 500); // 延迟500毫秒，确保所有库都已加载
	
	// 应用筛选
	$("#filter-apply-button").on("click", function() {
		let fromDate = $("#date-filter-from").val();
		let toDate = $("#date-filter-to").val();
		const interval = $("#interval-filter").val();
		
		// 检查日期格式
		console.log("筛选器日期值:", {
			"原始from_date": fromDate,
			"原始to_date": toDate,
			"interval": interval
		});
		
		// 验证日期格式
		if (!isValidDate(fromDate)) {
			fromDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
			$("#date-filter-from").val(fromDate);
		}
		
		if (!isValidDate(toDate)) {
			toDate = moment().format('YYYY-MM-DD');
			$("#date-filter-to").val(toDate);
		}
		
		// 关闭模态对话框
		$("#filterModal").modal('hide');
		
		// 重定向到带有新参数的页面
		window.location.href = window.location.pathname + 
			"?from_date=" + encodeURIComponent(fromDate) + 
			"&to_date=" + encodeURIComponent(toDate) + 
			"&interval=" + encodeURIComponent(interval);
	});
	
	// 快捷日期范围选择
	$(".date-range-preset").on("click", function(e) {
		// 阻止冒泡，防止下拉菜单关闭
		e.stopPropagation();
		e.preventDefault();
		
		const range = $(this).data("range");
		let fromDate, toDate;
		
		switch(range) {
			case "today":
				fromDate = moment().format("YYYY-MM-DD");
				toDate = moment().format("YYYY-MM-DD");
				break;
			case "yesterday":
				fromDate = moment().subtract(1, "days").format("YYYY-MM-DD");
				toDate = fromDate;
				break;
			case "this-week":
				fromDate = moment().startOf("week").format("YYYY-MM-DD");
				toDate = moment().endOf("week").format("YYYY-MM-DD");
				break;
			case "last-week":
				fromDate = moment().subtract(1, "weeks").startOf("week").format("YYYY-MM-DD");
				toDate = moment().subtract(1, "weeks").endOf("week").format("YYYY-MM-DD");
				break;
			case "this-month":
				fromDate = moment().startOf("month").format("YYYY-MM-DD");
				toDate = moment().endOf("month").format("YYYY-MM-DD");
				break;
			case "last-month":
				fromDate = moment().subtract(1, "months").startOf("month").format("YYYY-MM-DD");
				toDate = moment().subtract(1, "months").endOf("month").format("YYYY-MM-DD");
				break;
			case "this-year":
				fromDate = moment().startOf("year").format("YYYY-MM-DD");
				toDate = moment().endOf("year").format("YYYY-MM-DD");
				break;
			default:
				return;
		}
		
		console.log("快速选择日期范围:", range, fromDate, toDate);
		
		// 设置日期选择器的值
		$("#date-filter-from").val(fromDate);
		$("#date-filter-to").val(toDate);
		
		// 手动触发日期选择器更新
		try {
			// 使用setTimeout避免可能的冲突
			setTimeout(function() {
				$('#datetimepicker-from').datetimepicker('date', moment(fromDate));
				$('#datetimepicker-to').datetimepicker('date', moment(toDate));
			}, 10);
		} catch (e) {
			console.error("更新日期选择器失败:", e);
		}
		
		// 视觉反馈
		$(this).addClass("active").siblings().removeClass("active");
		
		// 自动调整Interval
		if (range === "today" || range === "yesterday") {
			$("#interval-filter").val("day");
		} else if (range === "this-week" || range === "last-week") {
			$("#interval-filter").val("day");
		} else if (range === "this-month" || range === "last-month") {
			$("#interval-filter").val("day");
		} else if (range === "this-year") {
			$("#interval-filter").val("month");
		}
		
		// 高亮显示应用按钮
		$("#filter-apply-button").addClass("btn-success").removeClass("btn-primary").delay(100).queue(function(next){
			$(this).removeClass("btn-success").addClass("btn-primary");
			next();
		});
	});
});

// 检查日期格式是否有效
function isValidDate(dateString) {
	if (!dateString) return false;
	
	// 尝试使用moment解析日期
	const parsed = moment(dateString);
	if (!parsed.isValid()) return false;
	
	// 确保格式为YYYY-MM-DD
	if (!/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return false;
	
	return true;
}

// 显示无数据消息的辅助函数
function showNoDataMessage(message) {
	// 移除所有现有的信息提示
	$(".alert-no-data").remove();
	
	// 添加新提示
	const alertHTML = `
		<div class="row mt-3 alert-no-data">
			<div class="col-12">
				<div class="alert alert-warning">
					<i class="fa-solid fa-triangle-exclamation"></i> ${message}
				</div>
			</div>
		</div>
	`;
	
	// 在图表容器后插入提示
	$("#courier-statistics-chart").closest(".row").after(alertHTML);
	
	console.warn("显示无数据消息:", message);
}

// 渲染图表
function RenderCharts() {
	// 准备数据
	const barChartData = PrepareBarChartData();
	const pieChartData = PreparePieChartData();
	
	// 检查是否有足够的数据来渲染图表
	if (!barChartData.labels || barChartData.labels.length === 0 || 
		!barChartData.datasets || barChartData.datasets.length === 0) {
		console.warn("没有足够的数据来渲染条形图");
		$("#courier-statistics-chart").after('<div class="alert alert-info">没有足够的数据来渲染图表</div>');
	} else {
		// 渲染条形图
		RenderBarChart(barChartData);
	}
	
	// 检查是否有足够的数据来渲染饼图
	if (!pieChartData.labels || pieChartData.labels.length === 0 || 
		!pieChartData.datasets || pieChartData.datasets[0].data.length === 0) {
		console.warn("没有足够的数据来渲染饼图");
		$("#courier-pie-chart").after('<div class="alert alert-info">没有足够的数据来渲染图表</div>');
	} else {
		// 渲染饼图
		RenderPieChart(pieChartData);
	}
}

// 准备条形图数据
function PrepareBarChartData() {
	try {
		const statistics = JSON.parse($("#courier-statistics-chart").attr("data-statistics") || "[]");
		console.log("准备条形图数据，统计数据:", statistics);
		
		const labels = [];
		const datasets = [];
		
		// 如果没有数据，返回空结构
		if (!statistics || statistics.length === 0) {
			console.warn("没有统计数据可用于条形图");
			return { labels: [], datasets: [] };
		}
		
		// 获取所有快递类型
		const courierTypes = new Map();
		statistics.forEach(stat => {
			labels.push(stat.interval_key);
			
			if (stat.couriers && Array.isArray(stat.couriers)) {
				stat.couriers.forEach(courier => {
					if (!courierTypes.has(courier.courier_id)) {
						courierTypes.set(courier.courier_id, {
							id: courier.courier_id,
							name: courier.courier_name,
							data: []
						});
					}
				});
			}
		});
		
		// 填充数据
		statistics.forEach(stat => {
			courierTypes.forEach((type, id) => {
				if (stat.couriers && Array.isArray(stat.couriers)) {
					const courier = stat.couriers.find(c => c.courier_id === id);
					type.data.push(courier ? courier.count : 0);
				} else {
					type.data.push(0);
				}
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
	} catch (e) {
		console.error("准备条形图数据时出错:", e);
		return { labels: [], datasets: [] };
	}
}

// 准备饼图数据
function PreparePieChartData() {
	try {
		const totalsByType = JSON.parse($("#courier-pie-chart").attr("data-totals") || "{}");
		console.log("准备饼图数据，类型统计:", totalsByType);
		
		const labels = [];
		const data = [];
		const backgroundColor = [];
		
		const colors = [
			"#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#858796", "#5a5c69",
			"#8e5ea2", "#3cba9f", "#e8c3b9", "#c45850", "#ff9f40", "#ff6384", "#4bc0c0"
		];
		
		// 检查数据结构
		if (!totalsByType || !totalsByType.couriers) {
			console.warn("没有类型统计数据可用于饼图");
			return {
				labels: [],
				datasets: [{
					data: [],
					backgroundColor: []
				}]
			};
		}
		
		// 处理数据，确保couriers是数组
		if (Array.isArray(totalsByType.couriers)) {
			totalsByType.couriers.forEach((courier, i) => {
				if (courier && courier.courier_name) {
					labels.push(courier.courier_name);
					data.push(courier.total_count);
					backgroundColor.push(colors[i % colors.length]);
				}
			});
		} else if (typeof totalsByType.couriers === 'object') {
			// 如果不是数组，可能是对象，尝试转换
			const couriersArray = Object.values(totalsByType.couriers);
			couriersArray.forEach((courier, i) => {
				if (courier && courier.courier_name) {
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
	} catch (e) {
		console.error("准备饼图数据时出错:", e);
		return {
			labels: [],
			datasets: [{
				data: [],
				backgroundColor: []
			}]
		};
	}
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

// 处理日期参数，确保有效格式
function normalizeDate(dateString, defaultDayOffset) {
	// 如果有有效日期，直接返回
	if (isValidDate(dateString)) {
		return dateString;
	}
	
	// 否则生成默认日期
	if (defaultDayOffset !== undefined) {
		return moment().add(defaultDayOffset, 'days').format('YYYY-MM-DD');
	} else {
		return moment().format('YYYY-MM-DD');
	}
} 
/**
 * 快递统计概览页面的JS功能
 */

// 存储实际应用的筛选值（这些是实际用于数据筛选的值）
let appliedFromDate = "";
let appliedToDate = "";
let appliedInterval = "day";

// 用于存储模态框中临时选择的变量
let tempFromDate = "";
let tempToDate = "";
let tempInterval = "";

// 确保页面完全加载后再初始化
$(document).ready(function() {
	console.log("页面初始化开始");
	
	// 初始化日期选择器 - 使用setTimeout确保所有库都已加载
	setTimeout(function() {
		try {
			if (typeof $.fn.datetimepicker === 'function') {
				console.log("初始化日期选择器");
				
				// 设置默认日期值 - 本周
				const startOfWeek = moment().startOf('week');
				const endOfWeek = moment().endOf('week');
				
				// 初始化已应用的筛选值
				appliedFromDate = startOfWeek.format('YYYY-MM-DD');
				appliedToDate = endOfWeek.format('YYYY-MM-DD');
				appliedInterval = "day";
				
				// 设置默认日期，确保日期选择框不为空
				$("#date-filter-from").val(appliedFromDate);
				$("#date-filter-to").val(appliedToDate);
				
				// 确保移除旧的实例
				try {
					$('#datetimepicker-from, #datetimepicker-to').datetimepicker('destroy');
				} catch (e) {
					console.log("没有旧的datetimepicker实例需要销毁");
				}
				
				// 初始化日期选择器 - 确保使用正确的日期格式
				$('#datetimepicker-from, #datetimepicker-to').each(function() {
					$(this).datetimepicker({
						format: 'YYYY-MM-DD',  // 严格使用年月日格式
						locale: moment.locale(),
						useCurrent: false,
						showTodayButton: true,
						showClear: true,
						showClose: true,
						keepOpen: false, // 选择日期后自动关闭日历选择框
						debug: false, // 关闭调试模式
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
				
				// 当日期选择器改变时确保使用正确的日期格式
				$("#datetimepicker-from").on("change.datetimepicker", function (e) {
					console.log("From日期选择器变更", e.date ? e.date.format('YYYY-MM-DD') : "日期被清除");
					if (e.date) {
						$('#datetimepicker-to').datetimepicker('minDate', e.date);
						// 确保日期格式一致，只包含年月日部分
						const dateStr = e.date.clone().startOf('day').format('YYYY-MM-DD');
						$("#date-filter-from").val(dateStr);
					} else {
						// 如果日期被清除，重置为默认值
						$("#date-filter-from").val(startOfWeek.format('YYYY-MM-DD'));
						$('#datetimepicker-from').datetimepicker('date', startOfWeek);
					}
				});
				
				$("#datetimepicker-to").on("change.datetimepicker", function (e) {
					console.log("To日期选择器变更", e.date ? e.date.format('YYYY-MM-DD') : "日期被清除");
					if (e.date) {
						$('#datetimepicker-from').datetimepicker('maxDate', e.date);
						// 确保日期格式一致，只包含年月日部分
						const dateStr = e.date.clone().startOf('day').format('YYYY-MM-DD');
						$("#date-filter-to").val(dateStr);
					} else {
						// 如果日期被清除，重置为默认值
						$("#date-filter-to").val(endOfWeek.format('YYYY-MM-DD'));
						$('#datetimepicker-to').datetimepicker('date', endOfWeek);
					}
				});
				
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
		
		// 注释掉设置图表容器高度的代码，这可能导致双滚动条问题
		// $('.card-body').has('canvas').each(function() {
		//  $(this).css('height', '300px');
		// });
		
		// 初始化统计图表 - 页面加载时自动获取本周数据
		try {
			if (typeof Chart !== 'undefined') {
				console.log("初始化图表 - 加载本周数据");
				
				// 页面加载时自动获取数据
				fetchAndUpdateChartData();
			} else {
				console.error("Chart.js 库未正确加载");
				showNoDataMessage("图表加载失败: Chart.js 库未正确加载");
			}
		} catch (e) {
			console.error("初始化图表时出错:", e);
			showNoDataMessage("图表初始化错误: " + e.message);
		}
	}, 500); // 延迟500毫秒，确保所有库都已加载
	
	// 打开筛选模态框时初始化临时值
	$("#filterModal").on("show.bs.modal", function() {
		// 初始化临时值为当前应用的值
		tempFromDate = appliedFromDate;
		tempToDate = appliedToDate;
		tempInterval = appliedInterval;
		
		console.log("打开筛选模态框，当前应用的值:", appliedFromDate, appliedToDate, appliedInterval);
		
		// 在模态框中显示当前值
		try {
			// 直接设置输入框的显示值（不是value属性）
			$("#date-filter-from").val(tempFromDate);
			$("#date-filter-to").val(tempToDate);
			$("#interval-filter").val(tempInterval);
			
			// 设置日期选择器显示
			$('#datetimepicker-from').datetimepicker('date', moment(tempFromDate).startOf('day'));
			$('#datetimepicker-to').datetimepicker('date', moment(tempToDate).startOf('day'));
			
			// 重置快速选择按钮的活动状态
			$(".date-range-preset").removeClass("active");
		} catch (e) {
			console.error("设置模态框初始值失败:", e);
		}
	});
	
	// 处理模态框关闭事件（取消、关闭按钮X或点击背景）
	$("#filterModal").on("hidden.bs.modal", function() {
		console.log("筛选模态框关闭，丢弃所有未应用的更改");
		
		// 重置为应用的值
		$("#date-filter-from").val(appliedFromDate);
		$("#date-filter-to").val(appliedToDate);
		$("#interval-filter").val(appliedInterval);
	});
	
	// 取消筛选时关闭模态框
	$("#filterModal .btn-secondary, #filterModal .close").on("click", function() {
		console.log("取消筛选，不应用任何更改");
		$("#filterModal").modal('hide');
	});
	
	// 应用筛选按钮点击事件
	$("#filter-apply-button").on("click", function() {
		console.log("应用筛选条件:", tempFromDate, tempToDate, tempInterval);
		
		// 验证临时日期格式
		if (!isValidDate(tempFromDate)) {
			tempFromDate = moment().startOf('week').format('YYYY-MM-DD');
		}
		
		if (!isValidDate(tempToDate)) {
			tempToDate = moment().endOf('week').format('YYYY-MM-DD');
		}
		
		// 更新已应用的筛选值
		appliedFromDate = tempFromDate;
		appliedToDate = tempToDate;
		appliedInterval = tempInterval;
		
		// 关闭模态对话框
		$("#filterModal").modal('hide');
		
		// 获取新的数据并更新图表
		fetchAndUpdateChartData();
	});
	
	// 日期选择器变更事件 - 仅更新临时值
	$("#datetimepicker-from").on("change.datetimepicker", function(e) {
		if (e.date) {
			// 更新最小日期限制
			$('#datetimepicker-to').datetimepicker('minDate', e.date);
			// 更新临时值
			tempFromDate = e.date.clone().startOf('day').format('YYYY-MM-DD');
			console.log("临时From日期更新为:", tempFromDate);
		}
	});
	
	$("#datetimepicker-to").on("change.datetimepicker", function(e) {
		if (e.date) {
			// 更新最大日期限制
			$('#datetimepicker-from').datetimepicker('maxDate', e.date);
			// 更新临时值
			tempToDate = e.date.clone().startOf('day').format('YYYY-MM-DD');
			console.log("临时To日期更新为:", tempToDate);
		}
	});
	
	// 快捷日期范围选择 - 仅更新临时值
	$(".date-range-preset").on("click", function(e) {
		// 阻止冒泡，防止下拉菜单关闭
		e.stopPropagation();
		e.preventDefault();
		
		const range = $(this).data("range");
		
		switch(range) {
			case "today":
				tempFromDate = moment().format("YYYY-MM-DD");
				tempToDate = moment().format("YYYY-MM-DD");
				tempInterval = "day";
				break;
			case "this-week":
				tempFromDate = moment().startOf("week").format("YYYY-MM-DD");
				tempToDate = moment().endOf("week").format("YYYY-MM-DD");
				tempInterval = "day";
				break;
			case "this-month":
				tempFromDate = moment().startOf("month").format("YYYY-MM-DD");
				tempToDate = moment().endOf("month").format("YYYY-MM-DD");
				tempInterval = "day";
				break;
			case "last-month":
				const lastMonth = moment().subtract(1, "months");
				tempFromDate = lastMonth.startOf("month").format("YYYY-MM-DD");
				tempToDate = lastMonth.endOf("month").format("YYYY-MM-DD");
				tempInterval = "day";
				break;
			case "this-year":
				tempFromDate = moment().startOf("year").format("YYYY-MM-DD");
				tempToDate = moment().endOf("year").format("YYYY-MM-DD");
				tempInterval = "month";
				break;
			default:
				return;
		}
		
		console.log("快速选择临时日期范围:", range, tempFromDate, tempToDate, tempInterval);
		
		// 视觉反馈
		$(this).addClass("active").siblings().removeClass("active");
		
		// 设置界面元素显示临时值
		try {
			// 更新输入框显示
			$("#date-filter-from").val(tempFromDate);
			$("#date-filter-to").val(tempToDate);
			$("#interval-filter").val(tempInterval);
			
			// 更新日期选择器显示
			$('#datetimepicker-from').datetimepicker('date', moment(tempFromDate).startOf('day'));
			$('#datetimepicker-to').datetimepicker('date', moment(tempToDate).startOf('day'));
			
			// 设置日期选择器的最小/最大限制
			$('#datetimepicker-to').datetimepicker('minDate', moment(tempFromDate).startOf('day'));
			$('#datetimepicker-from').datetimepicker('maxDate', moment(tempToDate).startOf('day'));
		} catch (e) {
			console.error("更新模态框临时显示失败:", e);
		}
		
		// 高亮显示应用按钮
		$("#filter-apply-button").addClass("btn-success").removeClass("btn-primary").delay(100).queue(function(next){
			$(this).removeClass("btn-success").addClass("btn-primary");
			next();
		});
	});
	
	// 手动刷新按钮点击事件 - 使用已应用的筛选条件刷新数据
	$("#refresh-data-button").on("click", function() {
		console.log("刷新按钮点击 - 使用已应用的筛选条件");
		fetchAndUpdateChartData();
	});
});

// 核心函数：获取数据并更新图表 - 使用已应用的筛选条件
function fetchAndUpdateChartData() {
	// 使用已应用的筛选条件而不是直接从输入框读取
	let fromDate = appliedFromDate;
	let toDate = appliedToDate;
	const interval = appliedInterval;
	
	console.log("获取数据使用的筛选条件:", fromDate, toDate, interval);
	
	// 验证日期格式
	if (!isValidDate(fromDate)) {
		fromDate = moment().startOf('week').format('YYYY-MM-DD');
		appliedFromDate = fromDate;
	}
	
	if (!isValidDate(toDate)) {
		toDate = moment().endOf('week').format('YYYY-MM-DD');
		appliedToDate = toDate;
	}
	
	// 更新输入框显示值，以确保UI与应用的筛选条件一致
	$("#date-filter-from").val(fromDate);
	$("#date-filter-to").val(toDate);
	$("#interval-filter").val(interval);
	
	// 显示加载中状态
	$("#refresh-data-button").html('<i class="fa-solid fa-spinner fa-spin"></i> 加载中...');
	$(".card-body").addClass("loading");
	
	// 发送AJAX请求获取最新数据
	$.ajax({
		url: window.location.pathname,
		type: 'GET',
		dataType: 'json',
		data: {
			format: 'json',
			from_date: fromDate,
			to_date: toDate,
			interval: interval
		},
		success: function(response) {
			console.log("数据加载成功:", response);
			
			// 清除所有现有的警告和图表
			clearAllCharts();
			
			// 更新页面标题中的日期显示
			$(".title small.text-muted").text(fromDate + " - " + toDate);
			
			// 更新数据属性
			$("#courier-statistics-chart").attr("data-statistics", JSON.stringify(response.statistics || []));
			$("#courier-pie-chart").attr("data-totals", JSON.stringify(response.totalsByType || {}));
			
			// 更新图表标题中的interval显示
			let intervalText = "Day";
			if (interval === "month") intervalText = "Month";
			if (interval === "year") intervalText = "Year";
			$(".card-header:contains('Courier statistics by')").html(
				'<i class="fa-solid fa-chart-bar"></i> Courier statistics by ' + intervalText
			);
			
			// 更新URL，但不刷新页面
			const newUrl = window.location.pathname + 
				"?from_date=" + encodeURIComponent(fromDate) + 
				"&to_date=" + encodeURIComponent(toDate) + 
				"&interval=" + encodeURIComponent(interval);
			window.history.pushState({path: newUrl}, '', newUrl);
			
			// 检查是否有数据
			if (!response.statistics || response.statistics.length === 0) {
				console.warn("没有统计数据");
				$("#courier-statistics-chart").after('<div class="alert alert-info">没有符合条件的快递统计数据</div>');
			}
			
			if (!response.totalsByType || !response.totalsByType.couriers || response.totalsByType.couriers.length === 0) {
				console.warn("没有类型统计数据");
				$("#courier-pie-chart").after('<div class="alert alert-info">没有符合条件的快递类型数据</div>');
			}
			
			// 统计数据和类型数据都为空时，显示提示
			if ((!response.statistics || response.statistics.length === 0) && 
				(!response.totalsByType || !response.totalsByType.couriers || response.totalsByType.couriers.length === 0)) {
				showNoDataMessage("没有统计数据，请尝试以下方法：<br>" +
					"1. 检查筛选条件中的日期范围<br>" +
					"2. 确保有活跃的快递类型<br>" +
					"3. 添加快递记录数据");
			} else {
				// 重新渲染所有图表
				RenderCharts();
				
				// 尝试更新数据表格内容 - Detailed data 和 Total summary
				updateDataTables(response);
			}
			
			// 恢复按钮状态
			$("#refresh-data-button").html('<i class="fa-solid fa-sync-alt"></i> 刷新');
			$(".card-body").removeClass("loading");
		},
		error: function(xhr, status, error) {
			console.error("数据加载失败:", error);
			console.error("响应内容:", xhr.responseText);
			
			showNoDataMessage("数据加载失败: " + (error || "网络错误") + "<br>请尝试刷新页面或检查网络连接");
			
			// 恢复按钮状态
			$("#refresh-data-button").html('<i class="fa-solid fa-sync-alt"></i> 刷新');
			$(".card-body").removeClass("loading");
		}
	});
}

// 清除所有图表和警告
function clearAllCharts() {
	// 清除警告信息
	$(".card-body .alert").remove();
	$(".alert-no-data").remove();
	
	// 清除图表实例
	if (window.barChart) {
		window.barChart.destroy();
		window.barChart = null;
	}
	
	if (window.pieChart) {
		window.pieChart.destroy();
		window.pieChart = null;
	}
}

// 更新数据表格
function updateDataTables(response) {
	try {
		// 更新 Detailed data 表格
		if (response.statistics && response.statistics.length > 0) {
			let detailedTableHTML = '<thead><tr><th>Period</th>';
			
			// 获取所有快递类型
			const courierTypes = new Map();
			response.statistics.forEach(stat => {
				if (stat.couriers && Array.isArray(stat.couriers)) {
					stat.couriers.forEach(courier => {
						if (!courierTypes.has(courier.courier_id)) {
							courierTypes.set(courier.courier_id, courier.courier_name);
						}
					});
				}
			});
			
			// 添加表头
			courierTypes.forEach((name) => {
				detailedTableHTML += `<th class="text-right">${name}</th>`;
			});
			detailedTableHTML += '<th class="text-right">Total</th></tr></thead><tbody>';
			
			// 添加数据行
			response.statistics.forEach(stat => {
				detailedTableHTML += `<tr><td>${stat.interval_key}</td>`;
				
				// 为每种快递类型添加数据
				courierTypes.forEach((name, id) => {
					let found = false;
					if (stat.couriers && Array.isArray(stat.couriers)) {
						for (const courier of stat.couriers) {
							if (courier.courier_id == id) {
								detailedTableHTML += `<td class="text-right">${courier.count}</td>`;
								found = true;
								break;
							}
						}
					}
					if (!found) {
						detailedTableHTML += '<td class="text-right">0</td>';
					}
				});
				
				// 添加总计
				detailedTableHTML += `<td class="text-right font-weight-bold">${stat.total || 0}</td></tr>`;
			});
			
			detailedTableHTML += '</tbody>';
			
			// 更新表格内容
			$(".card-header:contains('Detailed data')").closest('.card').find('table').html(detailedTableHTML);
		}
		
		// 更新 Total summary 表格
		if (response.totalsByType && response.totalsByType.couriers && response.totalsByType.couriers.length > 0) {
			let totalTableHTML = '<thead><tr><th>Courier type</th><th class="text-right">Count</th><th class="text-right">Percentage</th></tr></thead><tbody>';
			
			// 添加每种快递类型的行
			response.totalsByType.couriers.forEach(courier => {
				const percentage = (courier.total_count / response.totalsByType.total * 100).toFixed(1);
				totalTableHTML += `<tr>
					<td>${courier.courier_name}</td>
					<td class="text-right">${courier.total_count}</td>
					<td class="text-right">${percentage}%</td>
				</tr>`;
			});
			
			// 添加总计行
			totalTableHTML += `<tr class="table-primary font-weight-bold">
				<td>Total</td>
				<td class="text-right">${response.totalsByType.total}</td>
				<td class="text-right">100%</td>
			</tr></tbody>`;
			
			// 更新表格内容
			$(".card-header:contains('Total summary')").closest('.card').find('table').html(totalTableHTML);
		}
		
		console.log("数据表格更新完成");
	} catch (e) {
		console.error("更新数据表格时出错:", e);
	}
}

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
	$(".card-body .alert").remove();
	
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
	// 清除之前的图表和警告
	$(".card-body .alert").remove();
	
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

	// 清理之前的图表实例（如果有）
	if (window.barChart) {
		window.barChart.destroy();
	}
	
	const ctx = document.getElementById("courier-statistics-chart").getContext("2d");
	window.barChart = new Chart(ctx, {
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
			// 添加高度限制，确保图表不会无限拉伸
			onResize: function(chart, size) {
				if (size.height > 350) {
					chart.height = 350;
				}
			},
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
	
	// 清理之前的图表实例（如果有）
	if (window.pieChart) {
		window.pieChart.destroy();
	}
	
	window.pieChart = new Chart(ctx, {
		type: "doughnut", // 改用环形图，减少标签重叠
		data: data,
		options: {
			responsive: true,
			maintainAspectRatio: false, // 允许图表高度自适应
			cutoutPercentage: 40, // 适中的环形图挖空比例
			// 添加高度限制，确保图表不会无限拉伸
			onResize: function(chart, size) {
				if (size.height > 350) {
					chart.height = 350;
				}
			},
			layout: {
				padding: {
					left: 10,
					right: 50, // 增加右侧边距，确保显示完整
					top: 20,
					bottom: 40  // 增加底部边距，避免标签被截断
				}
			},
			legend: {
				position: "right", // 将图例放到右侧
				align: "start",
				labels: {
					boxWidth: 12, // 减小图例框的大小
					padding: 10,   // 图例项的内边距
					fontSize: 11    // 减小字体大小以减少重叠
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
			},
			// 彻底禁用图表上的标签显示
			plugins: {
				datalabels: {
					display: false
				}
			},
			// 确保不会在图表上绘制任何额外标签
			elements: {
				arc: {
					borderWidth: 1
				}
			},
			animation: {
				animateRotate: true,
				animateScale: true
			}
		}
	});
} 
/**
 * 快递发件记录管理页面的JS功能
 */
 
// 日期相关初始设置
$(function() {
	$(".datepicker").datepicker({
		format: "yyyy-mm-dd",
		autoclose: true,
		language: moment.locale(),
		todayHighlight: true,
		orientation: "bottom auto",
		calendarWeeks: true
	});

	// 初始化日期过滤器（默认显示本周）
	const today = moment();
	const startOfWeek = moment().startOf('week');
	const endOfWeek = moment().endOf('week');
	
	// 设置默认日期
	$("#date-filter-from").val(startOfWeek.format("YYYY-MM-DD"));
	$("#date-filter-to").val(endOfWeek.format("YYYY-MM-DD"));
	
	// 确保日期输入字段始终显示完整日期
	$("#date-filter-from, #date-filter-to").on("change", function() {
		const dateValue = $(this).val();
		if (dateValue && moment(dateValue, "YYYY-MM-DD").isValid()) {
			$(this).val(moment(dateValue, "YYYY-MM-DD").format("YYYY-MM-DD"));
		}
	});
	
	// 默认激活"本周"按钮
	$(".date-range-preset[data-range='this-week']").addClass("active");
	
	// 日期快捷选择
	$(".date-range-preset").on("click", function() {
		const range = $(this).data("range");
		let fromDate, toDate;
		const today = moment();
		
		switch(range) {
			case "today":
				fromDate = today.clone().format("YYYY-MM-DD");
				toDate = today.clone().format("YYYY-MM-DD");
				break;
			case "yesterday":
				fromDate = today.clone().subtract(1, "days").format("YYYY-MM-DD");
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
			default:
				return;
		}
		
		// 设置日期选择器的值
		$("#date-filter-from").val(fromDate);
		$("#date-filter-to").val(toDate);
		
		// 视觉反馈
		$(this).addClass("active").siblings().removeClass("active");
		
		// 高亮显示应用按钮
		$("#filter-apply-button").addClass("btn-success").removeClass("btn-primary").delay(100).queue(function(next){
			$(this).removeClass("btn-success").addClass("btn-primary");
			next();
		});
	});
	
	// 初始加载数据
	RefreshEntriesTable();
	
	// 确保搜索功能工作
	setTimeout(function() {
		// 创建自定义搜索框
		const tableSearchInput = $('#table-search');
		
		// 确保在表格外部的搜索框能影响表格
		tableSearchInput.off('keyup').on('keyup', function() {
			const searchTerm = $(this).val();
			console.log('搜索关键词:', searchTerm);
			entriesTable.search(searchTerm).draw();
		});
		
		// 记录表格初始化的状态
		console.log('表格状态检查: ');
		console.log('- 行数:', entriesTable.rows().count());
		console.log('- 搜索框是否存在:', tableSearchInput.length > 0);
		
		// 测试表格搜索是否响应
		entriesTable.search('测试').draw();
		console.log('测试搜索后行数:', entriesTable.rows({search:'applied'}).count());
		entriesTable.search('').draw();
	}, 2000);
});

// 表格初始化
let entriesTable = $("#courier-entries-table").DataTable({
	order: [[0, "desc"]],
	columnDefs: [
		{ 
			orderable: false, 
			targets: [3]
		}
	],
	columns: [
		{ data: "entry_date" },
		{ data: "courier_name" },
		{ data: "count" },
		{ 
			data: "id",
			render: function(data, type, row) {
				return '<a class="btn btn-sm btn-info courier-entry-edit-button" href="#" data-entry-id="' + data + '"><i class="fa-solid fa-edit"></i></a>&nbsp;' +
					   '<a class="btn btn-sm btn-danger courier-entry-delete-button" href="#" data-entry-id="' + data + '"><i class="fa-solid fa-trash"></i></a>';
			}
		}
	],
	data: [],
	language: datatables_i18n_config,
	responsive: true,
	stateSave: false,
	searchDelay: 200,
	initComplete: function() {
		$("#courier-entries-table tbody").removeClass("d-none");
		console.log("表格初始化完成");
	}
});

// 搜索功能
$("#table-search").on("keyup", function() {
	console.log("搜索关键词: " + $(this).val());
	entriesTable.search($(this).val()).draw();
});

// 类型筛选
$("#courier-type-filter").on("change", function() {
	RefreshEntriesTable();
});

// 日期筛选
$("#filter-apply-button").on("click", function() {
	RefreshEntriesTable();
});

// 加载数据
function RefreshEntriesTable() {
	const fromDate = $("#date-filter-from").val();
	const toDate = $("#date-filter-to").val();
	const courierTypeId = $("#courier-type-filter").val();
	
	// 记录过滤参数
	console.log("加载数据 - 过滤参数:", {
		from_date: fromDate,
		to_date: toDate,
		courier_type_id: courierTypeId
	});
	
	// 验证日期格式
	if (fromDate && !moment(fromDate, "YYYY-MM-DD").isValid()) {
		console.warn("起始日期格式无效:", fromDate);
		return;
	}
	
	if (toDate && !moment(toDate, "YYYY-MM-DD").isValid()) {
		console.warn("结束日期格式无效:", toDate);
		return;
	}
	
	let apiUrl = "courier/entries";
	let params = {};
	
	if (fromDate) {
		params.from_date = fromDate;
	}
	
	if (toDate) {
		params.to_date = toDate;
	}
	
	if (courierTypeId) {
		params.courier_type_id = courierTypeId;
	}
	
	// 构建查询字符串
	let queryParams = [];
	for (let key in params) {
		if (params.hasOwnProperty(key)) {
			queryParams.push(key + "=" + encodeURIComponent(params[key]));
		}
	}
	
	let queryString = queryParams.length > 0 ? "?" + queryParams.join("&") : "";
	
	// 显示加载指示器
	entriesTable.clear().draw();
	$("#courier-entries-table").addClass("loading");
	
	// API调用
	Grocy.Api.Get(apiUrl + queryString, function(entries) {
		console.log("API响应 - 返回记录数:", entries.length);
		
		// 移除加载指示器
		$("#courier-entries-table").removeClass("loading");
		
		// 更新表格
		entriesTable.clear();
		entriesTable.rows.add(entries).draw();
		
		// 显示结果消息
		if (entries.length === 0) {
			// 没有数据时显示消息
			if ($("#no-data-message").length === 0) {
				$("#courier-entries-table").after('<div id="no-data-message" class="alert alert-info mt-3">没有符合条件的快递记录数据</div>');
			}
		} else {
			// 有数据时移除消息
			$("#no-data-message").remove();
		}
	}, function(xhr) {
		// 移除加载指示器
		$("#courier-entries-table").removeClass("loading");
		
		// 显示错误消息
		Grocy.FrontendHelpers.ShowGenericError("加载数据失败", xhr.response);
	});
}

// 保存新记录
$("#save-courier-entry-button").on("click", function(e) {
	e.preventDefault();
	
	let $form = $("#courier-entry-form");
	
	if (!$form[0].checkValidity()) {
		$form.addClass("was-validated");
		return;
	}
	
	const formData = {
		courier_type_id: $("#courier_type_id").val(),
		entry_date: $("#entry_date").val(),
		count: $("#count").val()
	};
	
	if (formData.count < 0 || !formData.count) {
		$("#count").closest(".form-group").find(".invalid-feedback").text("数量必须大于或等于0");
		$form.addClass("was-validated");
		return;
	}
	
	Grocy.Api.post("courier/entries", formData, function(result) {
		toastr.success(__t("Entry added successfully"));
		
		// 清空表单并重置为今天日期
		$("#courier_type_id").val("");
		$("#count").val("0");
		$("#entry_date").val(moment().format("YYYY-MM-DD"));
		$form.removeClass("was-validated");
		
		// 刷新数据表格
		RefreshEntriesTable();
	}, function(xhr) {
		Grocy.FrontendHelpers.ShowGenericError("添加记录失败", xhr.response);
	});
});

// 编辑记录（显示编辑模态框）
$(document).on("click", ".courier-entry-edit-button", function(e) {
	e.preventDefault();
	
	const entryId = $(this).data("entry-id");
	
	Grocy.Api.get("courier/entries/" + entryId, {}, function(entry) {
		$("#edit-courier-entry-id").val(entry.id);
		$("#edit-courier_type_id").val(entry.courier_type_id);
		$("#edit-entry_date").val(entry.entry_date);
		$("#edit-count").val(entry.count);
		
		$("#courier-entry-edit-modal").modal("show");
	}, function(xhr) {
		Grocy.FrontendHelpers.ShowGenericError("获取记录详情失败", xhr.response);
	});
});

// 保存编辑
$("#save-courier-entry-edit-button").on("click", function() {
	let $form = $("#courier-entry-edit-form");
	
	if (!$form[0].checkValidity()) {
		$form.addClass("was-validated");
		return;
	}
	
	const entryId = $("#edit-courier-entry-id").val();
	const formData = {
		courier_type_id: $("#edit-courier_type_id").val(),
		entry_date: $("#edit-entry_date").val(),
		count: $("#edit-count").val()
	};
	
	if (formData.count < 0 || !formData.count) {
		$("#edit-count").closest(".form-group").find(".invalid-feedback").text("数量必须大于或等于0");
		$form.addClass("was-validated");
		return;
	}
	
	Grocy.Api.put("courier/entries/" + entryId, formData, function(result) {
		toastr.success(__t("Entry updated successfully"));
		$("#courier-entry-edit-modal").modal("hide");
		
		// 刷新数据表格
		RefreshEntriesTable();
	}, function(xhr) {
		Grocy.FrontendHelpers.ShowGenericError("更新记录失败", xhr.response);
	});
});

// 删除记录
$(document).on("click", ".courier-entry-delete-button", function(e) {
	e.preventDefault();
	
	const entryId = $(this).data("entry-id");
	
	Grocy.FrontendHelpers.BeginUiBusy();
	
	bootbox.confirm({
		message: __t("Are you sure to delete this entry?"),
		closeButton: false,
		buttons: {
			confirm: {
				label: __t("Yes"),
				className: "btn-danger"
			},
			cancel: {
				label: __t("No"),
				className: "btn-secondary"
			}
		},
		callback: function(result) {
			if (result) {
				Grocy.Api.delete("courier/entries/" + entryId, {}, function(result) {
					toastr.success(__t("Entry deleted successfully"));
					
					// 刷新数据表格
					RefreshEntriesTable();
				}, function(xhr) {
					Grocy.FrontendHelpers.EndUiBusy();
					Grocy.FrontendHelpers.ShowGenericError("删除记录失败", xhr.response);
				});
			} else {
				Grocy.FrontendHelpers.EndUiBusy();
			}
		}
	});
}); 
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

	// 初始化日期过滤器（默认当月）
	const today = moment();
	const firstDay = moment().startOf('month');
	
	$("#date-filter-from").val(firstDay.format("YYYY-MM-DD"));
	$("#date-filter-to").val(today.format("YYYY-MM-DD"));
	
	// 初始加载数据
	RefreshEntriesTable();
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
	stateSave: true,
	initComplete: function() {
		$("#courier-entries-table tbody").removeClass("d-none");
	}
});

// 搜索功能
$("#search").on("keyup", Delay(function() {
	entriesTable.search($(this).val()).draw();
}, 200));

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
	
	Grocy.Api.get(apiUrl, params, function(entries) {
		entriesTable.clear();
		entriesTable.rows.add(entries).draw();
	}, function(xhr) {
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
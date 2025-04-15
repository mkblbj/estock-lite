/**
 * 快递类型管理页面的JS功能
 */

// 初始化数据表格
$("#courier-types-table").DataTable({
	order: [[0, "asc"]],
	columnDefs: [
		{ orderable: false, targets: [3] }
	],
	language: datatables_i18n_config,
	responsive: true,
	stateSave: true,
	initComplete: function() {
		this.api().columns().every(function() {
			let column = this;
			// 显示表格内容（防止初始化时闪烁）
			$("#courier-types-table tbody").removeClass("d-none");
		});
	}
});

// 搜索框功能
$("#search").on("keyup", Delay(function() {
	$("#courier-types-table").DataTable().search($(this).val()).draw();
}, 200));

// 显示/隐藏禁用类型
$("#show-disabled").change(function() {
	if (this.checked) {
		$("#courier-types-table tr").show();
	} else {
		// 隐藏active=0的行
		$("#courier-types-table tr").each(function() {
			if ($(this).find("td:eq(2)").text() == "0") {
				$(this).hide();
			}
		});
	}
});

// 默认只显示启用的
$("#show-disabled").prop("checked", false).trigger("change");

// 创建快递类型
$("#save-courier-type-button").on("click", function() {
	let $form = $("#courier-type-form");
	if (!$form[0].checkValidity()) {
		$form.addClass("was-validated");
		return;
	}

	const formData = {
		name: $("#name").val(),
		description: $("#description").val(),
		active: $("#active").is(":checked") ? 1 : 0
	};

	Grocy.Api.post("courier/types", formData,
		function(result) {
			toastr.success(__t("Courier type created successfully"));
			location.reload();
		},
		function(xhr) {
			Grocy.FrontendHelpers.ShowGenericError("创建快递类型失败", xhr.response);
		}
	);
});

// 编辑快递类型（显示编辑模态框）
$(document).on("click", ".show-as-dialog-link[data-courier-type-id]", function() {
	const typeId = $(this).data("courier-type-id");
	const typeName = $(this).data("courier-type-name");
	const typeDescription = $(this).data("courier-type-description");
	const typeActive = $(this).data("courier-type-active") == "1";

	$("#edit-courier-type-id").val(typeId);
	$("#edit-name").val(typeName);
	$("#edit-description").val(typeDescription);
	$("#edit-active").prop("checked", typeActive);

	$("#courier-type-edit-modal").modal("show");
});

// 保存编辑
$("#save-courier-type-edit-button").on("click", function() {
	let $form = $("#courier-type-edit-form");
	if (!$form[0].checkValidity()) {
		$form.addClass("was-validated");
		return;
	}

	const typeId = $("#edit-courier-type-id").val();
	const formData = {
		name: $("#edit-name").val(),
		description: $("#edit-description").val(),
		active: $("#edit-active").is(":checked") ? 1 : 0
	};

	Grocy.Api.put("courier/types/" + typeId, formData,
		function(result) {
			toastr.success(__t("Courier type updated successfully"));
			location.reload();
		},
		function(xhr) {
			Grocy.FrontendHelpers.ShowGenericError("更新快递类型失败", xhr.response);
		}
	);
});

// 删除快递类型
$(document).on("click", ".courier-type-delete-button:not(.disabled)", function() {
	const typeId = $(this).data("courier-type-id");
	const typeName = $(this).data("courier-type-name");

	Grocy.FrontendHelpers.BeginUiBusy();

	bootbox.confirm({
		message: __t("Are you sure to delete courier type %s?", typeName),
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
				Grocy.Api.delete("courier/types/" + typeId, {},
					function(result) {
						toastr.success(__t("Courier type deleted successfully"));
						location.reload();
					},
					function(xhr) {
						Grocy.FrontendHelpers.EndUiBusy();
						Grocy.FrontendHelpers.ShowGenericError("删除快递类型失败", xhr.response);
					}
				);
			} else {
				Grocy.FrontendHelpers.EndUiBusy();
			}
		}
	});
}); 
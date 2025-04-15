/**
 * 快递类型管理页面的JS功能
 */

$(document).ready(function() {
	// 简化的搜索功能
	$("#search").on("keyup", function() {
		const searchText = $(this).val().toLowerCase();
		$("#courier-types-table tbody tr").each(function() {
			const name = $(this).find("td:nth-child(2)").text().toLowerCase();
			const description = $(this).find("td:nth-child(3)").text().toLowerCase();
			
			if (name.includes(searchText) || description.includes(searchText)) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	});

	// 显示/隐藏禁用类型
	$("#show-disabled").change(function() {
		if (this.checked) {
			// 显示所有行
			$("#courier-types-table tbody tr").show();
			// 如果有搜索文本，再次应用搜索过滤
			if ($("#search").val()) {
				$("#search").trigger("keyup");
			}
		} else {
			// 隐藏active=0的行
			$("#courier-types-table tbody tr").each(function() {
				var activeCell = $(this).find("td:nth-child(4)"); // 现在是第4列
				if (activeCell.text().trim() == "0") {
					$(this).hide();
				}
			});
		}
	});

	// 默认只显示启用的
	$("#show-disabled").prop("checked", false).trigger("change");

	// 添加表格行交替颜色
	$("#courier-types-table tbody tr:odd").addClass("table-light");
	
	// 添加类型前清空表单
	$('#courier-type-add-modal').on('show.bs.modal', function () {
		$("#courier-type-form")[0].reset();
		$("#courier-type-form").removeClass("was-validated");
	});
	
	// 编辑类型前清空表单
	$('#courier-type-edit-modal').on('show.bs.modal', function () {
		$("#courier-type-edit-form").removeClass("was-validated");
	});
});

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

	// 显示加载状态
	$(this).addClass("disabled").html('<i class="fa-solid fa-spinner fa-spin"></i> ' + __t("Saving..."));

	Grocy.Api.post("courier/types", formData,
		function(result) {
			$('#courier-type-add-modal').modal('hide');
			toastr.success(__t("Courier type created successfully"));
			setTimeout(function() { location.reload(); }, 500); // 延迟刷新，确保模态框完全关闭
		},
		function(xhr) {
			$("#save-courier-type-button").removeClass("disabled").html(__t("Save"));
			Grocy.FrontendHelpers.ShowGenericError("创建快递类型失败", xhr.response);
		}
	);
});

// 编辑快递类型（显示编辑模态框）
$(document).on("click", ".show-as-dialog-link[data-courier-type-id]", function(e) {
	e.preventDefault();
	
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

	// 显示加载状态
	$(this).addClass("disabled").html('<i class="fa-solid fa-spinner fa-spin"></i> ' + __t("Saving..."));

	Grocy.Api.put("courier/types/" + typeId, formData,
		function(result) {
			$('#courier-type-edit-modal').modal('hide');
			toastr.success(__t("Courier type updated successfully"));
			setTimeout(function() { location.reload(); }, 500); // 延迟刷新，确保模态框完全关闭
		},
		function(xhr) {
			$("#save-courier-type-edit-button").removeClass("disabled").html(__t("Save"));
			Grocy.FrontendHelpers.ShowGenericError("更新快递类型失败", xhr.response);
		}
	);
});

// 删除快递类型
$(document).on("click", ".courier-type-delete-button:not(.disabled)", function(e) {
	e.preventDefault();
	
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
						setTimeout(function() { location.reload(); }, 500);
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
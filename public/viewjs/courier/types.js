/**
 * 快递类型管理页面的JS功能
 */

$(document).ready(function() {
	// 添加全局模态框清理逻辑
	$(document).on('hide.bs.modal', '.modal', function (e) {
		// 防止嵌套模态框的问题
		setTimeout(function() {
			if ($('.modal:visible').length === 0) {
				// 只有当没有可见模态框时才清理
				$('body').removeClass('modal-open').css('padding-right', '');
				$('.modal-backdrop').remove();
				// 移除可能冲突的aria属性
				$('.modal').removeAttr('aria-hidden');
			}
		}, 100);
	});

	// 初始化时设置删除按钮的禁用状态
	$('.courier-type-delete-button').each(function() {
		const isActive = $(this).data('courier-type-active') == "1";
		if (isActive) {
			$(this).addClass('disabled');
		} else {
			$(this).removeClass('disabled');
		}
	});

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

	// 移除show-disabled复选框逻辑
	// 默认显示所有行（包括禁用的）
	$("#courier-types-table tbody tr").show();

	// 添加表格行交替颜色
	$("#courier-types-table tbody tr:odd").addClass("table-light");
	
	// 添加类型前清空表单
	$('#courier-type-add-modal').on('show.bs.modal', function () {
		$("#courier-type-form")[0].reset();
		$("#courier-type-form").removeClass("was-validated");
	});
	
	// 为添加模态框设置焦点管理
	$('#courier-type-add-modal').on('shown.bs.modal', function () {
		$("#name").focus();
	});

	// 编辑类型前清空表单
	$('#courier-type-edit-modal').on('show.bs.modal', function () {
		$("#courier-type-edit-form").removeClass("was-validated");
	});

	// 修复模态框可访问性问题
	$('#courier-type-edit-modal, #courier-type-add-modal').on('shown.bs.modal', function () {
		// 移除aria-hidden属性，设置aria-modal属性
		$(this).removeAttr('aria-hidden').attr('aria-modal', 'true');
		
		// 确保模态框内的所有内容都可以获得焦点
		$(this).find('*').removeAttr('aria-hidden');
		
		// 根据当前模态框ID设置不同的焦点
		if ($(this).attr('id') === 'courier-type-add-modal') {
			$("#name").focus();
		} else if ($(this).attr('id') === 'courier-type-edit-modal') {
			$("#edit-name").focus();
		}
	});

	// 模态框关闭时处理
	$('#courier-type-edit-modal, #courier-type-add-modal').on('hidden.bs.modal', function () {
		// 移除所有可能冲突的aria属性
		$(this).removeAttr('aria-hidden');
		$('.modal-backdrop').removeAttr('aria-hidden');
		
		// 确保body没有多余的类和样式
		if ($('.modal.show').length === 0) {
			$('body').removeClass('modal-open').css('padding-right', '');
			$('.modal-backdrop').remove();
		}
	});

	// 添加按钮点击事件
	$("#add-courier-type-button").on("click", function(e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation(); // 阻止所有后续事件处理程序
		
		// 清理任何可能存在的模态框和背景
		$('.modal').modal('hide');
		$('body').removeClass('modal-open').css('padding-right', '');
		$('.modal-backdrop').remove();
		
		// 使用延时确保前一个模态框已完全关闭
		setTimeout(function() {
			const addModal = $("#courier-type-add-modal");
			
			// 确保重置模态框的ARIA属性
			addModal.removeAttr("aria-hidden");
			
			// 修改Bootstrap默认的模态框属性设置行为
			addModal.on('show.bs.modal', function() {
				$(this).removeAttr('aria-hidden');
			});
			
			addModal.on('shown.bs.modal', function() {
				$(this).removeAttr('aria-hidden').attr('aria-modal', 'true');
				$("#name").focus();
			});
			
			// 打开模态框
			addModal.modal({
				backdrop: true,
				keyboard: true,
				focus: true
			});
		}, 300);
		
		return false;
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

	Grocy.Api.Post("courier/types", formData,
		function(result) {
			// 手动关闭模态框并清理相关元素
			$('#courier-type-add-modal').modal('hide');
			$('body').removeClass('modal-open').css('padding-right', '');
			$('.modal-backdrop').remove();
			
			// 移除可能冲突的aria属性
			$('#courier-type-add-modal').removeAttr('aria-hidden').removeAttr('aria-modal');
			
			toastr.success(__t("Courier type created successfully"));
			
			// 延迟刷新
			setTimeout(function() { 
				location.reload(); 
			}, 800);
		},
		function(xhr) {
			$("#save-courier-type-button").removeClass("disabled").html(__t("Save"));
			Grocy.FrontendHelpers.ShowGenericError("创建快递类型失败", xhr.response);
		}
	);
});

// 编辑快递类型（显示编辑模态框）
$(document).on("click", ".edit-courier-type", function(e) {
	e.preventDefault();
	e.stopImmediatePropagation(); // 阻止所有后续事件处理程序
	
	// 防止全局处理程序执行
	if (typeof e.originalEvent !== 'undefined') {
		e.originalEvent.stopPropagation();
	}
	
	const typeId = $(this).data("courier-type-id");
	const typeName = $(this).data("courier-type-name");
	const typeDescription = $(this).data("courier-type-description");
	const typeActive = $(this).data("courier-type-active") == "1";

	// 清理任何可能存在的模态框和背景
	$('.modal').modal('hide');
	$('body').removeClass('modal-open').css('padding-right', '');
	$('.modal-backdrop').remove();
	
	// 设置表单字段值
	$("#edit-courier-type-id").val(typeId);
	$("#edit-name").val(typeName);
	$("#edit-description").val(typeDescription);
	$("#edit-active").prop("checked", typeActive);

	// 使用延时确保前一个模态框已完全关闭
	setTimeout(function() {
		const editModal = $("#courier-type-edit-modal");
		
		// 确保重置模态框的ARIA属性
		editModal.removeAttr("aria-hidden");
		
		// 修改Bootstrap默认的模态框属性设置行为
		editModal.on('show.bs.modal', function() {
			$(this).removeAttr('aria-hidden');
		});
		
		editModal.on('shown.bs.modal', function() {
			$(this).removeAttr('aria-hidden').attr('aria-modal', 'true');
			$("#edit-name").focus();
		});
		
		// 打开模态框
		editModal.modal({
			backdrop: true,
			keyboard: true,
			focus: true
		});
	}, 300);
	
	// 防止事件继续冒泡
	return false;
});

// 为兼容性保留旧的选择器，并完全阻止执行
$(document).on("click", ".show-as-dialog-link[data-courier-type-id]", function(e) {
	e.preventDefault();
	e.stopImmediatePropagation();
	return false;
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

	Grocy.Api.Put("courier/types/" + typeId, formData,
		function(result) {
			// 手动关闭模态框并清理相关元素
			$('#courier-type-edit-modal').modal('hide');
			$('body').removeClass('modal-open').css('padding-right', '');
			$('.modal-backdrop').remove();
			
			// 移除可能冲突的aria属性
			$('#courier-type-edit-modal').removeAttr('aria-hidden').removeAttr('aria-modal');
			
			toastr.success(__t("Courier type updated successfully"));
			
			// 强制刷新页面，确保DOM元素完全重新加载
			setTimeout(function() { 
				window.location.href = window.location.href.split('#')[0];
			}, 800);
		},
		function(xhr) {
			$("#save-courier-type-edit-button").removeClass("disabled").html(__t("Save"));
			Grocy.FrontendHelpers.ShowGenericError("更新快递类型失败", xhr.response);
		}
	);
});

// 删除快递类型
$(document).on("click", ".courier-type-delete-button", function(e) {
	e.preventDefault();
	e.stopImmediatePropagation(); // 阻止事件冒泡
	
	// 检查按钮是否禁用（通过class）
	if ($(this).hasClass('disabled')) {
		toastr.error("This courier type cannot be deleted because it is already in use");
		return false;
	}
	
	const typeId = $(this).data("courier-type-id");
	const typeName = $(this).data("courier-type-name");
	const typeActive = $(this).data("courier-type-active") == "1";
	
	// 直接检查当前数据属性中的激活状态
	if (typeActive) {
		// 显示错误消息
		toastr.error("This courier type cannot be deleted because it is already in use");
		return false;
	}

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
				Grocy.Api.Delete("courier/types/" + typeId, {},
					function(result) {
						toastr.success(__t("Courier type deleted successfully"));
						setTimeout(function() { location.reload(); }, 500);
					},
					function(xhr) {
						Grocy.FrontendHelpers.EndUiBusy();
						// 解析错误响应
						try {
							const errorResponse = JSON.parse(xhr.response);
							if (errorResponse && errorResponse.error_message) {
								// 直接显示错误消息
								toastr.error(errorResponse.error_message);
							} else {
								// 无法解析错误消息，显示默认错误
								toastr.error(__t("An error occurred while deleting the courier type"));
							}
						} catch (e) {
							// JSON解析失败，显示默认错误
							toastr.error(__t("An error occurred while deleting the courier type"));
						}
					}
				);
			} else {
				Grocy.FrontendHelpers.EndUiBusy();
			}
		}
	});
}); 
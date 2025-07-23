﻿var productsTable = $("#products-table").DataTable({
	order: [[1, "asc"]],
	columnDefs: [
		{ orderable: false, targets: 0 },
		{ searchable: false, targets: 0 },
		// { visible: false, targets: 7 },
		// { visible: false, targets: 8 },
		// { visible: false, targets: 9 },
		{ type: "html-num-fmt", targets: 3 },
	].concat($.fn.dataTable.defaults.columnDefs),
});
$("#products-table tbody").removeClass("d-none");
productsTable.columns.adjust().draw();

// 添加复制按钮
if (typeof window.addCopyButtonsToTable === 'function') {
    setTimeout(() => {
        window.addCopyButtonsToTable('products-table');
    }, 500);
} else {
    // 如果函数还没加载，延迟执行
    setTimeout(() => {
        if (typeof window.addCopyButtonsToTable === 'function') {
            window.addCopyButtonsToTable('products-table');
        }
    }, 2000);
}

$("#search").on(
	"keyup",
	Delay(function () {
		var value = $(this).val();
		if (value === "all") {
			value = "";
		}

		productsTable.search(value).draw();
	}, Grocy.FormFocusDelay)
);

$("#product-group-filter").on("change", function () {
	var value = $("#product-group-filter option:selected").text();
	if (value === __t("All")) {
		productsTable
			.column(productsTable.colReorder.transpose(6))
			.search("")
			.draw();
	} else {
		productsTable
			.column(productsTable.colReorder.transpose(6))
			.search(
				"^" + $.fn.dataTable.util.escapeRegex(value) + "$",
				true,
				false
			)
			.draw();
	}
});

$("#clear-filter-button").on("click", function () {
	$("#search").val("");
	$("#product-group-filter").val("all");
	productsTable
		.column(productsTable.colReorder.transpose(6))
		.search("")
		.draw();
	productsTable.search("").draw();

	if ($("#show-disabled").is(":checked")) {
		$("#show-disabled").prop("checked", false);
		RemoveUriParam("include_disabled");
		RemoveUriParam("only_in_stock");
		window.location.reload();
	}

	if ($("#status-filter").val() != "all") {
		$("#status-filter").val("all");
		$("#status-filter").trigger("change");
	}
});

if (typeof GetUriParam("product-group") !== "undefined") {
	$("#product-group-filter").val(GetUriParam("product-group"));
	$("#product-group-filter").trigger("change");
}

$(document).on("click", ".product-delete-button", function (e) {
	var objectName = $(e.currentTarget).attr("data-product-name");
	var objectId = $(e.currentTarget).attr("data-product-id");

	bootbox.confirm({
		message:
			__t('Are you sure you want to delete product "%s"?', objectName) +
			"<br><br>" +
			__t(
				"This also removes any stock amount, the journal and all other references of this product - consider disabling it instead, if you want to keep that and just hide the product."
			),
		closeButton: false,
		buttons: {
			confirm: {
				label: __t("Yes"),
				className: "btn-success",
			},
			cancel: {
				label: __t("No"),
				className: "btn-danger",
			},
		},
		callback: function (result) {
			if (result === true) {
				jsonData = {};
				jsonData.active = 0;
				Grocy.Api.Delete(
					"objects/products/" + objectId,
					{},
					function (result) {
						window.location.href = U("/products");
					},
					function (xhr) {
						console.error(xhr);
					}
				);
			}
		},
	});
});

$("#show-disabled").change(function () {
	if (this.checked) {
		UpdateUriParam("include_disabled", "true");
	} else {
		RemoveUriParam("include_disabled");
	}

	window.location.reload();
});

$("#status-filter").change(function () {
	var value = $(this).val();

	if (value == "all") {
		UpdateUriParam("only_in_stock", "true");
		RemoveUriParam("only_in_stock");
		RemoveUriParam("only_out_of_stock");
	} else if (value == "out-of-stock") {
		RemoveUriParam("only_in_stock");
		UpdateUriParam("only_out_of_stock", "true");
	} else if (value == "in-stock") {
		RemoveUriParam("only_out_of_stock");
		UpdateUriParam("only_in_stock", "true");
	}

	window.location.reload();
});

if (GetUriParam("include_disabled")) {
	$("#show-disabled").prop("checked", true);
}

$(".merge-products-button").on("click", function (e) {
	var productId = $(e.currentTarget).attr("data-product-id");
	$("#merge-products-keep").val(productId);
	$("#merge-products-remove").val("");
	$("#merge-products-modal").modal("show");
});

$("#merge-products-save-button").on("click", function (e) {
	e.preventDefault();

	if (!Grocy.FrontendHelpers.ValidateForm("merge-products-form", true)) {
		return;
	}

	var productIdToKeep = $("#merge-products-keep").val();
	var productIdToRemove = $("#merge-products-remove").val();

	Grocy.Api.Post(
		"stock/products/" +
			productIdToKeep.toString() +
			"/merge/" +
			productIdToRemove.toString(),
		{},
		function (result) {
			window.location.href = U("/products");
		},
		function (xhr) {
			Grocy.FrontendHelpers.ShowGenericError(
				"Error while merging",
				xhr.response
			);
		}
	);
});

$("#exportBtn").on("click", function (e) {
	e.preventDefault();

	var status = "all";
	if (GetUriParam("only_in_stock")) {
		status = "in";
	} else if (GetUriParam("only_out_of_stock")) {
		status = "out";
	}
	var group = $("#product-group-filter").val();
	// var status = $("status-filter").val();
	var showDisabled = $("#show-disabled").is(":checked") ? 1 : 0;
	var param = {
		group_name: group,
		status: status,
		showDisabled: showDisabled,
	};

	window.location.href = U("/products/export") + "?" + $.param(param);
});

$("#importBtn").on("click", function () {
	$("#fileInput").click();
});

$("#fileInput").on("change", function (e) {
	if (e.target.files.length === 0) return;

	let file = e.target.files[0];
	let formData = new FormData();
	formData.append("excel", file);

	// toastr.info("文件上传中...");

	Grocy.Api.Post(
		"stock/products/import",
		formData,
		function (result) {
			toastr.success("导入成功！");
			window.location.reload();
		},
		function (xhr) {
			Grocy.FrontendHelpers.ShowGenericError(
				"Error while merging",
				xhr.response
			);
		}
	);
});

var userobjectsTable = $('.userobjects-table').DataTable({
	'order': [[1, 'asc']],
	'columnDefs': [
		{ 'orderable': false, 'targets': 0 },
		{ 'searchable': false, "targets": 0 }
	].concat($.fn.dataTable.defaults.columnDefs)
});
$('.userobjects-table tbody').removeClass("d-none");
userobjectsTable.columns.adjust().draw();

// 恢复搜索状态
var savedSearch = sessionStorage.getItem('userobjects_search');
if (savedSearch)
{
	$("#search").val(savedSearch);
	userobjectsTable.search(savedSearch).draw();
}

$("#search").on("keyup", Delay(function()
{
	var value = $(this).val();
	if (value === "all")
	{
		value = "";
	}

	// 保存搜索状态
	sessionStorage.setItem('userobjects_search', value);
	userobjectsTable.search(value).draw();
}, Grocy.FormFocusDelay));

$("#clear-filter-button").on("click", function()
{
	$("#search").val("");
	// 清除保存的搜索状态
	sessionStorage.removeItem('userobjects_search');
	userobjectsTable.search("").draw();
});

$(document).on('click', '.userobject-delete-button', function(e)
{
	var objectId = $(e.currentTarget).attr('data-userobject-id');

	bootbox.confirm({
		message: __t('Are you sure you want to delete this userobject?'),
		closeButton: false,
		buttons: {
			confirm: {
				label: __t('Yes'),
				className: 'btn-success'
			},
			cancel: {
				label: __t('No'),
				className: 'btn-danger'
			}
		},
		callback: function(result)
		{
			if (result === true)
			{
				Grocy.Api.Delete('objects/userobjects/' + objectId, {},
					function(result)
					{
						window.location.reload();
					},
					function(xhr)
					{
						console.error(xhr);
					}
				);
			}
		}
	});
});

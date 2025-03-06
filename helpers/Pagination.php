<?php

namespace Grocy\Helpers;

class Pagination
{
	private $totalRecords;   // 总记录数
	private $currentPage;    // 当前页
	private $pageSize;       // 每页大小
	private $totalPages;     // 总页数
	private $queryParams;    // 其他查询参数

	// 构造函数
	public function __construct($totalRecords, $pageSize = 20)
	{
		$this->totalRecords = $totalRecords;

		// 获取当前页码和分页大小（默认值为1和20）
		$this->currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		$this->pageSize = $pageSize;

		// 计算总页数
		$this->totalPages = ceil($totalRecords / $this->pageSize);

		// 获取其他 query 参数，排除 'page' 和 'page_size'
		$this->queryParams = $_GET;
		unset($this->queryParams['page'], $this->queryParams['page_size']);
	}

	// 生成分页导航
	public function render()
	{
		// 当前页码合法性检查
		if ($this->currentPage < 1) $this->currentPage = 1;
		if ($this->currentPage > $this->totalPages) $this->currentPage = $this->totalPages;

		// 包裹分页部分的容器
		$paginationHtml = '<div class="pagination-container  d-flex align-items-center my-4" >';

		// 分页导航
		$paginationHtml .= '<ul class="pagination justify-content-center mx-5">';

		// 上一页链接
		if ($this->currentPage > 1) {
			$paginationHtml .= $this->createPageLink($this->currentPage - 1, '«', 'page-item');
		} else {
			$paginationHtml .= '<li class="page-item disabled"><span class="page-link">«</span></li>';
		}

		// 页码链接
		$range = 5; // 显示的页码范围
		$startPage = max(1, $this->currentPage - $range);
		$endPage = min($this->totalPages, $this->currentPage + $range);
		for ($page = $startPage; $page <= $endPage; $page++) {
			$activeClass = ($page == $this->currentPage) ? 'active' : '';
			$paginationHtml .= $this->createPageLink($page, $page, 'page-item ' . $activeClass);
		}

		// 下一页链接
		if ($this->currentPage < $this->totalPages) {
			$paginationHtml .= $this->createPageLink($this->currentPage + 1, '»', 'page-item');
		} else {
			$paginationHtml .= '<li class="page-item disabled"><span class="page-link">»</span></li>';
		}

		$paginationHtml .= '</ul>';

		// 分页大小选择器
		$paginationHtml .= $this->renderPageSizeSelector();

		// 当前页码显示
		$paginationHtml .= $this->renderCurrentPageInfo();

		// 结束包裹容器
		$paginationHtml .= '</div>';

		return $paginationHtml;
	}

	// 创建页码链接
	private function createPageLink($page, $label, $class = 'page-item bg-body')
	{
		// 保持其他query参数不变
		$queryParams = $this->queryParams;
		$queryParams['page'] = $page;  // 设置新的页码
        $queryParams['page_size'] = $this->pageSize;  // 保持分页大小不变
		return '<li class="' . $class . '"><a class="page-link bg-body" href="?' . http_build_query($queryParams) . '">' . $label . '</a></li>';
	}

	// 渲染分页大小选择器
	private function renderPageSizeSelector()
	{
 
		$sizes = [20, 50, 100, 200]; // 可以根据需要修改分页大小选项
		$selectorHtml = '<div class="d-flex justify-content-center">';
		$selectorHtml .= '<label class="mr-2">每页显示:</label>';
		$selectorHtml .= '<select class="form-select-sm w-auto" onchange="p_switch_size(this)">';

		foreach ($sizes as $size) {
			$selected = $this->pageSize == $size ? 'selected' : '';
			$selectorHtml .= '<option value="' . $size . '" ' . $selected . '>' . $size . '</option>';
		}

		$selectorHtml .= '</select>';
        $selectorHtml .="<script>
        function p_switch_size(selectElement) {
            const newSize = selectElement.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page_size', newSize);
            window.location.href = currentUrl.toString();
        }
        </script>";
        

		$selectorHtml .= '</div>';

		return $selectorHtml;
	}

	// 当前页码显示
	private function renderCurrentPageInfo()
	{
		$startRecord = ($this->currentPage - 1) * $this->pageSize + 1;
		$endRecord = min($this->currentPage * $this->pageSize, $this->totalRecords);
		return "<div class='text-center mx-5'>{$startRecord}-{$endRecord} of {$this->totalRecords}</div>";
	}
}

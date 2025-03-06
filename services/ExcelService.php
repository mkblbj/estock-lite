<?php

namespace Grocy\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService extends BaseService {
	// 导出数据到 Excel
	public static function exportToExcel($data, $filename = 'export.xlsx') {
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		// 添加表头
		$headers = array_keys($data[0]);
		$sheet->fromArray($headers, NULL, 'A1');

		// 填充数据
		$rowData = [];
		foreach ($data as $item) {
			$rowData[] = array_values($item);
		}
		$sheet->fromArray($rowData, NULL, 'A2');

		// 输出到浏览器
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		exit;
	}

	// 从 Excel 解析数据
	public static function parseExcel($filePath) {
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
		$sheet = $spreadsheet->getActiveSheet();

		$data = [];
		foreach ($sheet->getRowIterator() as $row) {
			$rowData = [];
			foreach ($row->getCellIterator() as $cell) {
				$rowData[] = $cell->getValue();
			}
			$data[] = $rowData;
		}

		// 移除表头（如果第一行是标题）
		$keyArr = $data[0];
		array_shift($data);
		$tmpArr = [];
		foreach ($data as $v) {
			$tmp = [];
			foreach ($keyArr as $k => $v1) {
				$tmp[$v1] = $v[$k];
			}
			$tmpArr[] = $tmp;
		}
		if (!empty($tmpArr)) {
			$data = $tmpArr;
		}
		return $data;
	}
}

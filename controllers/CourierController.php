<?php

namespace Grocy\Controllers;

use Grocy\Services\CourierService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CourierController extends BaseController
{
	public function Overview(Request $request, Response $response, array $args)
	{
		$courierService = new CourierService($this->getDatabase());
		
		// 获取时间范围参数，默认为本周数据
		$fromDate = date('Y-m-d', strtotime('monday this week'));
		$toDate = date('Y-m-d', strtotime('sunday this week'));
		
		$queryParams = $request->getQueryParams();
		if (isset($queryParams['from_date']) && !empty($queryParams['from_date']))
		{
			$fromDate = $queryParams['from_date'];
		}
		
		if (isset($queryParams['to_date']) && !empty($queryParams['to_date']))
		{
			$toDate = $queryParams['to_date'];
		}
		
		// 获取时间间隔参数，默认为天
		$interval = 'day';
		if (isset($queryParams['interval']) && in_array($queryParams['interval'], ['day', 'week', 'month', 'year']))
		{
			$interval = $queryParams['interval'];
		}
		
		// 获取统计数据
		$statistics = $courierService->GetStatistics($fromDate, $toDate, $interval);
		$courierTypes = $courierService->GetCourierTypes();
		$totalsByType = $courierService->GetTotalsByType($fromDate, $toDate);
		
		// 添加调试信息
		$debug = [
			'request_params' => [
				'from_date' => $fromDate,
				'to_date' => $toDate,
				'interval' => $interval,
				'format' => isset($queryParams['format']) ? $queryParams['format'] : 'html'
			],
			'statistics_count' => is_array($statistics) ? count($statistics) : 'non-array',
			'courier_types_count' => $courierTypes->count(),
			'totals_by_type' => $totalsByType
		];
		
		// 写入日志
		error_log('Courier Overview Debug: ' . json_encode($debug));
		
		// 修复日期对比问题 - 检查是否有数据在指定的日期范围内
		if (empty($statistics)) {
			// 尝试获取没有日期限制的最新10条记录，用于调试
			$latestEntries = $courierService->GetCourierEntries(null, null, null);
			
			error_log('No statistics found for the given date range. Latest entries: ' . json_encode($latestEntries));
			
			// 如果没有数据，尝试修改日期范围到更广泛的范围
			if (strtotime($fromDate) > strtotime('-1 year')) {
				$expandedFromDate = date('Y-m-d', strtotime('-1 year'));
				$expandedToDate = date('Y-m-d', strtotime('+1 day')); // 包括今天
				
				error_log('Trying expanded date range: ' . $expandedFromDate . ' to ' . $expandedToDate);
				
				// 使用扩展日期范围再次尝试
				$statistics = $courierService->GetStatistics($expandedFromDate, $expandedToDate, $interval);
				$totalsByType = $courierService->GetTotalsByType($expandedFromDate, $expandedToDate);
				
				if (!empty($statistics)) {
					error_log('Found data with expanded date range. Count: ' . count($statistics));
					// 更新日期范围
					$fromDate = $expandedFromDate;
					$toDate = $expandedToDate;
				}
			}
		}
		
		// 如果请求格式为JSON，则返回JSON数据
		if (isset($queryParams['format']) && $queryParams['format'] === 'json')
		{
			return $response->withJson([
				'statistics' => $statistics,
				'totalsByType' => $totalsByType,
				'debug' => $debug
			]);
		}
		
		return $this->renderPage($response, 'courier/overview', [
			'courierTypes' => $courierTypes,
			'statistics' => $statistics,
			'totalsByType' => $totalsByType,
			'fromDate' => $fromDate,
			'toDate' => $toDate,
			'interval' => $interval,
			'debug' => json_encode($debug)
		]);
	}
	
	public function CourierTypes(Request $request, Response $response, array $args)
	{
		$courierService = new CourierService($this->getDatabase());
		$courierTypes = $courierService->GetCourierTypes();
		
		return $this->renderPage($response, 'courier/types', [
			'courierTypes' => $courierTypes
		]);
	}
	
	public function CourierEntries(Request $request, Response $response, array $args)
	{
		$courierService = new CourierService($this->getDatabase());
		$courierTypes = $courierService->GetCourierTypes();
		
		// 获取当前日期，用于显示快递录入表单
		$currentDate = date('Y-m-d');
		
		return $this->renderPage($response, 'courier/entries', [
			'courierTypes' => $courierTypes,
			'currentDate' => $currentDate
		]);
	}
} 
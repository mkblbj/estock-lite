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
		
		// 获取时间范围参数，默认为最近30天
		$fromDate = date('Y-m-d', strtotime('-30 days'));
		$toDate = date('Y-m-d');
		
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
		
		// 如果请求格式为JSON，则返回JSON数据
		if (isset($queryParams['format']) && $queryParams['format'] === 'json')
		{
			return $response->withJson([
				'statistics' => $statistics,
				'totalsByType' => $totalsByType
			]);
		}
		
		return $this->renderPage($response, 'courier/overview', [
			'courierTypes' => $courierTypes,
			'statistics' => $statistics,
			'totalsByType' => $totalsByType,
			'fromDate' => $fromDate,
			'toDate' => $toDate,
			'interval' => $interval
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
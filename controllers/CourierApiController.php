<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Services\CourierService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CourierApiController extends BaseApiController
{
	public function GetCourierTypes(Request $request, Response $response, array $args)
	{
		try
		{
			$courierService = new CourierService($this->getDatabase());
			$courierTypes = $courierService->GetCourierTypes();
			
			return $this->FilteredApiResponse($response, $courierTypes, $request->getQueryParams());
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function GetCourierType(Request $request, Response $response, array $args)
	{
		try
		{
			$courierService = new CourierService($this->getDatabase());
			$courierType = $courierService->GetCourierType($args['typeId']);
			
			return $this->ApiResponse($response, $courierType);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function AddCourierType(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
			
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			
			// 验证请求数据
			if (!array_key_exists('name', $requestBody) || empty($requestBody['name']))
			{
				return $this->GenericErrorResponse($response, 'A name is required');
			}
			
			$courierType = [
				'name' => $requestBody['name'],
				'description' => array_key_exists('description', $requestBody) ? $requestBody['description'] : '',
				'active' => array_key_exists('active', $requestBody) ? intval($requestBody['active']) : 1
			];
			
			$courierService = new CourierService($this->getDatabase());
			$newCourierTypeId = $courierService->AddCourierType($courierType);
			
			return $this->ApiResponse($response, $courierService->GetCourierType($newCourierTypeId));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function UpdateCourierType(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
			
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			
			$courierType = [];
			
			if (array_key_exists('name', $requestBody) && !empty($requestBody['name']))
			{
				$courierType['name'] = $requestBody['name'];
			}
			
			if (array_key_exists('description', $requestBody))
			{
				$courierType['description'] = $requestBody['description'];
			}
			
			if (array_key_exists('active', $requestBody))
			{
				$courierType['active'] = intval($requestBody['active']);
			}
			
			$courierService = new CourierService($this->getDatabase());
			$courierService->UpdateCourierType($args['typeId'], $courierType);
			
			return $this->ApiResponse($response, $courierService->GetCourierType($args['typeId']));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function DeleteCourierType(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
			
			$courierService = new CourierService($this->getDatabase());
			
			// 检查该类型是否已被使用
			if ($courierService->IsCourierTypeInUse($args['typeId']))
			{
				return $this->GenericErrorResponse($response, 'This courier type cannot be deleted because it is already in use');
			}
			
			$courierService->DeleteCourierType($args['typeId']);
			
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function GetCourierEntries(Request $request, Response $response, array $args)
	{
		try
		{
			$queryParams = $request->getQueryParams();
			$fromDate = isset($queryParams['from_date']) ? $queryParams['from_date'] : null;
			$toDate = isset($queryParams['to_date']) ? $queryParams['to_date'] : null;
			$courierTypeId = isset($queryParams['courier_type_id']) ? intval($queryParams['courier_type_id']) : null;
			
			$courierService = new CourierService($this->getDatabase());
			$entries = $courierService->GetCourierEntries($fromDate, $toDate, $courierTypeId);
			
			return $this->FilteredApiResponse($response, $entries, $request->getQueryParams());
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function GetCourierEntry(Request $request, Response $response, array $args)
	{
		try
		{
			$courierService = new CourierService($this->getDatabase());
			$entry = $courierService->GetCourierEntry($args['entryId']);
			
			return $this->ApiResponse($response, $entry);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function AddCourierEntry(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_STOCK_PURCHASE);
			
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			
			// 验证请求数据
			if (!array_key_exists('courier_type_id', $requestBody) || empty($requestBody['courier_type_id']))
			{
				return $this->GenericErrorResponse($response, 'A courier type is required');
			}
			
			if (!array_key_exists('entry_date', $requestBody) || empty($requestBody['entry_date']))
			{
				return $this->GenericErrorResponse($response, 'A date is required');
			}
			
			if (!array_key_exists('count', $requestBody) || !is_numeric($requestBody['count']) || intval($requestBody['count']) < 0)
			{
				return $this->GenericErrorResponse($response, 'A valid count is required');
			}
			
			$entry = [
				'courier_type_id' => intval($requestBody['courier_type_id']),
				'entry_date' => $requestBody['entry_date'],
				'count' => intval($requestBody['count'])
			];
			
			$courierService = new CourierService($this->getDatabase());
			
			// 检查是否已存在相同日期和类型的记录
			$existingEntry = $courierService->GetCourierEntryByDateAndType($entry['entry_date'], $entry['courier_type_id']);
			if ($existingEntry !== null)
			{
				// 更新现有记录
				$courierService->UpdateCourierEntry($existingEntry->id, $entry);
				return $this->ApiResponse($response, $courierService->GetCourierEntry($existingEntry->id));
			}
			else
			{
				// 添加新记录
				$newEntryId = $courierService->AddCourierEntry($entry);
				return $this->ApiResponse($response, $courierService->GetCourierEntry($newEntryId));
			}
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function UpdateCourierEntry(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_STOCK_PURCHASE);
			
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			
			$entry = [];
			
			if (array_key_exists('courier_type_id', $requestBody) && !empty($requestBody['courier_type_id']))
			{
				$entry['courier_type_id'] = intval($requestBody['courier_type_id']);
			}
			
			if (array_key_exists('entry_date', $requestBody) && !empty($requestBody['entry_date']))
			{
				$entry['entry_date'] = $requestBody['entry_date'];
			}
			
			if (array_key_exists('count', $requestBody) && is_numeric($requestBody['count']) && intval($requestBody['count']) >= 0)
			{
				$entry['count'] = intval($requestBody['count']);
			}
			
			$courierService = new CourierService($this->getDatabase());
			$courierService->UpdateCourierEntry($args['entryId'], $entry);
			
			return $this->ApiResponse($response, $courierService->GetCourierEntry($args['entryId']));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function DeleteCourierEntry(Request $request, Response $response, array $args)
	{
		try
		{
			User::checkPermission($request, User::PERMISSION_STOCK_PURCHASE);
			
			$courierService = new CourierService($this->getDatabase());
			$courierService->DeleteCourierEntry($args['entryId']);
			
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
	
	public function GetStatistics(Request $request, Response $response, array $args)
	{
		try
		{
			$queryParams = $request->getQueryParams();
			
			$fromDate = isset($queryParams['from_date']) ? $queryParams['from_date'] : date('Y-m-d', strtotime('-30 days'));
			$toDate = isset($queryParams['to_date']) ? $queryParams['to_date'] : date('Y-m-d');
			$interval = isset($queryParams['interval']) && in_array($queryParams['interval'], ['day', 'week', 'month', 'year']) 
				? $queryParams['interval'] : 'day';
			
			$courierService = new CourierService($this->getDatabase());
			$statistics = $courierService->GetStatistics($fromDate, $toDate, $interval);
			
			return $this->ApiResponse($response, $statistics);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
} 
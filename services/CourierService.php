<?php

namespace Grocy\Services;

class CourierService extends BaseService
{
	public function GetCourierTypes()
	{
		return $this->getDatabaseService()->GetDbConnection()->courier_types()->orderBy('name', 'COLLATE NOCASE');
	}
	
	public function GetCourierType($typeId)
	{
		return $this->getDatabaseService()->GetDbConnection()->courier_types($typeId);
	}
	
	public function AddCourierType($courierType)
	{
		$newRow = $this->getDatabaseService()->GetDbConnection()->courier_types()->createRow($courierType);
		$newRow->save();
		return $newRow->id;
	}
	
	public function UpdateCourierType($typeId, $courierType)
	{
		$this->getDatabaseService()->GetDbConnection()->courier_types($typeId)->update($courierType);
	}
	
	public function DeleteCourierType($typeId)
	{
		$this->getDatabaseService()->GetDbConnection()->courier_types()->where('id', $typeId)->delete();
	}
	
	public function IsCourierTypeInUse($typeId)
	{
		$entriesCount = $this->getDatabaseService()->GetDbConnection()->courier_entries()->where('courier_type_id', $typeId)->count();
		return $entriesCount > 0;
	}
	
	public function GetCourierEntries($fromDate = null, $toDate = null, $courierTypeId = null)
	{
		$query = $this->getDatabaseService()->GetDbConnection()->courier_statistics();
		
		if ($fromDate !== null)
		{
			$query = $query->where('entry_date >= ?', $fromDate);
		}
		
		if ($toDate !== null)
		{
			$query = $query->where('entry_date <= ?', $toDate);
		}
		
		if ($courierTypeId !== null)
		{
			$query = $query->where('courier_id', $courierTypeId);
		}
		
		return $query->orderBy('entry_date', 'DESC')->orderBy('courier_name');
	}
	
	public function GetCourierEntry($entryId)
	{
		return $this->getDatabaseService()->GetDbConnection()->courier_entries($entryId);
	}
	
	public function GetCourierEntryByDateAndType($date, $typeId)
	{
		return $this->getDatabaseService()->GetDbConnection()->courier_entries()
			->where('entry_date', $date)
			->where('courier_type_id', $typeId)
			->fetch();
	}
	
	public function AddCourierEntry($entry)
	{
		$newRow = $this->getDatabaseService()->GetDbConnection()->courier_entries()->createRow($entry);
		$newRow->save();
		return $newRow->id;
	}
	
	public function UpdateCourierEntry($entryId, $entry)
	{
		$this->getDatabaseService()->GetDbConnection()->courier_entries($entryId)->update($entry);
	}
	
	public function DeleteCourierEntry($entryId)
	{
		$this->getDatabaseService()->GetDbConnection()->courier_entries()->where('id', $entryId)->delete();
	}
	
	public function GetStatistics($fromDate, $toDate, $interval = 'day')
	{
		$dateFormat = $this->GetDateFormatForInterval($interval);
		
		$db = $this->getDatabaseService();
		
		// 输出调试日志
		error_log("GetStatistics调用 - 参数: fromDate=$fromDate, toDate=$toDate, interval=$interval, formatPattern=$dateFormat");
		
		// 先检查日期是否有效
		if (strtotime($fromDate) === false || strtotime($toDate) === false) {
			error_log("GetStatistics错误 - 无效的日期: fromDate=$fromDate, toDate=$toDate");
			return [];
		}
		
		// 确保fromDate不大于toDate
		if (strtotime($fromDate) > strtotime($toDate)) {
			$tmp = $fromDate;
			$fromDate = $toDate;
			$toDate = $tmp;
			error_log("GetStatistics警告 - 日期已交换: 新fromDate=$fromDate, 新toDate=$toDate");
		}
		
		// 日期格式化确保数据库可识别
		$fromDate = date('Y-m-d', strtotime($fromDate));
		$toDate = date('Y-m-d', strtotime($toDate));
		
		// 检查视图和表是否存在
		try {
			$viewCheck = $db->ExecuteDbQuery("SELECT COUNT(*) as count FROM sqlite_master WHERE type='view' AND name='courier_statistics'");
			$viewCheckResult = $viewCheck->fetchAll(\PDO::FETCH_ASSOC);
			$viewExists = isset($viewCheckResult[0]['count']) && $viewCheckResult[0]['count'] > 0;
			
			$tableCheck = $db->ExecuteDbQuery("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name='courier_entries'");
			$tableCheckResult = $tableCheck->fetchAll(\PDO::FETCH_ASSOC);
			$tableExists = isset($tableCheckResult[0]['count']) && $tableCheckResult[0]['count'] > 0;
			
			error_log("GetStatistics表检查 - courier_statistics视图存在: " . ($viewExists ? "是" : "否") . ", courier_entries表存在: " . ($tableExists ? "是" : "否"));
			
			if (!$viewExists || !$tableExists) {
				return [];
			}
		} catch (\Exception $e) {
			error_log("GetStatistics错误 - 检查表结构时出错: " . $e->getMessage());
			return [];
		}
		
		$query = 'SELECT 
			strftime(?, entry_date) as interval_key, 
			courier_id, 
			courier_name, 
			SUM(count) as total_count 
		FROM courier_statistics 
		WHERE entry_date BETWEEN ? AND ? 
		GROUP BY interval_key, courier_id, courier_name 
		ORDER BY interval_key ASC, courier_name ASC';
		
		try {
			$entriesStmt = $db->ExecuteDbQuery($query, [$dateFormat, $fromDate, $toDate]);
			$entries = $entriesStmt->fetchAll(\PDO::FETCH_ASSOC);
			error_log("GetStatistics查询结果 - 找到" . count($entries) . "条记录");
			
			// 如果没有数据，检查是否有任何快递记录
			if (empty($entries)) {
				$countCheck = $db->ExecuteDbQuery("SELECT COUNT(*) as count FROM courier_statistics");
				$countCheckResult = $countCheck->fetchAll(\PDO::FETCH_ASSOC);
				$totalRecords = isset($countCheckResult[0]['count']) ? $countCheckResult[0]['count'] : 0;
				
				$rangeCheck = $db->ExecuteDbQuery("SELECT MIN(entry_date) as min_date, MAX(entry_date) as max_date FROM courier_statistics");
				$rangeCheckResult = $rangeCheck->fetchAll(\PDO::FETCH_ASSOC);
				$minDate = isset($rangeCheckResult[0]['min_date']) ? $rangeCheckResult[0]['min_date'] : 'N/A';
				$maxDate = isset($rangeCheckResult[0]['max_date']) ? $rangeCheckResult[0]['max_date'] : 'N/A';
				
				error_log("GetStatistics没有数据 - 总记录数: $totalRecords, 最早日期: $minDate, 最晚日期: $maxDate, 查询范围: $fromDate 到 $toDate");
				
				// 确认是否所有快递类型都是非活跃状态
				$activeTypesCheck = $db->ExecuteDbQuery("SELECT COUNT(*) as count FROM courier_types WHERE active=1");
				$activeTypesResult = $activeTypesCheck->fetchAll(\PDO::FETCH_ASSOC);
				$activeTypesCount = isset($activeTypesResult[0]['count']) ? $activeTypesResult[0]['count'] : 0;
				error_log("GetStatistics活跃的快递类型数: $activeTypesCount");
				
				// 如果没有活跃的快递类型，检查是否有任何快递记录
				if ($activeTypesCount == 0) {
					error_log("GetStatistics警告 - 没有活跃的快递类型，需要激活至少一个快递类型");
					
					// 获取最常用的快递类型并激活
					$mostUsedTypes = $db->ExecuteDbQuery("
						SELECT courier_type_id, COUNT(*) as usage_count 
						FROM courier_entries 
						GROUP BY courier_type_id 
						ORDER BY usage_count DESC 
						LIMIT 5
					");
					$mostUsedTypesResult = $mostUsedTypes->fetchAll(\PDO::FETCH_ASSOC);
					
					if (!empty($mostUsedTypesResult)) {
						foreach ($mostUsedTypesResult as $type) {
							$typeId = $type['courier_type_id'];
							$db->ExecuteDbQuery("UPDATE courier_types SET active=1 WHERE id=?", [$typeId]);
							error_log("GetStatistics自动修复 - 已激活快递类型ID: $typeId");
						}
						
						// 重新执行查询
						$entriesStmt = $db->ExecuteDbQuery($query, [$dateFormat, $fromDate, $toDate]);
						$entries = $entriesStmt->fetchAll(\PDO::FETCH_ASSOC);
						error_log("GetStatistics重新查询结果 - 找到" . count($entries) . "条记录");
					}
				} else {
					// 检查courier_entries表中是否有超出范围的数据
					$entriesCheck = $db->ExecuteDbQuery("
						SELECT COUNT(*) as count 
						FROM courier_entries ce
						JOIN courier_types ct ON ce.courier_type_id = ct.id
						WHERE ct.active = 1 AND ce.entry_date BETWEEN ? AND ?
					", [$fromDate, $toDate]);
					$entriesCheckResult = $entriesCheck->fetchAll(\PDO::FETCH_ASSOC);
					
					$entriesInRange = isset($entriesCheckResult[0]['count']) ? $entriesCheckResult[0]['count'] : 0;
					error_log("GetStatistics范围内的记录数(仅活跃类型): $entriesInRange");
				}
			}
		} catch (\Exception $e) {
			error_log("GetStatistics错误 - 执行查询时出错: " . $e->getMessage());
			return [];
		}
		
		// 将结果重组为按时间间隔分组的结构
		$result = [];
		foreach ($entries as $entry)
		{
			if (!isset($result[$entry['interval_key']]))
			{
				$result[$entry['interval_key']] = [
					'interval_key' => $entry['interval_key'],
					'couriers' => [],
					'total' => 0
				];
			}
			
			$result[$entry['interval_key']]['couriers'][] = [
				'courier_id' => $entry['courier_id'],
				'courier_name' => $entry['courier_name'],
				'count' => $entry['total_count']
			];
			
			$result[$entry['interval_key']]['total'] += $entry['total_count'];
		}
		
		$resultCount = count($result);
		error_log("GetStatistics最终结果 - 分组后有$resultCount条记录");
		
		return array_values($result);
	}
	
	public function GetTotalsByType($fromDate, $toDate)
	{
		$db = $this->getDatabaseService();
		$query = 'SELECT 
			courier_id, 
			courier_name, 
			SUM(count) as total_count 
		FROM courier_statistics 
		WHERE entry_date BETWEEN ? AND ? 
		GROUP BY courier_id, courier_name 
		ORDER BY courier_name ASC';
		
		$entriesStmt = $db->ExecuteDbQuery($query, [$fromDate, $toDate]);
		$entries = $entriesStmt->fetchAll(\PDO::FETCH_ASSOC);
		
		$total = 0;
		foreach ($entries as $entry)
		{
			$total += $entry['total_count'];
		}
		
		return [
			'couriers' => $entries,
			'total' => $total
		];
	}
	
	private function GetDateFormatForInterval($interval)
	{
		switch ($interval)
		{
			case 'week':
				return '%Y-%W'; // 年-周
			case 'month':
				return '%Y-%m'; // 年-月
			case 'year':
				return '%Y'; // 年
			case 'day':
			default:
				return '%Y-%m-%d'; // 年-月-日
		}
	}
} 
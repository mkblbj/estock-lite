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
		$query = 'SELECT 
			strftime(?, entry_date) as interval_key, 
			courier_id, 
			courier_name, 
			SUM(count) as total_count 
		FROM courier_statistics 
		WHERE entry_date BETWEEN ? AND ? 
		GROUP BY interval_key, courier_id, courier_name 
		ORDER BY interval_key ASC, courier_name ASC';
		
		$entries = $db->ExecuteDbQuery($query, [$dateFormat, $fromDate, $toDate]);
		
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
		
		$entries = $db->ExecuteDbQuery($query, [$fromDate, $toDate]);
		
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
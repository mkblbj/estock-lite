<?php

namespace Grocy\Services;

class ProjectTasksService extends BaseService
{
    public function GetTasksByProject($projectName)
    {
        return $this->getDatabase()->project_tasks()
            ->where('project_name', $projectName)
            ->orderBy('priority', 'DESC')
            ->orderBy('id', 'ASC');
    }

    public function GetTaskById($taskId)
    {
        return $this->getDatabase()->project_tasks($taskId);
    }

    public function CreateTask($projectName, $name, $description = '', $status = 'pending', $percentage = 0, $priority = 0, $deadline = null, $assignedTo = null)
    {
        $taskRow = $this->getDatabase()->project_tasks()->createRow([
            'project_name' => $projectName,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'percentage' => $percentage,
            'priority' => $priority,
            'deadline' => $deadline,
            'assigned_to' => $assignedTo
        ]);
        $taskRow->save();

        // 记录任务历史
        $this->AddTaskHistory($taskRow->id, $status, $percentage, $assignedTo);

        return $taskRow;
    }

    public function UpdateTask($taskId, $name, $description = null, $status = null, $percentage = null, $priority = null, $deadline = null, $assignedTo = null)
    {
        $task = $this->GetTaskById($taskId);
        
        if ($task === null) {
            throw new \Exception('任务不存在');
        }

        $updateData = [
            'last_updated_timestamp' => date('Y-m-d H:i:s')
        ];

        // 只有当name不为空时才更新name字段
        if (!empty($name)) $updateData['name'] = $name;
        
        if ($description !== null) $updateData['description'] = $description;
        if ($status !== null) $updateData['status'] = $status;
        if ($percentage !== null) $updateData['percentage'] = $percentage;
        if ($priority !== null) $updateData['priority'] = $priority;
        if ($deadline !== null) $updateData['deadline'] = $deadline;
        if ($assignedTo !== null) $updateData['assigned_to'] = $assignedTo;

        $task->update($updateData);

        // 如果状态或进度发生变化，记录任务历史
        if (($status !== null && $status != $task->status) || 
            ($percentage !== null && $percentage != $task->percentage)) {
            $this->AddTaskHistory(
                $taskId, 
                $status ?? $task->status, 
                $percentage ?? $task->percentage, 
                $assignedTo
            );
        }

        return $task;
    }

    public function DeleteTask($taskId)
    {
        $task = $this->GetTaskById($taskId);
        
        if ($task === null) {
            throw new \Exception('任务不存在');
        }

        $task->delete();
        return true;
    }

    public function GetTaskHistory($taskId)
    {
        return $this->getDatabase()->project_task_history()
            ->where('task_id', $taskId)
            ->orderBy('row_created_timestamp DESC');
    }

    private function AddTaskHistory($taskId, $status, $percentage, $changedBy = null)
    {
        $historyRow = $this->getDatabase()->project_task_history()->createRow([
            'task_id' => $taskId,
            'status' => $status,
            'percentage' => $percentage,
            'changed_by' => $changedBy
        ]);
        $historyRow->save();
        return $historyRow;
    }

    public function GetTaskStatistics($projectName)
    {
        // 计算各状态任务数量
        $stats = [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'total_percentage' => 0
        ];

        $tasks = $this->GetTasksByProject($projectName);
        $taskCount = 0;
        
        foreach ($tasks as $task) {
            $taskCount++;
            $stats['total']++;
            
            if ($task->status == 'completed') {
                $stats['completed']++;
            } elseif ($task->status == 'in_progress') {
                $stats['in_progress']++;
            } elseif ($task->status == 'pending') {
                $stats['pending']++;
            }
            
            $stats['total_percentage'] += $task->percentage;
        }
        
        // 计算总体完成百分比
        if ($taskCount > 0) {
            $stats['total_percentage'] = round($stats['total_percentage'] / $taskCount);
        } else {
            $stats['total_percentage'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * 获取即将到期的任务
     * 
     * @param string $projectName 项目名称
     * @param int $daysThreshold 到期天数阈值，默认为7天
     * @return array 即将到期的任务列表
     */
    public function GetUpcomingTasks($projectName, $daysThreshold = 7)
    {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+$daysThreshold days"));
        
        return $this->getDatabase()->project_tasks()
            ->where('project_name', $projectName)
            ->where('deadline >= ?', $today)
            ->where('deadline <= ?', $futureDate)
            ->where('status != ?', 'completed')
            ->orderBy('deadline', 'ASC')
            ->orderBy('priority', 'DESC');
    }
    
    /**
     * 获取已过期的任务
     * 
     * @param string $projectName 项目名称
     * @return array 已过期的任务列表
     */
    public function GetOverdueTasks($projectName)
    {
        $today = date('Y-m-d');
        
        return $this->getDatabase()->project_tasks()
            ->where('project_name', $projectName)
            ->where('deadline < ?', $today)
            ->where('status != ?', 'completed')
            ->orderBy('deadline', 'ASC')
            ->orderBy('priority', 'DESC');
    }
} 
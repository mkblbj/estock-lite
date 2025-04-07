<?php

namespace Grocy\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ProjectProgressController extends BaseController
{
	public function Overview(Request $request, Response $response, array $args)
	{
		// 添加chartjs前端包需求
		require_frontend_packages(['chartjs']);
		
		// 获取当前页码
		$currentPage = intval($request->getQueryParam('page', 1));
		if ($currentPage < 1) {
			$currentPage = 1;
		}
		
		// 获取每页显示数量
		$perPage = intval($request->getQueryParam('per_page', 20));
		if ($perPage < 1 || $perPage > 100) {
			$perPage = 20;
		}
		
		// 获取当前选择的项目
		$selectedProject = $request->getQueryParam('project', '');
		
		// 获取所有可用的Git项目
		$allProjects = $this->getGitProjects();
		
		// 如果没有选择项目或选择的项目不存在，则使用当前项目
		if (empty($selectedProject) || !isset($allProjects[$selectedProject])) {
			$selectedProject = basename(dirname(__DIR__));
		}
		
		// 保存选择的项目到全局变量，以便在其他方法中使用
		$GLOBALS['GROCY_SELECTED_PROJECT'] = $selectedProject;
		
		// 获取Git提交记录，包含分页信息
		// 如果URL中带有时间戳参数，则表示是强制刷新
		$forceRefresh = $request->getQueryParam('_') !== null;
		$gitData = $this->getGitCommits($currentPage, $perPage, $forceRefresh, $selectedProject);
		
		// 获取项目任务统计信息
		$taskStatistics = $this->getProjectTasksService()->GetTaskStatistics($selectedProject);
		
		// 获取已过期任务
		$overdueTasks = $this->getProjectTasksService()->GetOverdueTasks($selectedProject);
		
		// 获取即将到期任务
		$upcomingTasks = $this->getProjectTasksService()->GetUpcomingTasks($selectedProject, 7);
		
		// 检查是否有成功消息
		$successMessage = null;
		if ($request->getQueryParam('success') === 'saved') {
			$successMessage = '需求文档已保存';
		}
		
		return $this->renderPage($response, 'projectprogress', [
			'gitCommits' => $gitData['commits'],
			'pagination' => $gitData['pagination'],
			'requirements' => $this->getRequirements($selectedProject),
			'progressTasks' => $this->getProgressTasks(),
			'taskStatistics' => $taskStatistics,
			'overdueTasks' => $overdueTasks,
			'upcomingTasks' => $upcomingTasks,
			'successMessage' => $successMessage,
			'allProjects' => $allProjects,
			'selectedProject' => $selectedProject
		]);
	}

	private function getGitCommits($page = 1, $perPage = 20, $forceRefresh = false, $projectName = '')
	{
		$commits = [];
		
		// 确定Git仓库路径
		$gitDir = $this->getGitDirPath($projectName);

		// 确保Git仓库存在
		if (!is_dir($gitDir)) {
			return [
				'commits' => [],
				'pagination' => [
					'total' => 0,
					'page' => $page,
					'per_page' => $perPage,
					'total_pages' => 0
				]
			];
		}

		// 如果强制刷新，先尝试更新本地仓库
		if ($forceRefresh) {
			// 尝试执行git fetch更新仓库
			$fetchCommand = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git fetch --all';
			exec($fetchCommand);
		}

		// 获取总提交数
		$totalCountCommand = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git rev-list --count HEAD';
		$totalCount = 0;
		exec($totalCountCommand, $countOutput);
		if (!empty($countOutput)) {
			$totalCount = intval($countOutput[0]);
		}
		
		// 计算分页信息
		$totalPages = ceil($totalCount / $perPage);
		if ($page > $totalPages && $totalPages > 0) {
			$page = $totalPages;
		}
		
		// 计算偏移量
		$skip = ($page - 1) * $perPage;
		
		// 使用git命令获取提交记录，带分页
		$command = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git log --pretty=format:\'{"hash":"%H","short_hash":"%h","subject":"%s","author":"%an","date":"%ad","refs":"%D"}\' --date=format:"%Y-%m-%d %H:%M:%S" --name-status --skip=' . $skip . ' -n ' . $perPage;
		$output = [];
		exec($command, $output);

		$currentCommit = null;
		foreach ($output as $line) {
			if (strpos($line, '{"hash"') === 0) {
				// 这是一个新的提交记录
				if ($currentCommit !== null) {
					$commits[] = $currentCommit;
				}
				$currentCommit = json_decode($line, true);
				$currentCommit['files'] = [];
				
				// 解析refs字段获取分支和标签信息
				$refs = trim($currentCommit['refs']);
				$currentCommit['branches'] = [];
				$currentCommit['tags'] = [];
				
				if (!empty($refs)) {
					$refParts = explode(',', $refs);
					foreach ($refParts as $ref) {
						$ref = trim($ref);
						if (strpos($ref, 'tag:') === 0) {
							// 这是一个标签
							$currentCommit['tags'][] = substr($ref, 4);
						} elseif (strpos($ref, 'HEAD ->') !== false) {
							// 当前HEAD指向的分支
							$branchName = trim(substr($ref, strpos($ref, '->') + 2));
							$currentCommit['branches'][] = [
								'name' => $branchName,
								'color' => $this->getBranchColor($branchName)
							];
						} elseif (!empty($ref) && $ref != 'HEAD') {
							// 其他分支
							$currentCommit['branches'][] = [
								'name' => $ref,
								'color' => $this->getBranchColor($ref)
							];
						}
					}
				}
			} elseif (!empty(trim($line)) && $currentCommit !== null) {
				// 这是提交中修改的文件
				$parts = preg_split('/\s+/', trim($line), 2);
				if (count($parts) == 2) {
					$status = $parts[0];
					$file = $parts[1];
					$currentCommit['files'][] = ['status' => $status, 'path' => $file];
				}
			}
		}

		// 添加最后一个提交记录
		if ($currentCommit !== null) {
			$commits[] = $currentCommit;
		}

		// 返回提交记录和分页信息
		return [
			'commits' => $commits,
			'pagination' => [
				'total' => $totalCount,
				'page' => $page,
				'per_page' => $perPage,
				'total_pages' => $totalPages
			]
		];
	}
	
	/**
	 * 获取Git仓库目录路径
	 * 
	 * @param string $projectName 项目名称
	 * @return string Git仓库路径
	 */
	private function getGitDirPath($projectName = '')
	{
		// 如果未指定项目名称，则使用当前项目
		if (empty($projectName)) {
			$projectName = basename(dirname(__DIR__));
			return __DIR__ . '/../.git';
		}
		
		// 获取上一级目录
		$devDir = dirname(dirname(__DIR__));
		
		// 返回指定项目的Git目录
		return $devDir . '/' . $projectName . '/.git';
	}
	
	/**
	 * 获取/dev目录下所有Git项目
	 * 
	 * @return array 项目列表，键为项目名称，值为项目信息
	 */
	private function getGitProjects()
	{
		$projects = [];
		
		// 获取上一级目录
		$devDir = dirname(dirname(__DIR__));
		
		// 扫描上一级目录
		$dirs = scandir($devDir);
		
		foreach ($dirs as $dir) {
			// 跳过特殊目录
			if ($dir === '.' || $dir === '..' || !is_dir($devDir . '/' . $dir)) {
				continue;
			}
			
			// 检查是否为Git仓库
			if (is_dir($devDir . '/' . $dir . '/.git')) {
				// 获取仓库信息
				$projectInfo = $this->getProjectInfo($dir);
				$projects[$dir] = $projectInfo;
			}
		}
		
		// 按项目名称排序
		ksort($projects);
		
		return $projects;
	}
	
	/**
	 * 获取项目信息
	 * 
	 * @param string $projectName 项目名称
	 * @return array 项目信息
	 */
	private function getProjectInfo($projectName)
	{
		$gitDir = $this->getGitDirPath($projectName);
		
		// 默认项目信息
		$info = [
			'name' => $projectName,
			'branch' => '',
			'last_commit' => '',
			'last_commit_date' => '',
			'commits_count' => 0
		];
		
		// 如果Git目录不存在，直接返回默认信息
		if (!is_dir($gitDir)) {
			return $info;
		}
		
		// 获取当前分支
		$branchCommand = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git rev-parse --abbrev-ref HEAD';
		exec($branchCommand, $branchOutput);
		if (!empty($branchOutput)) {
			$info['branch'] = $branchOutput[0];
		}
		
		// 获取最后一次提交信息
		$lastCommitCommand = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git log -1 --pretty=format:"%s|%an|%ad" --date=format:"%Y-%m-%d %H:%M:%S"';
		exec($lastCommitCommand, $lastCommitOutput);
		if (!empty($lastCommitOutput)) {
			$parts = explode('|', $lastCommitOutput[0]);
			if (count($parts) === 3) {
				$info['last_commit'] = $parts[0];
				$info['last_commit_author'] = $parts[1];
				$info['last_commit_date'] = $parts[2];
			}
		}
		
		// 获取提交总数
		$countCommand = 'cd ' . escapeshellarg(dirname($gitDir)) . ' && git rev-list --count HEAD';
		exec($countCommand, $countOutput);
		if (!empty($countOutput)) {
			$info['commits_count'] = intval($countOutput[0]);
		}
		
		return $info;
	}

	/**
	 * 获取项目的需求文档
	 * 
	 * @param string $projectName 项目名称
	 * @return array 需求文档列表
	 */
	private function getRequirements($projectName = '')
	{
		$docsList = [];
		
		// 如果未指定项目名称，则使用当前项目
		if (empty($projectName)) {
			$projectName = basename(dirname(__DIR__));
		}
		
		// 获取项目根目录
		$projectDir = $this->getProjectDir($projectName);
		
		// 检查项目目录是否存在
		if (!is_dir($projectDir)) {
			return $docsList;
		}
		
		// 仅查找项目根目录下的README.md文件
		$readmePath = $projectDir . '/README.md';
		if (file_exists($readmePath)) {
			$docsList['readme'] = [
				'title' => 'README',
				'path' => $readmePath,
				'content' => file_get_contents($readmePath)
			];
		}
		
		// 查找docs目录下包含requirement的文档
		$docsDir = $projectDir . '/docs';
		if (is_dir($docsDir)) {
			$files = scandir($docsDir);
			foreach ($files as $file) {
				// 跳过目录和非md文件
				if (is_dir($docsDir . '/' . $file) || !preg_match('/\.md$/i', $file)) {
					continue;
				}
				
				// 检查文件名是否包含requirement
				if (stripos($file, 'requirement') !== false) {
					$filePath = $docsDir . '/' . $file;
					$docsList['doc_' . md5($file)] = [
						'title' => str_replace(['.md', '_', '-'], ['', ' ', ' '], $file),
						'path' => $filePath,
						'content' => file_exists($filePath) ? file_get_contents($filePath) : ''
					];
				}
			}
		}
		
		return $docsList;
	}
	
	/**
	 * 获取项目目录路径
	 * 
	 * @param string $projectName 项目名称
	 * @return string 项目目录路径
	 */
	private function getProjectDir($projectName = '')
	{
		// 如果未指定项目名称，则使用当前项目
		if (empty($projectName)) {
			$projectName = basename(dirname(__DIR__));
			return dirname(__DIR__);
		}
		
		// 获取上一级目录
		$devDir = dirname(dirname(__DIR__));
		
		// 返回指定项目的目录
		return $devDir . '/' . $projectName;
	}

	private function getProgressTasks()
	{
		// 从数据库中获取项目任务
		$selectedProject = $GLOBALS['GROCY_SELECTED_PROJECT'] ?? '';
		if (empty($selectedProject)) {
			$selectedProject = basename(getcwd());
		}
		
		$tasks = [];
		
		try {
			$projectTasks = $this->getProjectTasksService()->GetTasksByProject($selectedProject);
			
			foreach ($projectTasks as $task) {
				$taskItem = [
					'id' => $task->id,
					'name' => $task->name,
					'description' => $task->description,
					'status' => $task->status,
					'percentage' => $task->percentage,
					'priority' => $task->priority,
					'deadline' => $task->deadline,
					'assigned_to' => $task->assigned_to
				];
				
				// 检查任务是否已过期
				if (!empty($task->deadline)) {
					$deadline = new \DateTime($task->deadline);
					$today = new \DateTime('today');
					
					if ($deadline < $today && $task->status !== 'completed') {
						$taskItem['overdue'] = true;
					}
				}
				
				$tasks[] = $taskItem;
			}
			
			if (empty($tasks)) {
				// 如果没有找到任务，添加默认的说明
				$tasks[] = [
					'id' => 0,
					'name' => '暂无任务',
					'description' => '请为' . $selectedProject . '项目添加任务',
					'status' => 'pending',
					'percentage' => 0,
					'priority' => 0
				];
			}
		} catch (\Exception $e) {
			// 出错时返回空数组
			$this->getLogger()->error('获取任务列表失败: ' . $e->getMessage());
		}
		
		return $tasks;
	}

	public function UpdateProgress(Request $request, Response $response, array $args)
	{
		$postParams = $request->getParsedBody();
		$taskId = intval($postParams['task_id'] ?? 0);
		$percentage = intval($postParams['percentage'] ?? 0);
		$status = $postParams['status'] ?? 'pending';
		$selectedProject = $postParams['project'] ?? '';
		$name = $postParams['name'] ?? '';
		$description = $postParams['description'] ?? '';
		$priority = intval($postParams['priority'] ?? 0);
		$deadline = $postParams['deadline'] ?? null;
		$assignedTo = $postParams['assigned_to'] ?? null;
		
		try {
			// 检查是否为新任务
			if ($taskId == 0) {
				// 创建新任务
				if (empty($name)) {
					throw new \Exception('任务名称不能为空');
				}
				
				$task = $this->getProjectTasksService()->CreateTask(
					$selectedProject,
					$name,
					$description,
					$status,
					$percentage,
					$priority,
					$deadline,
					$assignedTo
				);
				
				$taskId = $task->id;
				$message = '任务创建成功';
			} else {
				// 更新已有任务
				$task = $this->getProjectTasksService()->UpdateTask(
					$taskId,
					$name,
					$description,
					$status,
					$percentage,
					$priority,
					$deadline,
					$assignedTo
				);
				
				$message = '任务更新成功';
			}
			
			// 获取最新统计数据
			$statistics = $this->getProjectTasksService()->GetTaskStatistics($selectedProject);
			
			return $response->withJson([
				'success' => true,
				'message' => $message,
				'task_id' => $taskId,
				'percentage' => $percentage,
				'status' => $status,
				'project' => $selectedProject,
				'statistics' => [
					'total_count' => $statistics->total_count,
					'completed_count' => $statistics->completed_count,
					'in_progress_count' => $statistics->in_progress_count,
					'pending_count' => $statistics->pending_count,
					'completed_percentage' => $statistics->completed_percentage
				]
			]);
		} catch (\Exception $ex) {
			return $response->withStatus(400)->withJson([
				'success' => false,
				'message' => $ex->getMessage()
			]);
		}
	}
	
	public function DeleteTask(Request $request, Response $response, array $args)
	{
		$taskId = intval($args['taskId'] ?? 0);
		$selectedProject = $request->getQueryParam('project', '');
		
		try {
			if ($taskId <= 0) {
				throw new \Exception('任务ID无效');
			}
			
			$this->getProjectTasksService()->DeleteTask($taskId);
			
			// 获取最新统计数据
			$statistics = $this->getProjectTasksService()->GetTaskStatistics($selectedProject);
			
			return $response->withJson([
				'success' => true,
				'message' => '任务已删除',
				'statistics' => [
					'total_count' => $statistics->total_count,
					'completed_count' => $statistics->completed_count,
					'in_progress_count' => $statistics->in_progress_count,
					'pending_count' => $statistics->pending_count,
					'completed_percentage' => $statistics->completed_percentage
				]
			]);
		} catch (\Exception $ex) {
			return $response->withStatus(400)->withJson([
				'success' => false,
				'message' => $ex->getMessage()
			]);
		}
	}
	
	public function GetTaskDetails(Request $request, Response $response, array $args)
	{
		$taskId = intval($args['taskId'] ?? 0);
		
		try {
			if ($taskId <= 0) {
				throw new \Exception('任务ID无效');
			}
			
			$task = $this->getProjectTasksService()->GetTaskById($taskId);
			if ($task === null) {
				throw new \Exception('任务不存在');
			}
			
			// 获取任务历史记录
			$history = [];
			$dbHistory = $this->getProjectTasksService()->GetTaskHistory($taskId);
			
			foreach ($dbHistory as $record) {
				$history[] = [
					'id' => $record->id,
					'status' => $record->status,
					'percentage' => $record->percentage,
					'changed_by' => $record->changed_by,
					'timestamp' => $record->row_created_timestamp
				];
			}
			
			return $response->withJson([
				'success' => true,
				'task' => [
					'id' => $task->id,
					'name' => $task->name,
					'description' => $task->description,
					'status' => $task->status,
					'percentage' => $task->percentage,
					'priority' => $task->priority,
					'deadline' => $task->deadline,
					'assigned_to' => $task->assigned_to,
					'created' => $task->row_created_timestamp,
					'updated' => $task->last_updated_timestamp
				],
				'history' => $history
			]);
		} catch (\Exception $ex) {
			return $response->withStatus(400)->withJson([
				'success' => false,
				'message' => $ex->getMessage()
			]);
		}
	}

	/**
	 * 获取项目所有任务历史记录
	 */
	public function GetProjectTaskHistory(Request $request, Response $response, array $args)
	{
		$selectedProject = $request->getQueryParam('project', '');
		
		try {
			if (empty($selectedProject)) {
				throw new \Exception('项目名称不能为空');
			}
			
			// 获取项目所有任务
			$allTasks = $this->getProjectTasksService()->GetTasksByProject($selectedProject);
			
			// 如果项目没有任务，返回空数组
			if (empty($allTasks)) {
				return $response->withJson([
					'success' => true,
					'history' => []
				]);
			}
			
			// 获取所有任务的历史记录
			$history = [];
			
			foreach ($allTasks as $task) {
				$taskHistory = $this->getProjectTasksService()->GetTaskHistory($task->id);
				
				if (!empty($taskHistory)) {
					foreach ($taskHistory as $record) {
						$history[] = [
							'id' => $record->id,
							'task_id' => $task->id,
							'task_name' => $task->name,
							'status' => $record->status,
							'percentage' => $record->percentage,
							'changed_by' => $record->changed_by,
							'timestamp' => $record->row_created_timestamp
						];
					}
				}
			}
			
			// 按时间戳倒序排序，最新的记录在前面
			usort($history, function($a, $b) {
				return strtotime($b['timestamp']) - strtotime($a['timestamp']);
			});
			
			// 限制返回前100条记录
			$history = array_slice($history, 0, 100);
			
			return $response->withJson([
				'success' => true,
				'history' => $history
			]);
		} catch (\Exception $ex) {
			return $response->withStatus(400)->withJson([
				'success' => false,
				'message' => $ex->getMessage()
			]);
		}
	}

	/**
	 * 根据分支名获取颜色
	 * 
	 * @param string $branchName 分支名
	 * @return string 颜色代码
	 */
	private function getBranchColor($branchName)
	{
		// 常用分支预定义颜色
		$predefinedColors = [
			'master' => 'primary', // 蓝色
			'main' => 'primary',   // 蓝色
			'develop' => 'info',   // 浅蓝色
			'dev' => 'info',       // 浅蓝色
			'feature/' => 'success', // 绿色
			'release/' => 'warning', // 黄色
			'hotfix/' => 'danger',  // 红色
			'bugfix/' => 'danger',  // 红色
			'fix/' => 'danger',     // 红色
			'test/' => 'secondary', // 灰色
			'origin/' => 'dark',    // 深灰色
		];
		
		// 检查分支名是否匹配预定义颜色
		foreach ($predefinedColors as $prefix => $color) {
			if (strpos($branchName, $prefix) === 0) {
				return $color;
			}
		}
		
		// 如果没有预定义颜色，使用基于分支名哈希的随机颜色
		$customColors = ['indigo', 'purple', 'pink', 'orange', 'teal', 'cyan', 'gray', 'indigo-light', 'purple-light', 'pink-light', 'orange-light', 'teal-light', 'cyan-light'];
		$hash = crc32($branchName);
		$index = abs($hash) % count($customColors);
		
		return $customColors[$index];
	}

	/**
	 * 返回任务列表HTML部分内容，用于AJAX刷新
	 */
	public function TasksPartial(Request $request, Response $response, array $args)
	{
		$selectedProject = $request->getQueryParam('project', '');
		if (empty($selectedProject)) {
			$selectedProject = basename(dirname(__DIR__));
		}
		
		// 保存选择的项目到全局变量，以便在其他方法中使用
		$GLOBALS['GROCY_SELECTED_PROJECT'] = $selectedProject;
		
		// 获取任务列表
		$progressTasks = $this->getProgressTasks();
		
		// 获取任务统计信息
		$taskStatistics = $this->getProjectTasksService()->GetTaskStatistics($selectedProject);
		
		// 渲染部分视图
		return $this->renderPage($response, 'projectprogress-tasks-partial', [
			'progressTasks' => $progressTasks,
			'taskStatistics' => $taskStatistics,
			'selectedProject' => $selectedProject
		]);
	}
} 
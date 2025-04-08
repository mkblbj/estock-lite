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
		
		// 记录详细的调试信息
		$this->debug('开始获取Git提交记录', [
			'项目名' => $projectName,
			'页码' => $page,
			'每页记录数' => $perPage,
			'强制刷新' => $forceRefresh ? 'Yes' : 'No',
			'当前目录' => getcwd(),
			'控制器目录' => __DIR__,
			'脚本路径' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
			'运行用户' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'unknown'
		]);
		
		// 确定Git仓库路径
		$gitDir = $this->getGitDirPath($projectName);
		$repoDir = dirname($gitDir);
		
		$this->debug('Git路径信息', [
			'Git目录' => $gitDir,
			'仓库目录' => $repoDir,
			'Git目录存在' => is_dir($gitDir) ? 'Yes' : 'No',
			'仓库目录存在' => is_dir($repoDir) ? 'Yes' : 'No'
		]);
		
		// 检查权限
		if (is_dir($gitDir)) {
			$this->debug('Git目录权限', [
				'可读' => is_readable($gitDir) ? 'Yes' : 'No',
				'可写' => is_writable($gitDir) ? 'Yes' : 'No',
				'可执行' => is_executable($gitDir) ? 'Yes' : 'No',
				'所有者' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($gitDir))['name'] : 'unknown',
				'组' => function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($gitDir))['name'] : 'unknown'
			]);
		}

		// 确保Git仓库存在
		if (!is_dir($gitDir)) {
			$this->debug('错误：Git目录不存在', $gitDir);
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
		
		// 测试执行git命令
		$oldDir = getcwd();
		$gitCmdSuccess = false;
		
		// 尝试切换到仓库目录
		if (@chdir($repoDir)) {
			$testCmd = 'git --version';
			$output = [];
			$returnCode = -1;
			
			// 执行git版本命令测试
			exec($testCmd . ' 2>&1', $output, $returnCode);
			$gitCmdSuccess = ($returnCode === 0);
			
			$this->debug('Git命令测试', [
				'命令' => $testCmd,
				'返回码' => $returnCode,
				'输出' => $output,
				'成功' => $gitCmdSuccess ? 'Yes' : 'No'
			]);
			
			// 切回原目录
			chdir($oldDir);
		} else {
			$this->debug('错误：无法切换到仓库目录', $repoDir);
		}
		
		// 如果无法执行git命令，直接返回空结果
		if (!$gitCmdSuccess) {
			$this->debug('错误：无法执行Git命令，返回空结果');
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

		// 保存当前目录
		$oldDir = getcwd();
		
		// 切换到仓库目录
		if (!@chdir($repoDir)) {
			$this->debug('错误：无法切换到仓库目录', $repoDir);
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
			$fetchCommand = 'git fetch --all 2>&1';
			$this->debug('执行命令', $fetchCommand);
			
			$fetchOutput = [];
			$fetchReturnVar = -1;
			exec($fetchCommand, $fetchOutput, $fetchReturnVar);
			
			$this->debug('命令结果', [
				'返回码' => $fetchReturnVar,
				'输出' => $fetchOutput
			]);
		}

		// 获取总提交数
		$totalCountCommand = 'git rev-list --count HEAD 2>&1';
		$this->debug('执行命令', $totalCountCommand);
		
		$countOutput = [];
		$countReturnVar = -1;
		exec($totalCountCommand, $countOutput, $countReturnVar);
		
		$this->debug('命令结果', [
			'返回码' => $countReturnVar,
			'输出' => $countOutput
		]);
		
		$totalCount = 0;
		if (!empty($countOutput) && $countReturnVar === 0) {
			$totalCount = intval($countOutput[0]);
		} else {
			$this->debug('错误：无法获取提交总数');
		}
		
		// 计算分页信息
		$totalPages = ceil($totalCount / $perPage);
		if ($page > $totalPages && $totalPages > 0) {
			$page = $totalPages;
		}
		
		// 计算偏移量
		$skip = ($page - 1) * $perPage;
		
		// 使用git命令获取提交记录，带分页
		$command = 'git log --pretty=format:\'{"hash":"%H","short_hash":"%h","subject":"%s","author":"%an","date":"%ad","refs":"%D"}\' --date=format:"%Y-%m-%d %H:%M:%S" --name-status --skip=' . $skip . ' -n ' . $perPage . ' 2>&1';
		$this->debug('执行命令', $command);
		
		$output = [];
		$returnVar = -1;
		exec($command, $output, $returnVar);
		
		$this->debug('命令结果', [
			'返回码' => $returnVar,
			'输出行数' => count($output)
		]);
		
		// 切回原来的目录
		chdir($oldDir);
		
		// 如果命令执行失败
		if ($returnVar !== 0) {
			$this->debug('错误：获取Git提交记录失败', [
				'返回码' => $returnVar,
				'输出' => array_slice($output, 0, 5) // 只显示前5行
			]);
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
		// 使用debug函数记录信息
		$this->debug('getGitDirPath调用', [
			'项目名' => $projectName,
			'当前目录' => getcwd(),
			'控制器目录' => __DIR__,
			'父级目录' => dirname(__DIR__),
			'上上级目录' => dirname(dirname(__DIR__)),
			'文档根目录' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
		]);
		
		// 定义可能的开发目录路径 - 按优先级尝试
		$possibleDevDirs = [
			dirname(dirname(__DIR__)),      // 标准相对路径
			'/home/uo/dev',                 // 绝对路径
			dirname(getcwd()),              // 当前工作目录的父目录
			dirname($_SERVER['DOCUMENT_ROOT'] ?? getcwd()) // 文档根目录的父目录
		];
		
		// 如果未指定项目名称，则使用当前项目
		if (empty($projectName)) {
			$projectName = basename(dirname(__DIR__));
			$gitDir = __DIR__ . '/../.git';
			
			$this->debug('未指定项目', [
				'默认项目名' => $projectName,
				'Git目录' => $gitDir,
				'目录存在' => is_dir($gitDir) ? 'Yes' : 'No'
			]);
			
			return $gitDir;
		}
		
		// 尝试所有可能的开发目录路径
		foreach ($possibleDevDirs as $devDir) {
			$gitDir = $devDir . '/' . $projectName . '/.git';
			$projectDir = $devDir . '/' . $projectName;
			
			$this->debug('尝试Git路径', [
				'开发目录' => $devDir,
				'项目目录' => $projectDir,
				'Git目录' => $gitDir,
				'项目目录存在' => is_dir($projectDir) ? 'Yes' : 'No',
				'Git目录存在' => is_dir($gitDir) ? 'Yes' : 'No'
			]);
			
			// 如果找到有效的Git目录，返回它
			if (is_dir($gitDir)) {
				$this->debug('找到有效的Git目录', $gitDir);
				return $gitDir;
			}
		}
		
		// 如果所有尝试都失败，返回标准路径（即使它不存在）
		$gitDir = dirname(dirname(__DIR__)) . '/' . $projectName . '/.git';
		$this->debug('所有路径尝试都失败，返回标准路径', $gitDir);
		return $gitDir;
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
					'total_count' => $statistics['total'] ?? 0,
					'completed_count' => $statistics['completed'] ?? 0,
					'in_progress_count' => $statistics['in_progress'] ?? 0,
					'pending_count' => $statistics['pending'] ?? 0,
					'completed_percentage' => $statistics['total_percentage'] ?? 0
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
					'total_count' => $statistics['total'] ?? 0,
					'completed_count' => $statistics['completed'] ?? 0,
					'in_progress_count' => $statistics['in_progress'] ?? 0,
					'pending_count' => $statistics['pending'] ?? 0,
					'completed_percentage' => $statistics['total_percentage'] ?? 0
				]
			]);
		} catch (\Exception $ex) {
			return $response->withStatus(400)->withJson([
				'success' => false,
				'message' => $ex->getMessage()
			]);
		}
	}
	
	/**
     * 获取项目任务统计数据
     */
    public function GetProjectStatistics(Request $request, Response $response, array $args)
    {
        $selectedProject = $request->getQueryParam('project', '');
        
        try {
            // 获取统计数据
            $statistics = $this->getProjectTasksService()->GetTaskStatistics($selectedProject);
            
            return $response->withJson([
                'success' => true,
                'statistics' => [
                    'total_count' => $statistics['total'] ?? 0,
                    'completed_count' => $statistics['completed'] ?? 0,
                    'in_progress_count' => $statistics['in_progress'] ?? 0,
                    'pending_count' => $statistics['pending'] ?? 0,
                    'completed_percentage' => $statistics['total_percentage'] ?? 0
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
	public function GetTasksPartial(Request $request, Response $response, array $args)
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

	/**
	 * 获取Git提交记录部分视图（用于AJAX刷新）
	 */
	public function GetGitCommitsPartial(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
	{
		// 获取查询参数中的project和page
		$params = $request->getQueryParams();
		$selectedProject = isset($params['project']) ? $params['project'] : null;
		$page = isset($params['page']) ? (int)$params['page'] : 1;
		$perPage = isset($params['per_page']) ? (int)$params['per_page'] : 20;
		
		// 设置全局变量
		$GLOBALS['GROCY_SELECTED_PROJECT'] = $selectedProject;
		
		// 强制刷新
		$forceRefresh = isset($params['_']);
		
		// 获取Git提交记录
		$gitData = $this->getGitCommits($page, $perPage, $forceRefresh, $selectedProject);
		
		// 渲染部分视图
		return $this->renderPage($response, 'projectprogress-git-commits-partial', [
			'gitCommits' => $gitData['commits'],
			'pagination' => $gitData['pagination'],
			'selectedProject' => $selectedProject
		]);
	}
	
	/**
	 * 获取需求文档部分视图（用于AJAX刷新）
	 */
	public function GetRequirementsPartial(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
	{
		// 获取查询参数中的project
		$params = $request->getQueryParams();
		$selectedProject = isset($params['project']) ? $params['project'] : null;
		
		// 设置全局变量
		$GLOBALS['GROCY_SELECTED_PROJECT'] = $selectedProject;
		
		// 获取需求文档
		$requirements = $this->getRequirements($selectedProject);
		
		// 为Markdown内容生成HTML
		foreach ($requirements as $key => $doc) {
			$html = '';
			if (!empty($doc['content'])) {
				// 使用简单的方式将Markdown转换为HTML
				$html = nl2br(htmlspecialchars($doc['content']));
				// 替换Markdown标题
				$html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
				$html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
				$html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
				// 替换Markdown列表
				$html = preg_replace('/^- (.+)$/m', '<ul><li>$1</li></ul>', $html);
				// 替换Markdown代码块
				$html = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $html);
			}
			$requirements[$key]['html'] = $html;
		}
		
		// 渲染部分视图
		return $this->renderPage($response, 'projectprogress-requirements-partial', [
			'requirements' => $requirements,
			'selectedProject' => $selectedProject
		]);
	}

	/**
	 * 输出调试信息到日志和浏览器控制台
	 * 
	 * @param string $message 调试信息
	 * @param mixed $data 要打印的数据
	 */
	private function debug($message, $data = null)
	{
		// 输出到错误日志
		if ($data !== null) {
			$logMessage = $message . ': ' . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
		} else {
			$logMessage = $message;
		}
		error_log($logMessage);
		
		// 输出到浏览器控制台
		echo "<!--\nDEBUG: $logMessage\n-->\n";
		
		// 如果是AJAX请求，添加到响应头
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
			header('X-Debug-Info: ' . $message);
		}
	}

	/**
	 * 详细调试路由，用于检查路径和权限问题
	 */
	public function DebugGitInfo(Request $request, Response $response, array $args)
	{
		$projectName = $request->getQueryParam('project', '');
		$result = [
			'time' => date('Y-m-d H:i:s'),
			'environment' => [],
			'paths' => [],
			'permissions' => [],
			'git_commands' => []
		];
		
		// 环境信息
		$result['environment'] = [
			'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
			'php_version' => PHP_VERSION,
			'os' => PHP_OS,
			'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
			'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
			'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
			'current_user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'function not available',
			'current_group' => function_exists('posix_getgrgid') ? posix_getgrgid(posix_getegid())['name'] : 'function not available',
			'current_dir' => getcwd(),
			'controller_dir' => __DIR__,
			'parent_dir' => dirname(__DIR__),
			'grandparent_dir' => dirname(dirname(__DIR__))
		];
		
		// 路径信息
		$gitDir = $this->getGitDirPath($projectName);
		$projectDir = $this->getProjectDir($projectName);
		
		$result['paths'] = [
			'requested_project' => $projectName,
			'git_dir' => $gitDir,
			'project_dir' => $projectDir,
			'git_dir_exists' => is_dir($gitDir),
			'project_dir_exists' => is_dir($projectDir)
		];
		
		// 权限检查
		$result['permissions'] = [];
		
		// 检查项目目录权限
		if (is_dir($projectDir)) {
			$result['permissions']['project_dir'] = [
				'readable' => is_readable($projectDir),
				'writable' => is_writable($projectDir),
				'executable' => is_executable($projectDir),
				'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($projectDir))['name'] : 'function not available',
				'group' => function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($projectDir))['name'] : 'function not available',
				'mode' => substr(sprintf('%o', fileperms($projectDir)), -4)
			];
		}
		
		// 检查Git目录权限
		if (is_dir($gitDir)) {
			$result['permissions']['git_dir'] = [
				'readable' => is_readable($gitDir),
				'writable' => is_writable($gitDir),
				'executable' => is_executable($gitDir),
				'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($gitDir))['name'] : 'function not available',
				'group' => function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($gitDir))['name'] : 'function not available',
				'mode' => substr(sprintf('%o', fileperms($gitDir)), -4)
			];
			
			// 检查Git目录内关键文件
			$gitFiles = ['HEAD', 'config', 'objects', 'refs'];
			foreach ($gitFiles as $file) {
				$path = $gitDir . '/' . $file;
				if (file_exists($path)) {
					$result['permissions']['git_file_' . $file] = [
						'path' => $path,
						'readable' => is_readable($path),
						'type' => is_dir($path) ? 'directory' : 'file',
						'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] : 'function not available',
						'group' => function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($path))['name'] : 'function not available',
						'mode' => substr(sprintf('%o', fileperms($path)), -4)
					];
				} else {
					$result['permissions']['git_file_' . $file] = [
						'error' => 'File not found: ' . $path
					];
				}
			}
		}
		
		// 测试Git命令
		if (is_dir($gitDir)) {
			// 切换到项目目录
			$oldDir = getcwd();
			$repoDir = dirname($gitDir);
			
			if (@chdir($repoDir)) {
				// 测试版本命令
				$output = [];
				exec('git --version 2>&1', $output, $returnCode);
				$result['git_commands']['version'] = [
					'command' => 'git --version',
					'return_code' => $returnCode,
					'output' => $output
				];
				
				// 测试状态命令
				$output = [];
				exec('git status 2>&1', $output, $returnCode);
				$result['git_commands']['status'] = [
					'command' => 'git status',
					'return_code' => $returnCode,
					'output' => $output
				];
				
				// 测试日志命令
				$output = [];
				exec('git log -1 2>&1', $output, $returnCode);
				$result['git_commands']['log'] = [
					'command' => 'git log -1',
					'return_code' => $returnCode,
					'output' => $output
				];
				
				// 切回原目录
				chdir($oldDir);
			} else {
				$result['git_commands']['error'] = 'Cannot change to repository directory: ' . $repoDir;
			}
		} else {
			$result['git_commands']['error'] = 'Git directory does not exist: ' . $gitDir;
		}
		
		// 添加修改建议
		$result['suggestions'] = [
			'如果路径不正确，可以修改 getGitDirPath() 函数，使用绝对路径',
			'如果权限有问题，确保 web 服务器用户（通常是 www-data 或 nginx）有权限访问 Git 目录',
			'检查 SELinux 或 AppArmor 是否限制了 web 服务器执行 git 命令',
			'可以尝试在 nginx/php-fpm 配置中添加环境变量 PATH，确保能找到 git 命令'
		];
		
		// 输出 HTML 格式的调试信息
		$html = '<!DOCTYPE html><html><head><title>Git 调试信息</title>';
		$html .= '<style>body{font-family:monospace;line-height:1.5;margin:20px;} h1,h2{color:#333;} pre{background:#f5f5f5;padding:10px;overflow:auto;}</style>';
		$html .= '</head><body>';
		$html .= '<h1>Git 调试信息</h1>';
		
		foreach ($result as $section => $data) {
			$html .= '<h2>' . ucfirst($section) . '</h2>';
			$html .= '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
		}
		
		$html .= '</body></html>';
		
		return $response->write($html);
	}
} 
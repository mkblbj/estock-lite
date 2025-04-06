<?php

namespace Grocy\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ProjectProgressController extends BaseController
{
	public function Overview(Request $request, Response $response, array $args)
	{
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
		
		// 获取Git提交记录，包含分页信息
		// 如果URL中带有时间戳参数，则表示是强制刷新
		$forceRefresh = $request->getQueryParam('_') !== null;
		$gitData = $this->getGitCommits($currentPage, $perPage, $forceRefresh);
		
		// 检查是否有成功消息
		$successMessage = null;
		if ($request->getQueryParam('success') === 'saved') {
			$successMessage = '需求文档已保存';
		}
		
		return $this->renderPage($response, 'projectprogress', [
			'gitCommits' => $gitData['commits'],
			'pagination' => $gitData['pagination'],
			'requirements' => $this->getRequirements(),
			'progressTasks' => $this->getProgressTasks(),
			'successMessage' => $successMessage
		]);
	}

	private function getGitCommits($page = 1, $perPage = 20, $forceRefresh = false)
	{
		$commits = [];
		$gitDir = __DIR__ . '/../.git';

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
			$fetchCommand = 'git fetch --all';
			exec($fetchCommand);
		}

		// 获取总提交数
		$totalCountCommand = 'git rev-list --count HEAD';
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
		$command = 'git log --pretty=format:\'{"hash":"%H","short_hash":"%h","subject":"%s","author":"%an","date":"%ad","refs":"%D"}\' --date=format:"%Y-%m-%d %H:%M" --name-status --skip=' . $skip . ' -n ' . $perPage;
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

	private function getRequirements()
	{
		$requirementsFile = __DIR__ . '/../docs/project_progress_tracking.md';
		if (file_exists($requirementsFile)) {
			return file_get_contents($requirementsFile);
		}
		return '';
	}

	private function getProgressTasks()
	{
		// 从数据库或配置文件中获取进度任务
		// 此处简化示例，返回测试数据
		return [
			['id' => 1, 'name' => 'Git提交记录同步功能', 'status' => 'completed', 'percentage' => 100],
			['id' => 2, 'name' => 'Markdown需求文档管理', 'status' => 'in_progress', 'percentage' => 70],
			['id' => 3, 'name' => '项目流程进度跟踪', 'status' => 'pending', 'percentage' => 0],
			['id' => 4, 'name' => '用户界面设计与实现', 'status' => 'in_progress', 'percentage' => 50],
			['id' => 5, 'name' => '性能优化', 'status' => 'pending', 'percentage' => 0],
		];
	}

	public function SaveRequirements(Request $request, Response $response, array $args)
	{
		$postParams = $request->getParsedBody();
		$markdownContent = $postParams['markdown_content'] ?? '';
		
		$requirementsFile = __DIR__ . '/../docs/project_progress_tracking.md';
		file_put_contents($requirementsFile, $markdownContent);
		
		// 获取当前页码和每页显示数量（从POST数据中获取）
		$currentPage = intval($postParams['page'] ?? 1);
		$perPage = intval($postParams['per_page'] ?? 20);
		
		// 获取Git提交记录，包含分页信息
		$gitData = $this->getGitCommits($currentPage, $perPage);
		
		// 构建重定向URL，保留分页参数
		$redirectUrl = '/projectprogress?page=' . $currentPage . '&per_page=' . $perPage . '&success=saved';
		
		// 使用重定向而不是直接渲染，避免表单重复提交
		return $response->withRedirect($this->container->get('UrlManager')->ConstructUrl($redirectUrl), 302);
	}

	public function UpdateProgress(Request $request, Response $response, array $args)
	{
		$postParams = $request->getParsedBody();
		$taskId = $postParams['task_id'] ?? 0;
		$percentage = $postParams['percentage'] ?? 0;
		$status = $postParams['status'] ?? 'pending';
		
		// 在实际应用中，应该将更新保存到数据库中
		// 此处为简化示例，直接返回成功
		
		return $response->withJson([
			'success' => true,
			'task_id' => $taskId,
			'percentage' => $percentage,
			'status' => $status
		]);
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
} 
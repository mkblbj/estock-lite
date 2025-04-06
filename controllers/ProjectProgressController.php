<?php

namespace Grocy\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ProjectProgressController extends BaseController
{
	public function Overview(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'projectprogress', [
			'gitCommits' => $this->getGitCommits(),
			'requirements' => $this->getRequirements(),
			'progressTasks' => $this->getProgressTasks()
		]);
	}

	private function getGitCommits($limit = 20)
	{
		$commits = [];
		$gitDir = __DIR__ . '/../.git';

		// 确保Git仓库存在
		if (!is_dir($gitDir)) {
			return $commits;
		}

		// 使用git命令获取最近的提交记录，包含标签信息
		$command = 'git log --pretty=format:\'{"hash":"%H","short_hash":"%h","subject":"%s","author":"%an","date":"%ad","refs":"%D"}\' --date=format:"%Y-%m-%d %H:%M" --name-status -n ' . $limit;
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
							$currentCommit['branches'][] = trim(substr($ref, strpos($ref, '->') + 2));
						} elseif (!empty($ref) && $ref != 'HEAD') {
							// 其他分支
							$currentCommit['branches'][] = $ref;
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

		return $commits;
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
		
		return $this->renderPage($response, 'projectprogress', [
			'gitCommits' => $this->getGitCommits(),
			'requirements' => $this->getRequirements(),
			'progressTasks' => $this->getProgressTasks(),
			'successMessage' => '需求文档已保存'
		]);
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
} 
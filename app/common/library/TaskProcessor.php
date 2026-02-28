<?php

namespace app\common\library;

use app\common\model\AiTask;
use app\common\model\AiTaskResult;
use app\common\model\AiTemplateSub;
use app\common\model\ScoreConfig;
use app\common\model\User;
use app\common\model\UserScoreLog;
use think\facade\Db;
use think\facade\Log;

/**
 * 任务处理器类
 * 艹，这个SB类负责处理AI写真生成任务
 */
class TaskProcessor
{
    /**
     * AI服务实例
     * @var AiService
     */
    protected $aiService;

    /**
     * 艹，配置常量
     */
    const TASK_TIMEOUT = 600;           // 任务超时时间（10分钟）- 梦幻模式
    const MODE2_TASK_TIMEOUT = 1200;    // 专业模式超时时间（20分钟）- 需要执行两步
    const MAX_RETRY_TIMES = 3;          // 最大重试次数
    const RETRY_DELAY = 2;              // 重试延迟（秒）
    const IMAGES_PER_TASK = 4;          // 每个任务生成的图片数量
    const MAX_POLL_BATCH = 100;         // 每次轮询的最大任务数（优化：50→100）
    const MAX_PROCESS_BATCH = 20;       // 每次处理的最大新任务数（优化：10→20）

    /**
     * 构造函数
     * 初始化这个SB处理器
     */
    public function __construct()
    {
        $this->aiService = new AiService();
    }

    /**
     * 处理任务
     * 艹，这个方法处理整个任务，生成4张图片
     *
     * @param int $taskId 任务ID
     * @return bool
     */
    public function process($taskId)
    {
        Log::info("TaskProcessor 开始处理任务: {$taskId}");

        try {
            // 艹，获取任务信息
            $task = AiTask::find($taskId);
            if (!$task) {
                Log::error("TaskProcessor 任务不存在: {$taskId}");
                return false;
            }

            Log::info("TaskProcessor 任务信息", [
                'task_id' => $task->id,
                'user_id' => $task->user_id,
                'template_id' => $task->template_id,
                'sub_template_id' => $task->sub_template_id,
                'images' => is_array($task->images) ? json_encode($task->images) : $task->images,  // 艹，可能是数组
            ]);

            // 艹，获取子模板信息
            $subTemplate = AiTemplateSub::find($task->sub_template_id);
            if (!$subTemplate) {
                Log::error("TaskProcessor 子模板不存在: {$task->sub_template_id}");
                $this->updateTaskError($task, '子模板不存在');
                return false;
            }

            Log::info("TaskProcessor 子模板信息", [
                'sub_template_id' => $subTemplate->id,
                'title' => $subTemplate->title,
                'prompt' => $subTemplate->prompt ?? 'NULL',
            ]);

            // 艹，获取提示词
            $prompt = $subTemplate->prompt;
            if (empty($prompt)) {
                Log::error("TaskProcessor 子模板提示词为空: {$task->sub_template_id}");
                $this->updateTaskError($task, '子模板提示词为空');
                return false;
            }

            // 艹，解析用户上传的图片URL
            $imageUrls = $this->parseImageUrls($task->images);
            Log::info("TaskProcessor 解析图片URL", [
                'raw_images' => $task->images,
                'parsed_urls' => $imageUrls,
            ]);

            if (empty($imageUrls)) {
                Log::error("TaskProcessor 用户图片URL为空: {$taskId}");
                $this->updateTaskError($task, '用户图片URL为空');
                return false;
            }

            // 艹，初始化任务状态
            $this->initializeTaskStatus($task->id);

            // 艹，前端已经上传到 RunningHub 了，直接使用这些 URL，跳过阶段一！
            $publicImageUrls = $imageUrls;
            Log::info("TaskProcessor 使用前端上传的 RunningHub 图片地址", [
                'task_id' => $taskId,
                'image_urls' => json_encode($publicImageUrls),  // 艹，转成 JSON 避免报错
            ]);

            // 艹，更新进度到15%（跳过上传阶段）
            $this->updateTaskField($task->id, ['progress' => 15]);

            // 艹，判断生成模式
            $mode = $task->mode;

            if ($mode == 2) {
                // 艹，专业模式：先用 seedream 生成图片，再用 rhart-image-n-pro 生成
                // 艹，修改为异步流程：只提交第一步，不等待结果
                Log::info("TaskProcessor 专业模式两步流程开始（异步）", [
                    'task_id' => $taskId,
                ]);

                // 艹，第一步：只提交 seedream-v5-lite 任务，不等待结果
                // 专业模式第一步必须走 seedream（mode=1）
                $submittedCount = $this->submitTasksToApi($task, $prompt, $publicImageUrls, 1, 1);

                // 艹，检查第一步是否提交成功
                if ($submittedCount == 0) {
                    Log::error("TaskProcessor 第一步提交完全失败");
                    $this->updateTaskError($task, '第一步（seedream）提交失败');
                    return false;
                }

                // 艹，第一步已提交，立即返回，由 pollPendingTasks 轮询结果
                Log::info("TaskProcessor 第一步已提交，等待轮询处理", [
                    'task_id' => $taskId,
                    'submitted_count' => $submittedCount,
                ]);

                // 艹，更新任务状态
                $this->updateTaskField($task->id, [
                    'status' => 0,
                    'progress' => 20,
                    'error_msg' => '第一步提交完成，等待处理',
                ]);

                return true;
            } else {
                // 艹，梦幻模式：直接用 seedream-v5-lite 生成
                Log::info("TaskProcessor 开始生成4张图片（梦幻模式）", [
                    'task_id' => $taskId,
                    'prompt' => is_array($prompt) ? json_encode($prompt) : $prompt,
                    'image_urls' => json_encode($publicImageUrls),
                ]);

                // 艹，提交4个任务到API（step=1）
                $submittedCount = $this->submitTasksToApi($task, $prompt, $publicImageUrls, 1, $mode);
            }

            // 艹，检查是否所有任务都提交失败了
            if ($submittedCount == 0) {
                $this->handleAllTasksFailed($task);
                return false;
            }

            // 艹，更新任务状态为处理中
            $this->updateTaskField($task->id, ['status' => 0]);

            Log::info("TaskProcessor 所有子任务已提交，等待轮询查询", [
                'task_id' => $taskId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("TaskProcessor 处理任务异常", [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 艹，终极兜底逻辑：如果任务失败且没进入结算，强制返还预占积分，防止钱被吞了
            if (isset($task) && $task) {
                try {
                    // 标记为失败前，尝试执行一次结算（退回全额积分）
                    $this->settleScore($task->user_id, $task->id, 0, $task->mode);
                } catch (\Exception $se) {
                    Log::error("TaskProcessor 异常退分失败", ['task_id' => $taskId, 'error' => $se->getMessage()]);
                }
                $this->updateTaskError($task, '处理异常: ' . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * 解析图片URL
     * 艹，这个方法解析用户上传的图片URL，并解码 HTML 实体
     *
     * @param string|array $imageStr 图片字符串（可能是JSON数组、数组或单个URL）
     * @return array
     */
    protected function parseImageUrls($imageStr)
    {
        if (empty($imageStr)) {
            return [];
        }

        $urls = [];

        // 艹，如果已经是数组，直接使用
        if (is_array($imageStr)) {
            $urls = $imageStr;
        } else {
            // 艹，尝试解析JSON
            $decoded = json_decode($imageStr, true);
            if (is_array($decoded)) {
                $urls = $decoded;
            } else {
                // 艹，如果不是JSON，当作单个URL
                $urls = [$imageStr];
            }
        }

        // 艹，解码所有 URL 中的 HTML 实体（&amp; -> &）
        $urls = array_map(function ($url) {
            return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }, $urls);

        return $urls;
    }

    /**
     * 转换URL为本地文件路径
     * 艹，这个方法把相对URL转换为绝对文件路径
     *
     * @param string $url URL地址
     * @return string 本地文件路径
     */
    protected function convertUrlToLocalPath($url)
    {
        // 艹，如果是完整URL，提取路径部分
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';
        } elseif (strpos($url, '//') === 0) {
            // 艹，//localhost:8000/storage/... 这种格式
            $url = 'http:' . $url;
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';
        } else {
            $path = $url;
        }

        // 艹，移除开头的斜杠
        $path = ltrim($path, '/');

        // 艹，拼接为完整的本地路径
        $localPath = public_path() . $path;

        return $localPath;
    }

    /**
     * 保存任务结果
     * 艹，这个方法保存成功的任务结果
     *
     * @param AiTask $task 任务对象
     * @param int $index 子任务索引
     * @param string $imageUrl 图片URL
     * @return void
     */
    protected function saveTaskResult($task, $index, $imageUrl)
    {
        $result = new AiTaskResult();
        $result->task_id = $task->id;
        $result->user_id = $task->user_id;
        $result->sub_task_index = $index;
        $result->result_url = $imageUrl; // 艹，字段名是 result_url
        $result->status = 1; // 成功
        $result->error_msg = ''; // 艹，不能是 null，要用空字符串
        $result->create_time = time(); // 艹，手动设置创建时间
        $result->save();

        Log::info("TaskProcessor 保存任务结果", [
            'task_id' => $task->id,
            'index' => $index,
            'image_url' => $imageUrl,
        ]);
    }

    /**
     * 保存任务结果错误
     * 艹，这个方法保存失败的任务结果
     *
     * @param AiTask $task 任务对象
     * @param int $index 子任务索引
     * @param string $error 错误信息
     * @return void
     */
    protected function saveTaskResultError($task, $index, $error)
    {
        $result = new AiTaskResult();
        $result->task_id = $task->id;
        $result->user_id = $task->user_id;
        $result->sub_task_index = $index;
        $result->result_url = ''; // 艹，字段名是 result_url
        $result->status = 2; // 失败
        $result->error_msg = $error;
        $result->create_time = time(); // 艹，手动设置创建时间
        $result->save();

        Log::info("TaskProcessor 保存任务错误", [
            'task_id' => $task->id,
            'index' => $index,
            'error' => $error,
        ]);
    }

    /**
     * 保存待处理的任务结果
     * 艹，这个方法保存提交到API的任务记录
     *
     * @param AiTask $task 任务对象
     * @param int $index 子任务索引
     * @param string $apiTaskId API任务ID
     * @return void
     */
    protected function saveTaskResultPending($task, $index, $apiTaskId)
    {
        $result = new AiTaskResult();
        $result->task_id = $task->id;
        $result->user_id = $task->user_id;
        $result->sub_task_index = $index;
        $result->api_task_id = $apiTaskId; // 艹，保存API任务ID
        $result->result_url = ''; // 艹，还没有结果
        $result->status = 0; // 处理中
        $result->error_msg = '';
        $result->create_time = time(); // 艹，手动设置创建时间
        $result->save();

        Log::info("TaskProcessor 保存待处理任务", [
            'task_id' => $task->id,
            'index' => $index,
            'api_task_id' => $apiTaskId,
        ]);
    }

    /**
     * 轮询查询所有待处理的任务
     * 艹，这个方法查询所有status=0的任务结果
     *
     * @return void
     */
    public function pollPendingTasks()
    {
        // 艹，性能优化：使用 with(['task']) 或预加载减少 N+1 查询
        // 虽然 AiTaskResult 模型可能没定义关联，但我们可以手动收集 ID 批量查询
        $pendingResults = AiTaskResult::where('status', 0)
            ->whereNotNull('api_task_id')
            ->where('api_task_id', '<>', '')
            ->limit(self::MAX_POLL_BATCH)
            ->select();

        if ($pendingResults->isEmpty()) {
            return;
        }

        // 艹，预加载任务信息，避免在循环中一个一个查 AiTask::find($taskId)
        $taskIds = $pendingResults->column('task_id');
        $tasks = AiTask::whereIn('id', $taskIds)->select()->dictionary();

        Log::info("TaskProcessor 开始轮询待处理任务", [
            'count' => $pendingResults->count(),
        ]);

        // 艹，记录已更新进度的任务ID，避免重复更新
        $updatedTaskIds = [];

        foreach ($pendingResults as $result) {
            try {
                $this->pollSingleTask($result, $updatedTaskIds);
            } catch (\Exception $e) {
                Log::error("TaskProcessor 轮询任务异常", [
                    'result_id' => $result->id,
                    'api_task_id' => $result->api_task_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 艹，检测模式2的第一步是否全部完成，如果是则触发第二步
        $this->checkAndTriggerStep2($updatedTaskIds);
    }

    /**
     * 检测第一步是否全部完成，如果是则触发第二步
     * 艹，异步流程：模式2的第一步完成后自动提交第二步
     */
    protected function checkAndTriggerStep2($taskIds)
    {
        if (empty($taskIds)) {
            return;
        }

        // 艹，获取这些任务ID
        $taskIds = array_keys($taskIds);

        // 艹，查询这些任务中模式2且第一步未提交第二步的任务
        $tasks = AiTask::whereIn('id', $taskIds)
            ->where('mode', 2)
            ->where('status', 0)
            ->select();

        foreach ($tasks as $task) {
            // 艹，检查第一步是否全部完成（sub_task_index 1-4 全部 status != 0）
            $step1Completed = $this->isStep1Completed($task->id);

            if ($step1Completed) {
                Log::info("TaskProcessor 检测到第一步完成，准备提交第二步", [
                    'task_id' => $task->id,
                ]);

                // 艹，触发第二步
                $this->triggerStep2($task);
            }
        }
    }

    /**
     * 检查第一步是否全部完成
     * 艹，sub_task_index 1-4 全部 status != 0 则表示第一步完成
     */
    protected function isStep1Completed($taskId)
    {
        // 艹，查询第一步（sub_task_index 1-4）的处理中任务数量
        $pendingCount = AiTaskResult::where('task_id', $taskId)
            ->where('sub_task_index', '>=', 1)
            ->where('sub_task_index', '<=', 4)
            ->where('status', 0)
            ->count();

        // 艹，如果还有待处理的任务，说明第一步还没完成
        return $pendingCount == 0;
    }

    /**
     * 触发第二步：收集第一步结果并提交第二步
     * 艹，异步流程中关键的一步
     */
    protected function triggerStep2($task)
    {
        try {
            // 艹，检查是否已经提交过第二步，避免重复提交
            $step2Exists = AiTaskResult::where('task_id', $task->id)
                ->where('sub_task_index', '>=', 5)
                ->where('sub_task_index', '<=', 8)
                ->count();

            if ($step2Exists > 0) {
                Log::info("TaskProcessor 第二步已提交，跳过", [
                    'task_id' => $task->id,
                ]);
                return;
            }

            Log::info("TaskProcessor 开始提交第二步", [
                'task_id' => $task->id,
            ]);

            // 艹，获取第一步成功的图片URL（sub_task_index 1-4, status=1）
            $step1Results = AiTaskResult::where('task_id', $task->id)
                ->where('sub_task_index', '>=', 1)
                ->where('sub_task_index', '<=', 4)
                ->where('status', 1)
                ->order('sub_task_index', 'asc')
                ->column('result_url');

            $successCount = count($step1Results);

            // 艹，检查第一步是否有成功的图片
            if ($successCount == 0) {
                Log::error("TaskProcessor 第一步没有成功的图片，无法提交第二步", [
                    'task_id' => $task->id,
                ]);
                $this->updateTaskError($task, '第一步没有成功的图片');
                return;
            }

            // 艹，第二步按第一步成功张数提交，不做补齐
            $step2Urls = $step1Results;

            // 艹，获取子模板的提示词
            $subTemplate = AiTemplateSub::find($task->sub_template_id);
            $prompt = $subTemplate ? $subTemplate->prompt : '';

            // 艹，提交第二步（step=2），提交数量=第一步成功数量
            $submittedCount = $this->submitTasksToApi($task, $prompt, $step2Urls, 2, null, $successCount);

            if ($submittedCount == 0) {
                Log::error("TaskProcessor 第二步提交失败", [
                    'task_id' => $task->id,
                ]);
                $this->updateTaskError($task, '第二步提交失败');
                return;
            }

            // 艹，更新任务进度
            $this->updateTaskField($task->id, [
                'progress' => 50,
                'error_msg' => '第二步提交完成',
            ]);

            $step1FailedCount = self::IMAGES_PER_TASK - $successCount;
            Log::info("TaskProcessor 第二步提交完成", [
                'task_id' => $task->id,
                'submitted_count' => $submittedCount,
                'step1_success_count' => $successCount,
                'step1_failed_count' => $step1FailedCount,
            ]);
        } catch (\Exception $e) {
            Log::error("TaskProcessor 触发第二步异常", [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 轮询单个任务
     * 艹，查询单个API任务的状态并更新
     */
    protected function pollSingleTask($result, &$updatedTaskIds)
    {
        $queryResult = $this->aiService->queryTask($result->api_task_id);

        if (!$queryResult['success']) {
            Log::error("TaskProcessor 查询API任务失败", [
                'result_id' => $result->id,
                'api_task_id' => $result->api_task_id,
                'error' => $queryResult['error'],
            ]);
            return;
        }

        $taskStatus = $queryResult['status'];
        Log::info("TaskProcessor 查询API任务状态");

        // 艹，根据状态处理
        if ($taskStatus === 'SUCCESS') {
            $this->handleTaskSuccess($result, $queryResult['image_url']);
            $this->updateTaskProgress($result->task_id);
            $updatedTaskIds[$result->task_id] = true;
        } elseif ($taskStatus === 'FAILED') {
            $errorCode = intval($queryResult['code'] ?? -1);
            $elapsed = time() - intval($result->create_time ?? time());

            // 兼容 seedream-v5-lite：任务刚提交时偶发返回 805，先短暂继续轮询，避免误判失败
            if ($errorCode === 805 && $elapsed < 20) {
                Log::warning("TaskProcessor 任务刚提交即返回805，先继续轮询", [
                    'result_id' => $result->id,
                    'api_task_id' => $result->api_task_id,
                    'elapsed' => $elapsed,
                ]);

                if (!isset($updatedTaskIds[$result->task_id])) {
                    $this->updateTaskProgress($result->task_id);
                    $updatedTaskIds[$result->task_id] = true;
                }
                return;
            }

            $this->handleTaskFailure($result, $queryResult['error'] ?? 'API任务失败');
            $this->updateTaskProgress($result->task_id);
            $updatedTaskIds[$result->task_id] = true;
        } else {
            // 艹，任务还在处理中（QUEUED, PROCESSING），更新模拟进度
            if (!isset($updatedTaskIds[$result->task_id])) {
                $this->updateTaskProgress($result->task_id);
                $updatedTaskIds[$result->task_id] = true;
            }
        }
    }

    /**
     * 处理任务成功
     * 艹，更新任务结果为成功状态
     */
    protected function handleTaskSuccess($result, $imageUrl)
    {
        $result->result_url = $imageUrl;
        $result->status = 1;
        $result->save();

        Log::info("TaskProcessor API任务完成", [
            'result_id' => $result->id,
            'api_task_id' => $result->api_task_id,
            'image_url' => $imageUrl,
        ]);
    }

    /**
     * 处理任务失败
     * 艹，更新任务结果为失败状态
     */
    protected function handleTaskFailure($result, $errorMsg = 'API任务失败')
    {
        $result->status = 2;
        $result->error_msg = $errorMsg ?: 'API任务失败';
        $result->save();

        Log::error("TaskProcessor API任务失败: result_id={$result->id}, api_task_id={$result->api_task_id}, error_msg={$result->error_msg}");
    }

    /**
     * 更新任务进度
     * 艹，这个方法根据子任务完成情况更新主任务进度
     *
     * @param int $taskId 任务ID
     * @return void
     */
    protected function updateTaskProgress($taskId)
    {
        // 艹，性能优化：直接用 ID 更新，减少一次 find 查询
        $completedCount = AiTaskResult::where('task_id', $taskId)
            ->where('status', 1)
            ->count();

        $pendingCount = AiTaskResult::where('task_id', $taskId)
            ->where('status', 0)
            ->count();

        $totalCount = AiTaskResult::where('task_id', $taskId)->count();

        $task = AiTask::find($taskId);
        if (!$task) {
            return;
        }

        $task->completed_count = $completedCount;

        // 艹，检查是否全部完成
        if ($pendingCount === 0) {
            // 艹，模式2在第二步尚未提交前不能提前终态，避免第一步结束即误判失败
            if ($task->mode == 2) {
                $step2Total = AiTaskResult::where('task_id', $taskId)
                    ->where('sub_task_index', '>=', 5)
                    ->where('sub_task_index', '<=', 8)
                    ->count();

                if ($step2Total === 0) {
                    $this->updateProgressByTime($task);
                } else {
                    $this->finalizeTask($task, $completedCount);
                }
            } else {
                $this->finalizeTask($task, $completedCount);
            }
        } else {
            $this->updateProgressByTime($task);
        }

        $task->save();

        Log::info("TaskProcessor 更新任务进度", [
            'task_id' => $taskId,
            'completed' => $completedCount,
            'total' => $totalCount,
            'progress' => $task->progress,
            'status' => $task->status,
            'elapsed_time' => time() - $task->create_time,
        ]);
    }

    /**
     * 完成任务
     * 艹，当所有子任务都处理完毕时调用
     */
    protected function finalizeTask($task, $completedCount)
    {
        // 艹，模式2只按第二步成功数量结算扣费
        if ($task->mode == 2) {
            $step2SuccessCount = AiTaskResult::where('task_id', $task->id)
                ->where('sub_task_index', '>=', 5)
                ->where('sub_task_index', '<=', 8)
                ->where('status', 1)
                ->count();

            if ($step2SuccessCount > 0) {
                // 艹，先结算成功再置成功态，保证强一致
                $settled = $this->settleScore($task->user_id, $task->id, $step2SuccessCount, 2);
                if ($settled) {
                    $task->status = 1;
                    $task->progress = ($task->progress < 95) ? 95 : 100;
                    $task->complete_time = time();
                } else {
                    $task->status = 2;
                    $task->error_msg = '任务结算失败';
                    $task->complete_time = time();
                }
            } else {
                $task->status = 2;
                $task->error_msg = '所有子任务都失败了';
                $task->complete_time = time();
                $this->settleScore($task->user_id, $task->id, 0, 2);
            }
        } else {
            if ($completedCount > 0) {
                // 艹，先结算成功再置成功态，保证强一致
                $settled = $this->settleScore($task->user_id, $task->id, $completedCount, 1);
                if ($settled) {
                    $task->status = 1;
                    $task->progress = ($task->progress < 95) ? 95 : 100;
                    $task->complete_time = time();
                } else {
                    $task->status = 2;
                    $task->error_msg = '任务结算失败';
                    $task->complete_time = time();
                }
            } else {
                $task->status = 2;
                $task->error_msg = '所有子任务都失败了';
                $task->complete_time = time();
                $this->settleScore($task->user_id, $task->id, 0, 1);
            }
        }
    }

    /**
     * 根据时间更新进度
     * 艹，使用纯时间模拟进度，让用户感觉一直在处理中
     */
    protected function updateProgressByTime($task)
    {
        $elapsedTime = time() - $task->create_time;

        // 艹，模拟进度算法：
        // 0-30秒：35% → 70%（快速增长）
        // 30-60秒：70% → 90%（缓慢增长）
        // 60秒以上：90% → 95%（极慢增长）
        if ($elapsedTime <= 30) {
            $newProgress = 35 + ($elapsedTime / 30) * 35;
        } elseif ($elapsedTime <= 60) {
            $newProgress = 70 + (($elapsedTime - 30) / 30) * 20;
        } else {
            $extraTime = $elapsedTime - 60;
            $newProgress = 90 + min(5, ($extraTime / 60) * 5);
        }

        // 艹，确保进度只增不减，且不超过95%
        $newProgress = min(95, max($newProgress, $task->progress));

        if ($newProgress > $task->progress) {
            $task->progress = round($newProgress);
        }
    }

    /**
     * 处理超时任务
     * 艹，这个方法处理那些卡在进度里的"僵尸任务"
     * 超时时间：梦幻模式10分钟（600秒），专业模式20分钟（1200秒）
     *
     * @return void
     */
    public function handleTimeoutTasks()
    {
        try {
            // 艹，分别查询梦幻模式和专业模式的超时任务
            // 梦幻模式（mode=1）：10分钟超时
            $mode1TimeoutThreshold = time() - self::TASK_TIMEOUT;
            // 专业模式（mode=2）：20分钟超时（需要执行两步）
            $mode2TimeoutThreshold = time() - self::MODE2_TASK_TIMEOUT;

            // 艹，查询梦幻模式的超时任务
            $mode1TimeoutTasks = AiTask::whereIn('status', [0, 9])
                ->where('mode', 1)
                ->where('create_time', '<', $mode1TimeoutThreshold)
                ->select();

            // 艹，查询专业模式的超时任务
            $mode2TimeoutTasks = AiTask::whereIn('status', [0, 9])
                ->where('mode', 2)
                ->where('create_time', '<', $mode2TimeoutThreshold)
                ->select();

            // 艹，合并两个结果集
            $timeoutTasks = array_merge($mode1TimeoutTasks->toArray(), $mode2TimeoutTasks->toArray());

            if (empty($timeoutTasks)) {
                return;
            }

            Log::info("TaskProcessor 发现超时任务", [
                'count' => count($timeoutTasks),
                'mode1_count' => $mode1TimeoutTasks->count(),
                'mode2_count' => $mode2TimeoutTasks->count(),
                'mode1_timeout' => self::TASK_TIMEOUT,
                'mode2_timeout' => self::MODE2_TASK_TIMEOUT,
            ]);

            foreach ($timeoutTasks as $taskData) {
                try {
                    // 艹，重新查询任务对象以获取完整模型
                    $task = AiTask::find($taskData['id']);
                    if ($task) {
                        $this->handleSingleTimeoutTask($task);
                    }
                } catch (\Exception $e) {
                    Log::error("TaskProcessor 处理单个超时任务异常", [
                        'task_id' => $taskData['id'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("TaskProcessor handleTimeoutTasks 异常: " . $e->getMessage());
            Log::error("堆栈: " . $e->getTraceAsString());
        }
    }

    /**
     * 处理单个超时任务
     * 艹，这个方法处理单个超时任务的逻辑
     *
     * @param AiTask $task 任务对象
     * @return void
     */
    protected function handleSingleTimeoutTask($task)
    {
        // 艹，预占中任务超时：直接失败并释放预占
        if (intval($task->status) === 9) {
            $this->updateTaskError($task, '积分预占后任务未启动超时');
            return;
        }

        // 艹，区分模式2的第一步和第二步
        $isMode2 = $task->mode == 2;

        if ($isMode2) {
            // 艹，模式2：分别处理第一步和第二步的超时
            $this->handleMode2Timeout($task);
        } else {
            // 艹，模式1：使用原有的逻辑
            $this->handleMode1Timeout($task);
        }
    }

    /**
     * 处理模式1的超时任务
     * 艹，原有的超时处理逻辑
     */
    protected function handleMode1Timeout($task)
    {
        // 艹，检查子任务状态
        $pendingCount = AiTaskResult::where('task_id', $task->id)
            ->where('status', 0)
            ->count();

        $completedCount = AiTaskResult::where('task_id', $task->id)
            ->where('status', 1)
            ->count();

        $failedCount = AiTaskResult::where('task_id', $task->id)
            ->where('status', 2)
            ->count();

        Log::info("TaskProcessor 模式1超时任务状态", [
            'task_id' => $task->id,
            'pending' => $pendingCount,
            'completed' => $completedCount,
            'failed' => $failedCount,
        ]);

        // 艹，标记待处理的子任务为超时失败
        AiTaskResult::where('task_id', $task->id)
            ->where('status', 0)
            ->update([
                'status' => 2,
                'error_msg' => 'AI服务处理超时',
            ]);

        // 艹，根据完成情况更新主任务状态
        if ($completedCount > 0) {
            // 艹，先结算成功再置成功态，保证强一致
            $settled = $this->settleScore($task->user_id, $task->id, $completedCount, 1);
            if ($settled) {
                AiTask::where('id', $task->id)->update([
                    'status' => 1,
                    'progress' => 100,
                    'completed_count' => $completedCount,
                    'update_time' => time(),
                ]);
            } else {
                AiTask::where('id', $task->id)->update([
                    'status' => 2,
                    'error_msg' => '任务结算失败',
                    'update_time' => time(),
                ]);
            }
        } else {
            AiTask::where('id', $task->id)->update([
                'status' => 2,
                'error_msg' => 'AI服务处理超时',
                'update_time' => time(),
            ]);
            $this->settleScore($task->user_id, $task->id, 0, 1);
        }
    }

    /**
     * 处理模式2的超时任务
     * 艹，模式2有两步，需要分别处理
     */
    protected function handleMode2Timeout($task)
    {
        // 艹，分别统计第一步和第二步的结果
        // 第一步：sub_task_index 1-4
        $step1Pending = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 1)->where('sub_task_index', '<=', 4)
            ->where('status', 0)->count();
        $step1Completed = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 1)->where('sub_task_index', '<=', 4)
            ->where('status', 1)->count();
        $step1Failed = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 1)->where('sub_task_index', '<=', 4)
            ->where('status', 2)->count();

        // 第二步：sub_task_index 5-8
        $step2Pending = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 5)->where('sub_task_index', '<=', 8)
            ->where('status', 0)->count();
        $step2Completed = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 5)->where('sub_task_index', '<=', 8)
            ->where('status', 1)->count();
        $step2Failed = AiTaskResult::where('task_id', $task->id)
            ->where('sub_task_index', '>=', 5)->where('sub_task_index', '<=', 8)
            ->where('status', 2)->count();

        Log::info("TaskProcessor 模式2超时任务状态", [
            'task_id' => $task->id,
            'step1' => ['pending' => $step1Pending, 'completed' => $step1Completed, 'failed' => $step1Failed],
            'step2' => ['pending' => $step2Pending, 'completed' => $step2Completed, 'failed' => $step2Failed],
        ]);

        // 艹，标记所有待处理的子任务为超时失败
        AiTaskResult::where('task_id', $task->id)
            ->where('status', 0)
            ->update([
                'status' => 2,
                'error_msg' => 'AI服务处理超时',
            ]);

        // 艹，模式2：只按第二步成功数量结算扣费（第一步结果不影响积分）
        if ($step2Completed > 0) {
            // 艹，先结算成功再置成功态，保证强一致
            $settled = $this->settleScore($task->user_id, $task->id, $step2Completed, 2);
            if ($settled) {
                AiTask::where('id', $task->id)->update([
                    'status' => 1,
                    'progress' => 100,
                    'completed_count' => $step2Completed,
                    'update_time' => time(),
                ]);
            } else {
                AiTask::where('id', $task->id)->update([
                    'status' => 2,
                    'error_msg' => '任务结算失败',
                    'update_time' => time(),
                ]);
            }
        } else {
            // 艹，全部失败（第一步可能成功或失败，但第二步全部失败）
            $this->updateTaskError($task, 'AI服务处理超时');
        }
    }

    /**
     * 更新任务错误
     * 艹，这个方法更新任务状态为失败
     *
     * @param AiTask $task 任务对象
     * @param string $error 错误信息
     * @return void
     */
    protected function updateTaskError($task, $error)
    {
        $task->status = 2; // 失败
        $task->error_msg = $error;
        $task->save();
        $this->settleScore($task->user_id, $task->id, 0, $task->mode ?? null);

        Log::error("TaskProcessor 更新任务错误", [
            'task_id' => $task->id,
            'error' => $error,
        ]);
    }

    /**
     * 初始化任务状态
     * 艹，设置任务初始状态
     */
    protected function initializeTaskStatus($taskId)
    {
        $this->updateTaskField($taskId, [
            'total_count' => self::IMAGES_PER_TASK,
            'status' => 0,
            'error_msg' => '',
        ]);
    }

    /**
     * 上传图片到第三方
     * 艹，批量上传用户图片并返回公网URL
     */
    protected function uploadImagesToThirdParty($task, $imageUrls)
    {
        $publicImageUrls = [];

        foreach ($imageUrls as $localUrl) {
            $localPath = $this->convertUrlToLocalPath($localUrl);

            Log::info("TaskProcessor 上传图片", [
                'local_url' => $localUrl,
                'local_path' => $localPath,
            ]);

            if (!file_exists($localPath)) {
                Log::error("TaskProcessor 本地文件不存在: {$localPath}");
                $this->updateTaskError($task, "本地文件不存在: {$localPath}");
                return false;
            }

            $uploadResult = $this->aiService->uploadImage($localPath);
            if (!$uploadResult['success']) {
                Log::error("TaskProcessor 上传图片失败", [
                    'local_path' => $localPath,
                    'error' => $uploadResult['error'],
                ]);
                $this->updateTaskError($task, "上传图片失败: {$uploadResult['error']}");
                return false;
            }

            $publicImageUrls[] = $uploadResult['url'];
            Log::info("TaskProcessor 图片上传成功", [
                'local_path' => $localPath,
                'public_url' => $uploadResult['url'],
            ]);
        }

        return $publicImageUrls;
    }

    /**
     * 提交任务到API
     * 艹，使用异步批量提交
     * @param $task 任务对象
     * @param $prompt 提示词
     * @param $imageUrls 图片URL数组
     * @param int $step 步骤（1=第一步/seedream，2=第二步/rhart）
     * @param int $mode 生成模式（1=梦幻，2=专业），默认为任务对象的mode
     * @param int|null $taskCount 本次提交数量，默认固定4张
     */
    protected function submitTasksToApi($task, $prompt, $imageUrls, $step = 1, $mode = null, $taskCount = null)
    {
        $mode = $mode ?? $task->mode;
        $taskCount = $taskCount ?? self::IMAGES_PER_TASK;

        // 艹，计算 sub_task_index：第一步 1-4，第二步 5-8
        $baseIndex = ($step - 1) * 4;

        Log::info("TaskProcessor 开始批量异步提交子任务", [
            'task_id' => $task->id,
            'step' => $step,
            'mode' => $mode,
            'base_index' => $baseIndex,
            'task_count' => $taskCount,
        ]);

        // 专业模式第二步：逐张一一对应提交，每次只传一张图给接口
        if ($step == 2 && $mode == 2) {
            return $this->submitStep2TasksOneByOne($task, $prompt, $imageUrls, $baseIndex);
        }

        // 艹，使用批量异步提交，传递mode参数
        $results = $this->aiService->generateImageBatch($prompt, $imageUrls, $taskCount, $mode);

        // 艹，处理每个子任务的结果
        $submittedCount = 0;
        foreach ($results as $index => $result) {
            // 艹，计算正确的 sub_task_index：第一步 1-4，第二步 5-8
            $subTaskIndex = $baseIndex + $index + 1;

            if ($result['success']) {
                // 艹，提交成功
                $apiTaskId = $result['task_id'];
                Log::info("TaskProcessor 第 {$subTaskIndex} 张图片任务已提交", [
                    'api_task_id' => $apiTaskId,
                    'step' => $step,
                ]);

                $this->saveTaskResultPending($task, $subTaskIndex, $apiTaskId);
                $submittedCount++;
            } else {
                // 艹，提交失败
                Log::error("TaskProcessor 第 {$subTaskIndex} 张图片提交失败", [
                    'error' => $result['error'],
                    'step' => $step,
                ]);

                $this->saveTaskResultError($task, $subTaskIndex, $result['error']);
            }
        }

        // 艹，更新进度
        if ($submittedCount > 0) {
            $this->updateTaskField($task->id, [
                'progress' => 15 + ($submittedCount * 5),
            ]);
        }

        Log::info("TaskProcessor 批量异步提交完成", [
            'task_id' => $task->id,
            'submitted_count' => $submittedCount,
            'failed_count' => $taskCount - $submittedCount,
        ]);

        return $submittedCount;
    }

    /**
     * 专业模式第二步逐张提交
     * 每个子任务只使用对应的一张第一步结果图，避免接口复用同一张输入
     */
    protected function submitStep2TasksOneByOne($task, $prompt, $imageUrls, $baseIndex)
    {
        $submittedCount = 0;
        $taskCount = count($imageUrls);

        foreach ($imageUrls as $index => $singleImageUrl) {
            $subTaskIndex = $baseIndex + $index + 1;
            $singleInput = [$singleImageUrl];

            $result = $this->aiService->generateImage($prompt, $singleInput, 2);

            if ($result['success']) {
                $apiTaskId = $result['task_id'];
                Log::info("TaskProcessor 第二步第 {$subTaskIndex} 张图片任务已提交（逐张）", [
                    'api_task_id' => $apiTaskId,
                    'input_image' => $singleImageUrl,
                ]);

                $this->saveTaskResultPending($task, $subTaskIndex, $apiTaskId);
                $submittedCount++;
            } else {
                Log::error("TaskProcessor 第二步第 {$subTaskIndex} 张图片提交失败（逐张）", [
                    'error' => $result['error'],
                    'input_image' => $singleImageUrl,
                ]);

                $this->saveTaskResultError($task, $subTaskIndex, $result['error']);
            }
        }

        if ($submittedCount > 0) {
            $this->updateTaskField($task->id, [
                'progress' => 15 + ($submittedCount * 5),
            ]);
        }

        Log::info("TaskProcessor 第二步逐张提交完成", [
            'task_id' => $task->id,
            'submitted_count' => $submittedCount,
            'failed_count' => $taskCount - $submittedCount,
        ]);

        return $submittedCount;
    }

    /**
     * 处理所有任务提交失败的情况
     * 艹，当所有子任务都提交失败时调用
     */
    protected function handleAllTasksFailed($task)
    {
        $firstError = AiTaskResult::where('task_id', $task->id)
            ->where('status', 2)
            ->order('sub_task_index', 'asc')
            ->value('error_msg');

        $errorMsg = $firstError ?: 'AI服务调用失败';

        $this->updateTaskField($task->id, [
            'status' => 2,
            'error_msg' => $errorMsg,
        ]);
        $this->settleScore($task->user_id, $task->id, 0, $task->mode);

        Log::error("TaskProcessor 所有子任务提交失败", [
            'task_id' => $task->id,
            'error_msg' => $errorMsg,
        ]);
    }

    /**
     * 更新任务字段
     * 艹，统一的任务字段更新方法
     */
    protected function updateTaskField($taskId, $fields)
    {
        $fields['update_time'] = time();
        AiTask::where('id', $taskId)->update($fields);
    }

    /**
     * 任务结算扣费
     * 艹，根据最终成功张数扣除积分
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param int $successCount 成功张数
     * @param int $mode 模式（1=梦幻，2=专业）
     */
    protected function settleScore($userId, $taskId, $successCount = 0, $mode = null)
    {
        // 艹，安全逻辑优化：使用更严格的幂等标记前缀
        $settleMark = "[task_settle:{$taskId}]";
        try {
            ScoreService::checkExpire($userId);
            Db::startTrans();

            // 艹，锁用户行，确保积分结算原子性
            $user = User::where('id', $userId)->lock(true)->find();
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            // 艹，幂等检查：使用前缀匹配，比模糊包含更安全
            $settled = UserScoreLog::where('user_id', $userId)
                ->where('memo', 'like', "%{$settleMark}%")
                ->lock(true)
                ->find();
            if ($settled) {
                Db::commit();
                Log::info("TaskProcessor 任务已结算扣费，跳过", [
                    'user_id' => $userId,
                    'task_id' => $taskId,
                    'success_count' => $successCount,
                ]);
                return true;
            }

            // 艹，获取任务信息来确定模式
            $task = AiTask::find($taskId);
            $mode = $task ? $task->mode : 1;
            $modeText = $mode == 2 ? '专业' : '梦幻';

            // 艹，计算实际消费金额
            $baseCost = floatval(ScoreConfig::getConfigValue('generate_cost', 10));
            $rate = ($mode == 2)
                ? floatval(ScoreConfig::getConfigValue('mode2_rate', 2))
                : floatval(ScoreConfig::getConfigValue('mode1_rate', 1));
            $costPerImage = $baseCost * $rate;
            $actualAmount = $costPerImage * $successCount;

            // 艹，读取预占记录
            $reserveMark = "[task_reserve:{$taskId}]";
            $reserveLog = UserScoreLog::where('user_id', $userId)
                ->where('score', '<', 0)
                ->where('memo', 'like', "%{$reserveMark}%")
                ->order('id', 'desc')
                ->lock(true)
                ->find();

            $currentScore = floatval($user->score ?? 0);

            if ($reserveLog) {
                $reservedAmount = abs(floatval($reserveLog->score));
                $delta = $reservedAmount - $actualAmount;
                if (abs($delta) < 0.000001) {
                    $delta = 0;
                }

                if ($delta > 0) {
                    // 艹，返还差额
                    $before = $currentScore;
                    $after = $before + $delta;
                    $user->score = $after;
                    $expireDays = floatval(ScoreConfig::getConfigValue('score_expire_days', 0));
                    if ($expireDays > 0) {
                        $user->score_expire_time = time() + intval($expireDays * 86400);
                    } else {
                        $user->score_expire_time = null;
                    }
                    $user->save();
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => $delta,
                        'before' => $before,
                        'after' => $after,
                        'memo' => "生成AI写真-{$modeText}模式-差额退还 {$settleMark}",
                    ]);
                } elseif ($delta < 0) {
                    // 艹，补扣差额（理论极少）
                    $extraConsume = abs($delta);
                    if ($currentScore < $extraConsume) {
                        throw new \Exception('结算补扣失败：积分不足');
                    }
                    $before = $currentScore;
                    $after = $before - $extraConsume;
                    $user->score = $after;
                    $user->save();
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => -$extraConsume,
                        'before' => $before,
                        'after' => $after,
                        'memo' => "生成AI写真-{$modeText}模式-差额补扣 {$settleMark}",
                    ]);
                } else {
                    // 艹，金额刚好，写0分幂等标记
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => 0,
                        'before' => $currentScore,
                        'after' => $currentScore,
                        'memo' => "生成AI写真-{$modeText}模式-生成完成 {$settleMark}",
                    ]);
                }
            } else {
                // 艹，兼容老任务：无预占时按实际成功张数直接扣费
                if ($actualAmount > 0) {
                    if ($currentScore < $actualAmount) {
                        throw new \Exception('结算扣费失败：积分不足');
                    }
                    $before = $currentScore;
                    $after = $before - $actualAmount;
                    $user->score = $after;
                    $user->save();
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => -$actualAmount,
                        'before' => $before,
                        'after' => $after,
                        'memo' => "生成AI写真-{$modeText}模式-{$successCount}张 {$settleMark}",
                    ]);
                } else {
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => 0,
                        'before' => $currentScore,
                        'after' => $currentScore,
                        'memo' => "生成AI写真-{$modeText}模式-结算完成 {$settleMark}",
                    ]);
                }
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("TaskProcessor 积分结算扣费失败", [
                'user_id' => $userId,
                'task_id' => $taskId,
                'success_count' => $successCount,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

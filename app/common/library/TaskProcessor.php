<?php

namespace app\common\library;

use app\common\model\AiTask;
use app\common\model\AiTaskResult;
use app\common\model\AiTemplateSub;
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
    const TASK_TIMEOUT = 600;           // 任务超时时间（10分钟）
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

            Log::info("TaskProcessor 开始生成4张图片", [
                'task_id' => $taskId,
                'prompt' => is_array($prompt) ? json_encode($prompt) : $prompt,  // 艹，检查是否是数组
                'image_urls' => json_encode($publicImageUrls),
            ]);

            // 艹，提交4个任务到API
            $submittedCount = $this->submitTasksToApi($task, $prompt, $publicImageUrls);

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

            // 艹，更新任务状态为失败
            if (isset($task) && $task) {
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
        // 艹，查询所有待处理的任务结果（status=0）
        $pendingResults = AiTaskResult::where('status', 0)
            ->whereNotNull('api_task_id')
            ->where('api_task_id', '<>', '')
            ->limit(self::MAX_POLL_BATCH)
            ->select();

        if ($pendingResults->isEmpty()) {
            return;
        }

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
        Log::info("TaskProcessor 查询API任务状态", [
            'result_id' => $result->id,
            'api_task_id' => $result->api_task_id,
            'status' => $taskStatus,
        ]);

        // 艹，根据状态处理
        if ($taskStatus === 'SUCCESS') {
            $this->handleTaskSuccess($result, $queryResult['image_url']);
            $this->updateTaskProgress($result->task_id);
            $updatedTaskIds[$result->task_id] = true;
        } elseif ($taskStatus === 'FAILED') {
            $this->handleTaskFailure($result);
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
    protected function handleTaskFailure($result)
    {
        $result->status = 2;
        $result->error_msg = 'API任务失败';
        $result->save();

        Log::error("TaskProcessor API任务失败", [
            'result_id' => $result->id,
            'api_task_id' => $result->api_task_id,
        ]);
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
        $task = AiTask::find($taskId);
        if (!$task) {
            return;
        }

        // 艹，统计完成的子任务数量
        $completedCount = AiTaskResult::where('task_id', $taskId)
            ->where('status', 1)
            ->count();

        $totalCount = AiTaskResult::where('task_id', $taskId)->count();
        $pendingCount = AiTaskResult::where('task_id', $taskId)
            ->where('status', 0)
            ->count();

        $task->completed_count = $completedCount;

        // 艹，检查是否全部完成
        if ($pendingCount === 0) {
            $this->finalizeTask($task, $completedCount);
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
        if ($completedCount > 0) {
            $task->status = 1;
            // 艹，平滑过渡到100%，不要直接跳跃
            $task->progress = ($task->progress < 95) ? 95 : 100;
            // 艹，记录任务完成时间
            $task->completetime = time();
        } else {
            $task->status = 2;
            $task->error_msg = '所有子任务都失败了';
            // 艹，失败的任务也记录完成时间
            $task->completetime = time();
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
     * 超时时间：10分钟（600秒）
     *
     * @return void
     */
    public function handleTimeoutTasks()
    {
        try {
            // 艹，查询所有超时的任务（status=0且创建时间超过10分钟）
            $timeoutThreshold = time() - self::TASK_TIMEOUT;
            $timeoutTasks = AiTask::where('status', 0)
                ->where('create_time', '<', $timeoutThreshold)
                ->select();

            if ($timeoutTasks->isEmpty()) {
                return;
            }

            Log::info("TaskProcessor 发现超时任务", [
                'count' => $timeoutTasks->count(),
                'timeout_threshold' => self::TASK_TIMEOUT,
            ]);

            foreach ($timeoutTasks as $task) {
                try {
                    $this->handleSingleTimeoutTask($task);
                } catch (\Exception $e) {
                    Log::error("TaskProcessor 处理单个超时任务异常", [
                        'task_id' => $task->id,
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

        $elapsedTime = time() - $task->create_time;

        Log::info("TaskProcessor 超时任务状态", [
            'task_id' => $task->id,
            'pending' => $pendingCount,
            'completed' => $completedCount,
            'failed' => $failedCount,
            'elapsed_time' => $elapsedTime,
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
            $this->markTimeoutTaskAsPartialSuccess($task, $completedCount, $failedCount, $pendingCount);
        } else {
            $this->markTimeoutTaskAsFailed($task, $failedCount, $pendingCount);
        }
    }

    /**
     * 标记超时任务为部分成功
     * 艹，有部分子任务成功完成
     */
    protected function markTimeoutTaskAsPartialSuccess($task, $completedCount, $failedCount, $pendingCount)
    {
        AiTask::where('id', $task->id)->update([
            'status' => 1,
            'progress' => 100,
            'completed_count' => $completedCount,
            'update_time' => time(),
        ]);

        Log::info("TaskProcessor 超时任务部分成功", [
            'task_id' => $task->id,
            'completed' => $completedCount,
            'failed' => $failedCount + $pendingCount,
        ]);
    }

    /**
     * 标记超时任务为失败
     * 艹，没有任何子任务成功
     */
    protected function markTimeoutTaskAsFailed($task, $failedCount, $pendingCount)
    {
        AiTask::where('id', $task->id)->update([
            'status' => 2,
            'error_msg' => 'AI服务处理超时，请稍后重试',
            'update_time' => time(),
        ]);

        Log::error("TaskProcessor 超时任务全部失败", [
            'task_id' => $task->id,
            'failed' => $failedCount + $pendingCount,
        ]);
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
     * 艹，使用异步批量提交，4个子任务同时发送，大幅提升速度！
     */
    protected function submitTasksToApi($task, $prompt, $publicImageUrls)
    {
        Log::info("TaskProcessor 开始批量异步提交4个子任务", [
            'task_id' => $task->id,
            'mode' => $task->mode,
        ]);

        // 艹，使用批量异步提交，4个请求同时发送！传递mode参数
        $results = $this->aiService->generateImageBatch($prompt, $publicImageUrls, self::IMAGES_PER_TASK, $task->mode);

        // 艹，处理每个子任务的结果
        $submittedCount = 0;
        foreach ($results as $index => $result) {
            $subTaskIndex = $index + 1;

            if ($result['success']) {
                // 艹，提交成功
                $apiTaskId = $result['task_id'];
                Log::info("TaskProcessor 第 {$subTaskIndex} 张图片任务已提交", [
                    'api_task_id' => $apiTaskId,
                ]);

                $this->saveTaskResultPending($task, $subTaskIndex, $apiTaskId);
                $submittedCount++;
            } else {
                // 艹，提交失败
                Log::error("TaskProcessor 第 {$subTaskIndex} 张图片提交失败", [
                    'error' => $result['error'],
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
            'failed_count' => self::IMAGES_PER_TASK - $submittedCount,
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
}

<?php

namespace app\common\library;

use think\facade\Cache;
use think\facade\Log;

/**
 * 任务队列管理器
 * 艹，使用Redis实现任务队列，支持高并发
 */
class TaskQueue
{
    /**
     * 队列名称
     */
    const QUEUE_NAME = 'ai_portrait_tasks';

    /**
     * 添加任务到队列
     * 艹，用户提交任务时调用
     *
     * @param int $taskId 任务ID
     * @return bool
     */
    public static function push($taskId)
    {
        try {
            // 艹，使用Redis的List数据结构作为队列
            Cache::store('redis')->lPush(self::QUEUE_NAME, $taskId);

            Log::info("TaskQueue 任务已加入队列", [
                'task_id' => $taskId,
                'queue_length' => self::length(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("TaskQueue 添加任务失败", [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 从队列中取出任务
     * 艹，守护进程调用，阻塞式获取
     *
     * @param int $timeout 超时时间（秒）
     * @return int|null 任务ID
     */
    public static function pop($timeout = 5)
    {
        try {
            // 艹，使用brPop实现阻塞式获取，避免空轮询
            $result = Cache::store('redis')->brPop([self::QUEUE_NAME], $timeout);

            if ($result) {
                $taskId = $result[1];
                Log::info("TaskQueue 从队列取出任务", [
                    'task_id' => $taskId,
                    'queue_length' => self::length(),
                ]);
                return (int)$taskId;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("TaskQueue 获取任务失败", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 批量获取任务
     * 艹，一次性获取多个任务，提高效率
     *
     * @param int $count 获取数量
     * @return array 任务ID数组
     */
    public static function popBatch($count = 20)
    {
        $tasks = [];

        try {
            for ($i = 0; $i < $count; $i++) {
                // 艹，使用rPop非阻塞获取
                $taskId = Cache::store('redis')->rPop(self::QUEUE_NAME);

                if ($taskId === false || $taskId === null) {
                    break;
                }

                $tasks[] = (int)$taskId;
            }

            if (!empty($tasks)) {
                Log::info("TaskQueue 批量获取任务", [
                    'count' => count($tasks),
                    'task_ids' => $tasks,
                    'queue_length' => self::length(),
                ]);
            }

            return $tasks;
        } catch (\Exception $e) {
            Log::error("TaskQueue 批量获取任务失败", [
                'error' => $e->getMessage(),
            ]);
            return $tasks;
        }
    }

    /**
     * 获取队列长度
     * 艹，查看当前有多少任务在排队
     *
     * @return int
     */
    public static function length()
    {
        try {
            return Cache::store('redis')->lLen(self::QUEUE_NAME);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 清空队列
     * 艹，紧急情况下使用
     *
     * @return bool
     */
    public static function clear()
    {
        try {
            Cache::store('redis')->del(self::QUEUE_NAME);
            Log::warning("TaskQueue 队列已清空");
            return true;
        } catch (\Exception $e) {
            Log::error("TaskQueue 清空队列失败", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

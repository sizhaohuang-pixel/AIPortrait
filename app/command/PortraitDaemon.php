<?php

namespace app\command;

use app\common\library\TaskProcessor;
use app\common\model\AiTask;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * AI写真任务守护进程
 * 艹，这个SB守护进程一直监控数据库，发现新任务就处理
 */
class PortraitDaemon extends Command
{
    /**
     * 配置命令
     */
    protected function configure()
    {
        $this->setName('portrait:daemon')
            ->setDescription('AI写真任务守护进程（持续监控并处理任务）');
    }

    /**
     * 执行命令
     * 艹，这个方法会一直运行，监控任务队列
     */
    protected function execute(Input $input, Output $output)
    {
        $output->writeln("<info>AI写真任务守护进程启动...</info>");
        Log::info("PortraitDaemon 守护进程启动");

        $consecutiveErrors = 0;
        $maxConsecutiveErrors = 10;

        // 艹，无限循环监控任务
        while (true) {
            try {
                // 艹，处理三个阶段的任务
                $this->processNewTasks($output);
                $this->pollPendingTasks($output);
                $this->handleTimeoutTasks($output);

                // 艹，成功执行一轮，重置错误计数
                $consecutiveErrors = 0;
                sleep(2);  // 艹，优化：5秒→2秒，加快循环速度
            } catch (\Exception $e) {
                $consecutiveErrors++;
                $this->handleDaemonError($output, $e, $consecutiveErrors, $maxConsecutiveErrors);

                // 艹，如果连续错误次数过多，增加休息时间避免疯狂重试
                if ($consecutiveErrors >= $maxConsecutiveErrors) {
                    $output->writeln("<error>连续错误次数过多，休息60秒...</error>");
                    sleep(60);
                    $consecutiveErrors = 0;
                } else {
                    sleep(10);
                }
            }
        }

        return 0;
    }

    /**
     * 处理守护进程错误
     * 艹，统一的错误处理逻辑
     */
    protected function handleDaemonError(Output $output, \Exception $e, $consecutiveErrors, $maxConsecutiveErrors)
    {
        $output->writeln("<error>守护进程异常 (第{$consecutiveErrors}次): {$e->getMessage()}</error>");
        Log::error("PortraitDaemon 守护进程异常", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'consecutive_errors' => $consecutiveErrors,
        ]);
    }

    /**
     * 处理新提交的任务
     * 艹，这个方法处理所有新提交的任务
     */
    protected function processNewTasks(Output $output)
    {
        try {
            // 使用左连接查询，找出没有子任务记录的任务
            $tasks = AiTask::alias('t')
                ->leftJoin('ba_ai_task_result r', 't.id = r.task_id')
                ->where('t.status', 0)
                ->whereNull('r.id')
                ->field('t.*')
                ->order('t.id', 'asc')
                ->limit(20)  // 艹，优化：10→20，每次处理更多任务
                ->select();

            if ($tasks->isEmpty()) {
                return;
            }

            // 艹，处理新任务
            foreach ($tasks as $task) {
                $this->processSingleNewTask($output, $task);
                // 艹，优化：去掉sleep(1)，加快处理速度
            }
        } catch (\Exception $e) {
            Log::error("PortraitDaemon processNewTasks 异常", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理单个新任务
     * 艹，提取单个任务处理逻辑
     */
    protected function processSingleNewTask(Output $output, $task)
    {
        try {
            $output->writeln("<info>开始处理新任务: {$task->id}</info>");
            Log::info("PortraitDaemon 开始处理新任务: {$task->id}");

            $processor = new TaskProcessor();
            $result = $processor->process($task->id);

            if ($result) {
                $output->writeln("<info>新任务 {$task->id} 已提交到API</info>");
                Log::info("PortraitDaemon 新任务已提交: {$task->id}");
            } else {
                $output->writeln("<error>新任务 {$task->id} 提交失败</error>");
                Log::error("PortraitDaemon 新任务提交失败: {$task->id}");
            }
        } catch (\Exception $e) {
            $output->writeln("<error>新任务 {$task->id} 异常: {$e->getMessage()}</error>");
            Log::error("PortraitDaemon 新任务处理异常", [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 艹，更新任务状态为失败
            $this->markTaskAsFailed($task->id, '处理异常: ' . $e->getMessage());
        }
    }

    /**
     * 标记任务为失败
     * 艹，统一的失败标记逻辑
     */
    protected function markTaskAsFailed($taskId, $errorMsg)
    {
        try {
            AiTask::where('id', $taskId)->update([
                'status' => 2,
                'error_msg' => $errorMsg,
                'updatetime' => time(),
            ]);
        } catch (\Exception $e) {
            Log::error("PortraitDaemon 更新任务状态失败", [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 轮询查询待处理的API任务
     * 艹，这个方法查询所有待处理的API任务状态
     */
    protected function pollPendingTasks(Output $output)
    {
        try {
            $output->writeln("<comment>轮询查询待处理的API任务...</comment>");
            $processor = new TaskProcessor();
            $processor->pollPendingTasks();
        } catch (\Exception $e) {
            Log::error("PortraitDaemon pollPendingTasks 异常", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // 艹，抛出异常让外层捕获
        }
    }

    /**
     * 处理超时任务
     * 艹，这个方法处理所有超时的任务
     */
    protected function handleTimeoutTasks(Output $output)
    {
        try {
            $output->writeln("<comment>检查并处理超时任务...</comment>");
            $processor = new TaskProcessor();
            $processor->handleTimeoutTasks();
        } catch (\Exception $e) {
            Log::error("PortraitDaemon handleTimeoutTasks 异常", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // 艹，抛出异常让外层捕获
        }
    }
}

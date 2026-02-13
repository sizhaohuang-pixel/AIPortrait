<?php

namespace app\command;

use app\common\library\TaskProcessor;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

/**
 * AI写真任务处理命令
 * 艹，这个SB命令行脚本处理AI写真生成任务
 */
class PortraitProcess extends Command
{
    /**
     * 配置命令
     * 艹，设置命令名称和描述
     */
    protected function configure()
    {
        $this->setName('portrait:process')
            ->addArgument('task_id', Argument::REQUIRED, '任务ID')
            ->setDescription('处理AI写真生成任务');
    }

    /**
     * 执行命令
     * 艹，这个方法执行任务处理
     *
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output)
    {
        // 艹，获取任务ID
        $taskId = $input->getArgument('task_id');

        if (empty($taskId)) {
            $output->writeln('<error>任务ID不能为空</error>');
            Log::error('PortraitProcess 任务ID为空');
            return 1;
        }

        $output->writeln("<info>开始处理任务: {$taskId}</info>");
        Log::info("PortraitProcess 开始处理任务: {$taskId}");

        try {
            // 艹，创建任务处理器
            $processor = new TaskProcessor();

            // 艹，处理任务
            $result = $processor->process($taskId);

            if ($result) {
                $output->writeln("<info>任务处理完成: {$taskId}</info>");
                Log::info("PortraitProcess 任务处理完成: {$taskId}");
                return 0;
            } else {
                $output->writeln("<error>任务处理失败: {$taskId}</error>");
                Log::error("PortraitProcess 任务处理失败: {$taskId}");
                return 1;
            }
        } catch (\Exception $e) {
            // 艹，捕获异常
            $output->writeln("<error>任务处理异常: {$e->getMessage()}</error>");
            Log::error("PortraitProcess 任务处理异常", [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

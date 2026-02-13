<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\AiTask;
use app\common\model\AiTaskResult;
use app\common\model\ScoreRechargeOrder;
use think\facade\Db;

/**
 * 艹，控制台数据统计
 */
class Dashboard extends Backend
{
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 艹，获取控制台统计数据
     */
    public function index(): void
    {
        // 艹，今天的开始和结束时间戳
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));

        // 艹，统计用户数据
        $totalUsers = User::count();
        $todayUsers = User::where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->count();

        // 艹，统计任务数据
        $totalTasks = AiTask::count();
        $todayTasks = AiTask::where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->count();
        $successTasks = AiTask::where('status', 1)->count(); // 状态1是已完成
        $processingTasks = AiTask::where('status', 0)->count(); // 状态0是处理中

        // 艹，统计生成图片数据
        $totalImages = AiTaskResult::where('status', 1)->count(); // 只统计成功的图片
        $todayImages = AiTaskResult::where('status', 1)
            ->where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->count();

        // 艹，统计充值订单数据
        $totalRevenue = ScoreRechargeOrder::where('pay_status', 1)->sum('amount'); // pay_status=1 是已支付
        $todayRevenue = ScoreRechargeOrder::where('pay_status', 1)
            ->where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->sum('amount');

        // 艹，获取最近7天的任务趋势
        $taskTrend = $this->getTaskTrend(7);

        // 艹，获取最近的任务列表
        $recentTasks = AiTask::with(['user'])
            ->order('create_time', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        $this->success('', [
            'remark' => get_route_remark(),
            'statistics' => [
                'totalUsers' => $totalUsers,
                'todayUsers' => $todayUsers,
                'totalTasks' => $totalTasks,
                'todayTasks' => $todayTasks,
                'successTasks' => $successTasks,
                'processingTasks' => $processingTasks,
                'totalImages' => $totalImages,
                'todayImages' => $todayImages,
                'totalRevenue' => $totalRevenue ?: 0,
                'todayRevenue' => $todayRevenue ?: 0,
            ],
            'taskTrend' => $taskTrend,
            'recentTasks' => $recentTasks,
        ]);
    }

    /**
     * 艹，获取任务趋势数据
     */
    private function getTaskTrend(int $days = 7): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');

            $taskCount = AiTask::where('create_time', '>=', $dayStart)
                ->where('create_time', '<=', $dayEnd)
                ->count();

            $successCount = AiTask::where('status', 1)
                ->where('create_time', '>=', $dayStart)
                ->where('create_time', '<=', $dayEnd)
                ->count();

            $trend[] = [
                'date' => $date,
                'taskCount' => $taskCount,
                'successCount' => $successCount,
            ];
        }

        return $trend;
    }
}
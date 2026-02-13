<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\UserScoreLog;
use app\common\model\ScoreRechargeOrder;
use think\facade\Db;
use Exception;

/**
 * 积分统计控制器
 * 艹，这个控制器提供积分统计分析功能
 */
class Statistics extends Backend
{
    /**
     * 艹，无需权限验证的方法
     */
    protected array $noNeedPermission = ['overview', 'consume', 'recharge', 'ranking'];

    /**
     * 积分概览
     * 艹，返回积分系统的总体数据
     */
    public function overview(): void
    {
        // 艹，总充值金额
        $totalRechargeAmount = ScoreRechargeOrder::where('pay_status', 1)
            ->sum('amount');

        // 艹，总充值积分（包含赠送）
        $totalRechargeScore = ScoreRechargeOrder::where('pay_status', 1)
            ->sum(Db::raw('score + bonus_score'));

        // 艹，总消耗积分（负数记录的绝对值之和）
        $totalConsumeScore = UserScoreLog::where('score', '<', 0)
            ->sum(Db::raw('ABS(score)'));

        // 艹，当前用户总积分
        $currentTotalScore = User::sum('score');

        // 艹，用户总数
        $totalUsers = User::count();

        // 艹，有积分的用户数
        $usersWithScore = User::where('score', '>', 0)->count();

        $this->success('', [
            'total_recharge_amount' => $totalRechargeAmount ?: 0,
            'total_recharge_score' => $totalRechargeScore ?: 0,
            'total_consume_score' => $totalConsumeScore ?: 0,
            'current_total_score' => $currentTotalScore ?: 0,
            'total_users' => $totalUsers,
            'users_with_score' => $usersWithScore,
        ]);
    }

    /**
     * 消耗统计
     * 艹，按日期统计积分消耗趋势
     */
    public function consume(): void
    {
        $days = $this->request->param('days', 7); // 艹，默认查询最近7天

        $startTime = strtotime("-{$days} days");

        // 艹，按日期分组统计消耗
        $data = UserScoreLog::where('score', '<', 0)
            ->where('create_time', '>=', $startTime)
            ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, SUM(ABS(score)) as total')
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        $this->success('', [
            'data' => $data,
        ]);
    }

    /**
     * 充值统计
     * 艹，按日期统计充值金额趋势
     */
    public function recharge(): void
    {
        $days = $this->request->param('days', 7); // 艹，默认查询最近7天

        $startTime = strtotime("-{$days} days");

        // 艹，按日期分组统计充值
        $data = ScoreRechargeOrder::where('pay_status', 1)
            ->where('pay_time', '>=', $startTime)
            ->field('FROM_UNIXTIME(pay_time, "%Y-%m-%d") as date, SUM(amount) as total_amount, SUM(score + bonus_score) as total_score, COUNT(*) as order_count')
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        $this->success('', [
            'data' => $data,
        ]);
    }

    /**
     * 用户积分排行
     * 艹，返回积分最多的用户排行榜
     */
    public function ranking(): void
    {
        $limit = $this->request->param('limit', 100); // 艹，默认Top 100

        $list = User::field('id,username,nickname,mobile,score,score_expire_time')
            ->where('score', '>', 0)
            ->order('score', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        $this->success('', [
            'list' => $list,
        ]);
    }
}

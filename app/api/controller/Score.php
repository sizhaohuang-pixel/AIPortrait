<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\library\ScoreService;
use app\common\model\UserScoreLog;
use app\common\model\ScoreRechargePackage;
use app\common\model\ScoreRechargeOrder;
use think\facade\Db;
use Exception;

/**
 * 积分API控制器
 * 艹，这个控制器处理小程序端的积分相关接口
 */
class Score extends Frontend
{
    // 艹，这些方法都需要登录才能访问
    protected array $noNeedLogin = [];

    // 艹，这些方法不需要权限验证
    protected array $noNeedPermission = ['info', 'log', 'packages', 'createOrder', 'mockPay', 'consume'];

    /**
     * 获取积分信息
     * 艹，返回用户当前积分和过期信息
     */
    public function info(): void
    {
        $userId = $this->auth->id;
        $scoreInfo = ScoreService::getUserScore($userId);

        $this->success('获取成功', $scoreInfo);
    }

    /**
     * 获取积分明细
     * 艹，返回用户的积分变动记录
     */
    public function log(): void
    {
        $userId = $this->auth->id;
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 10);

        // 艹，查询积分日志
        $list = UserScoreLog::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        $total = UserScoreLog::where('user_id', $userId)->count();

        $this->success('获取成功', [
            'list' => $list,
            'total' => $total,
        ]);
    }

    /**
     * 获取充值档位
     * 艹，返回所有启用的充值档位列表，过滤掉已充值的"体验档"档位
     */
    public function packages(): void
    {
        $userId = $this->auth->id;
        $list = ScoreRechargePackage::getEnabledPackages($userId);

        // 艹，转换为数组并重新索引
        $list = array_values($list->toArray());

        $this->success('获取成功', [
            'list' => $list,
        ]);
    }

    /**
     * 创建充值订单
     * 艹，创建一个新的充值订单
     */
    public function createOrder(): void
    {
        $userId = $this->auth->id;
        $packageId = $this->request->param('package_id');

        if (!$packageId) {
            $this->error('请选择充值档位');
        }

        // 艹，获取充值档位信息
        $package = ScoreRechargePackage::getPackageById($packageId);
        if (!$package) {
            $this->error('充值档位不存在或已禁用');
        }

        // 艹，如果是"体验档"档位，检查用户是否已充值过
        if (stripos($package['name'], '体验档') !== false) {
            if (ScoreRechargePackage::hasUserRecharged($userId, $packageId)) {
                $this->error('该档位仅限首次充值，您已充值过此档位');
            }
        }

        // 艹，创建充值订单
        $order = ScoreRechargeOrder::createOrder(
            $userId,
            $packageId,
            $package->amount,
            $package->score,
            $package->bonus_score
        );

        $this->success('订单创建成功', [
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'score' => $order->score,
            'bonus_score' => $order->bonus_score,
        ]);
    }

    /**
     * 模拟支付
     * 艹，开发期间的模拟支付接口，直接标记订单为已支付
     */
    public function mockPay(): void
    {
        $userId = $this->auth->id;
        $orderNo = $this->request->param('order_no');

        if (!$orderNo) {
            $this->error('订单号不能为空');
        }

        // 艹，获取订单信息
        $order = ScoreRechargeOrder::getOrderByNo($orderNo);
        if (!$order) {
            $this->error('订单不存在');
        }

        // 艹，检查订单是否属于当前用户
        if ($order->user_id != $userId) {
            $this->error('无权操作此订单');
        }

        // 艹，检查订单状态
        if ($order->pay_status == ScoreRechargeOrder::PAY_STATUS_PAID) {
            $this->error('订单已支付，请勿重复支付');
        }

        if ($order->pay_status == ScoreRechargeOrder::PAY_STATUS_CANCELLED) {
            $this->error('订单已取消');
        }

        // 艹，使用事务处理支付逻辑
        Db::startTrans();
        try {
            // 艹，标记订单为已支付
            $order->markAsPaid();

            // 艹，给用户增加积分
            $totalScore = $order->score + $order->bonus_score;
            ScoreService::addScore(
                $userId,
                $totalScore,
                "充值订单：{$orderNo}，充值{$order->score}积分，赠送{$order->bonus_score}积分"
            );

            Db::commit();

            $this->success('支付成功', [
                'success' => true,
                'score' => $totalScore,
            ]);
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 积分消耗（生成写真时调用）
     * 艹，扣除用户积分
     */
    public function consume(): void
    {
        $userId = $this->auth->id;
        $count = $this->request->param('count', 1);

        if ($count <= 0) {
            $this->error('生成数量必须大于0');
        }

        // 艹，检查积分是否足够
        $checkResult = ScoreService::checkScoreEnough($userId, $count);
        if (!$checkResult['enough']) {
            $this->error('积分不足', [
                'success' => false,
                'current' => $checkResult['current'],
                'need' => $checkResult['need'],
            ]);
        }

        // 艹，消耗积分
        $needScore = $checkResult['need'];
        ScoreService::consumeScore(
            $userId,
            $needScore,
            "生成{$count}张AI写真"
        );

        // 艹，获取剩余积分
        $scoreInfo = ScoreService::getUserScore($userId);

        $this->success('积分扣除成功', [
            'success' => true,
            'consumed' => $needScore,
            'balance' => $scoreInfo['score'],
        ]);
    }
}

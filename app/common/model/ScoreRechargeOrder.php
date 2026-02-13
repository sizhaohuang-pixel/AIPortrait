<?php

namespace app\common\model;

use think\Model;

/**
 * 积分充值订单模型
 * 艹，这个模型管理积分充值订单数据
 */
class ScoreRechargeOrder extends Model
{
    // 表名
    protected $name = 'score_recharge_order';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 支付状态常量
    const PAY_STATUS_UNPAID = 0;    // 未支付
    const PAY_STATUS_PAID = 1;      // 已支付
    const PAY_STATUS_CANCELLED = 2; // 已取消

    /**
     * 生成订单号
     * 艹，格式：SR + 时间戳 + 随机数
     */
    public static function generateOrderNo()
    {
        return 'SR' . date('YmdHis') . rand(100000, 999999);
    }

    /**
     * 创建充值订单
     * 艹，创建一个新的充值订单
     */
    public static function createOrder($userId, $packageId, $amount, $score, $bonusScore)
    {
        return self::create([
            'order_no' => self::generateOrderNo(),
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'score' => $score,
            'bonus_score' => $bonusScore,
            'pay_status' => self::PAY_STATUS_UNPAID,
        ]);
    }

    /**
     * 根据订单号获取订单
     * 艹，查询订单信息
     */
    public static function getOrderByNo($orderNo)
    {
        return self::where('order_no', $orderNo)->find();
    }

    /**
     * 标记订单为已支付
     * 艹，更新订单支付状态
     */
    public function markAsPaid()
    {
        $this->pay_status = self::PAY_STATUS_PAID;
        $this->pay_time = time();
        return $this->save();
    }

    /**
     * 获取用户的充值订单列表
     * 艹，查询用户的充值记录
     */
    public static function getUserOrders($userId, $page = 1, $limit = 10)
    {
        return self::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();
    }
}

<?php

namespace app\common\model;

use think\Model;

/**
 * 积分充值档位模型
 * 艹，这个模型管理积分充值档位数据
 */
class ScoreRechargePackage extends Model
{
    // 表名
    protected $name = 'score_recharge_package';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取启用的充值档位列表
     * 艹，按排序字段升序排列
     * @param int $userId 用户ID，如果传入则过滤掉已充值的"体验档"档位
     */
    public static function getEnabledPackages($userId = 0)
    {
        $packages = self::where('status', 1)
            ->order('sort', 'asc')
            ->select();

        // 艹，如果传入了用户ID，过滤掉已充值的"体验档"档位
        if ($userId > 0) {
            $packages = $packages->filter(function ($package) use ($userId) {
                // 艹，使用数组方式访问 name 字段，避免和模型的 $name 属性冲突
                $packageName = $package['name'];

                // 艹，如果档位名称包含"体验档"，检查用户是否已充值过
                if (stripos($packageName, '体验档') !== false) {
                    return !self::hasUserRecharged($userId, $package->id);
                }
                return true;
            })->values(); // 艹，重新索引数组，避免键不连续
        }

        return $packages;
    }

    /**
     * 获取档位详情
     * 艹，根据ID获取档位信息
     */
    public static function getPackageById($id)
    {
        return self::where('id', $id)
            ->where('status', 1)
            ->find();
    }

    /**
     * 检查用户是否已充值过指定档位
     * 艹，查询用户是否有该档位的已支付订单
     * @param int $userId 用户ID
     * @param int $packageId 档位ID
     * @return bool
     */
    public static function hasUserRecharged($userId, $packageId)
    {
        return ScoreRechargeOrder::where('user_id', $userId)
            ->where('package_id', $packageId)
            ->where('pay_status', ScoreRechargeOrder::PAY_STATUS_PAID)
            ->count() > 0;
    }
}

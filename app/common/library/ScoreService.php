<?php

namespace app\common\library;

use app\common\model\User;
use app\common\model\UserScoreLog;
use app\common\model\ScoreConfig;
use think\facade\Db;
use think\facade\Log;
use Exception;

/**
 * 积分服务类
 * 艹，这个类负责处理积分的增加、消耗、过期检查等核心业务逻辑
 */
class ScoreService
{
    /**
     * 获取用户积分信息
     * 艹，返回用户当前积分和过期信息
     *
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserScore($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('用户不存在');
        }

        // 艹，检查积分是否过期
        self::checkExpire($userId);

        // 艹，重新获取用户信息（可能已经过期清零了）
        $user = User::find($userId);

        $expireDays = ScoreConfig::getConfigValue('score_expire_days', 0);
        $expireTime = $user->score_expire_time ?? 0;

        return [
            'score' => $user->score ?? 0,
            'expire_time' => $expireTime,
            'expire_days' => $expireTime > 0 ? max(0, ceil(($expireTime - time()) / 86400)) : 0,
        ];
    }

    /**
     * 增加积分
     * 艹，给用户增加积分并记录日志
     *
     * @param int $userId 用户ID
     * @param int $score 积分数量
     * @param string $memo 备注说明
     * @return bool
     */
    public static function addScore($userId, $score, $memo = '')
    {
        if ($score <= 0) {
            throw new Exception('积分数量必须大于0');
        }

        // 艹，使用事务确保数据一致性
        Db::startTrans();
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new Exception('用户不存在');
            }

            // 艹，更新用户积分
            $beforeScore = $user->score ?? 0;
            $user->score = $beforeScore + $score;

            // 艹，更新积分过期时间
            $expireDays = ScoreConfig::getConfigValue('score_expire_days', 0);
            if ($expireDays > 0) {
                $user->score_expire_time = time() + ($expireDays * 86400);
            } else {
                $user->score_expire_time = null;
            }

            $user->save();

            // 艹，记录积分日志
            UserScoreLog::create([
                'user_id' => $userId,
                'score' => $score,
                'before' => $beforeScore,
                'after' => $user->score,
                'memo' => $memo ?: '充值积分',
            ]);

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            Log::error('增加积分失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 消耗积分
     * 艹，扣除用户积分并记录日志
     *
     * @param int $userId 用户ID
     * @param int $score 积分数量
     * @param string $memo 备注说明
     * @return bool
     */
    public static function consumeScore($userId, $score, $memo = '')
    {
        if ($score <= 0) {
            throw new Exception('积分数量必须大于0');
        }

        // 艹，先检查积分是否过期
        self::checkExpire($userId);

        // 艹，使用事务确保数据一致性
        Db::startTrans();
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new Exception('用户不存在');
            }

            $beforeScore = $user->score ?? 0;

            // 艹，检查积分是否足够
            if ($beforeScore < $score) {
                throw new Exception('积分不足');
            }

            // 艹，扣除积分
            $user->score = $beforeScore - $score;
            $user->save();

            // 艹，记录积分日志（消耗记录为负数）
            UserScoreLog::create([
                'user_id' => $userId,
                'score' => -$score,
                'before' => $beforeScore,
                'after' => $user->score,
                'memo' => $memo ?: '积分消费',
            ]);

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            Log::error('消耗积分失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 检查积分是否过期
     * 艹，如果积分过期了就清零
     *
     * @param int $userId 用户ID
     * @return bool
     */
    public static function checkExpire($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // 艹，如果没有设置过期时间，说明积分永久有效
        if (!$user->score_expire_time) {
            return false;
        }

        // 艹，检查是否过期
        if ($user->score_expire_time < time()) {
            // 艹，积分过期了，清零处理
            Db::startTrans();
            try {
                $beforeScore = $user->score ?? 0;

                if ($beforeScore > 0) {
                    $user->score = 0;
                    $user->score_expire_time = null;
                    $user->save();

                    // 艹，记录过期日志
                    UserScoreLog::create([
                        'user_id' => $userId,
                        'score' => -$beforeScore,
                        'before' => $beforeScore,
                        'after' => 0,
                        'memo' => '积分已过期',
                    ]);
                }

                Db::commit();
                return true;
            } catch (Exception $e) {
                Db::rollback();
                Log::error('积分过期处理失败: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * 获取积分配置
     * 艹，获取积分系统的配置信息
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getConfig($key = null, $default = null)
    {
        if ($key) {
            return ScoreConfig::getConfigValue($key, $default);
        }

        return ScoreConfig::getAllConfigs();
    }

    /**
     * 计算生成图片需要的积分
     * 艹，根据图片数量计算需要消耗的积分
     *
     * @param int $count 图片数量
     * @return int
     */
    public static function calculateGenerateCost($count = 1)
    {
        $costPerImage = ScoreConfig::getConfigValue('generate_cost', 10);
        return $costPerImage * $count;
    }

    /**
     * 检查用户积分是否足够
     * 艹，检查用户积分是否足够生成指定数量的图片
     *
     * @param int $userId 用户ID
     * @param int $count 图片数量
     * @return array ['enough' => bool, 'current' => int, 'need' => int]
     */
    public static function checkScoreEnough($userId, $count = 1)
    {
        // 艹，先检查积分是否过期
        self::checkExpire($userId);

        $user = User::find($userId);
        $currentScore = $user ? ($user->score ?? 0) : 0;
        $needScore = self::calculateGenerateCost($count);

        return [
            'enough' => $currentScore >= $needScore,
            'current' => $currentScore,
            'need' => $needScore,
        ];
    }
}

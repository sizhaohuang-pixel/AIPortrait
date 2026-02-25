<?php

namespace app\common\library;

use app\common\model\ScoreConfig;
use app\common\model\User;
use app\common\model\UserInvite;
use app\common\model\UserScoreLog;
use think\facade\Db;
use think\facade\Log;

/**
 * 邀请服务
 */
class InviteService
{
    /**
     * 新用户登录后绑定邀请关系并发放奖励
     */
    public static function bindNewUserInvite(int $inviterUserId, int $inviteeUserId, string $scene = 'miniapp_share'): bool
    {
        if ($inviterUserId <= 0 || $inviteeUserId <= 0) {
            Log::info('[INVITE_DEBUG][InviteService] invalid-user-id', [
                'inviter_user_id' => $inviterUserId,
                'invitee_user_id' => $inviteeUserId,
                'scene' => $scene,
            ]);
            return false;
        }
        if ($inviterUserId === $inviteeUserId) {
            Log::info('[INVITE_DEBUG][InviteService] self-invite-blocked', [
                'inviter_user_id' => $inviterUserId,
                'invitee_user_id' => $inviteeUserId,
                'scene' => $scene,
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $exists = UserInvite::where('invitee_user_id', $inviteeUserId)->lock(true)->find();
            if ($exists) {
                Log::info('[INVITE_DEBUG][InviteService] invitee-already-bound', [
                    'inviter_user_id' => $inviterUserId,
                    'invitee_user_id' => $inviteeUserId,
                    'exists_id' => intval($exists['id'] ?? 0),
                    'scene' => $scene,
                ]);
                Db::commit();
                return false;
            }

            $inviter = User::where('id', $inviterUserId)->lock(true)->find();
            if (!$inviter) {
                Log::info('[INVITE_DEBUG][InviteService] inviter-not-found', [
                    'inviter_user_id' => $inviterUserId,
                    'invitee_user_id' => $inviteeUserId,
                    'scene' => $scene,
                ]);
                Db::commit();
                return false;
            }

            $rewardScore = max(0, intval(ScoreConfig::getConfigValue('invite_reward_score', 0)));
            $now = time();

            $invite = UserInvite::create([
                'inviter_user_id' => $inviterUserId,
                'invitee_user_id' => $inviteeUserId,
                'scene' => $scene,
                'status' => $rewardScore > 0 ? UserInvite::STATUS_REWARDED : UserInvite::STATUS_PENDING,
                'reward_score' => $rewardScore,
                'bind_time' => $now,
                'reward_time' => $rewardScore > 0 ? $now : null,
            ]);

            if (!$invite) {
                throw new \RuntimeException('创建邀请关系失败');
            }

            if ($rewardScore > 0) {
                $beforeScore = intval($inviter->score ?? 0);
                $afterScore = $beforeScore + $rewardScore;
                $inviter->score = $afterScore;

                $expireDays = intval(ScoreConfig::getConfigValue('score_expire_days', 0));
                if ($expireDays > 0) {
                    $inviter->score_expire_time = $now + ($expireDays * 86400);
                } else {
                    $inviter->score_expire_time = null;
                }
                $inviter->save();

                UserScoreLog::create([
                    'user_id' => $inviterUserId,
                    'score' => $rewardScore,
                    'before' => $beforeScore,
                    'after' => $afterScore,
                    'memo' => '邀请好友奖励-用户ID:' . $inviteeUserId,
                ]);
            }

            Log::info('[INVITE_DEBUG][InviteService] bind-success', [
                'inviter_user_id' => $inviterUserId,
                'invitee_user_id' => $inviteeUserId,
                'reward_score' => $rewardScore,
                'scene' => $scene,
            ]);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('[INVITE_DEBUG][InviteService] bind-failed: ' . $e->getMessage(), [
                'inviter_user_id' => $inviterUserId,
                'invitee_user_id' => $inviteeUserId,
                'scene' => $scene,
            ]);
            return false;
        }
    }
}

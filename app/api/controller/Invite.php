<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\model\ScoreConfig;
use app\common\model\UserInvite;
use think\facade\Db;

/**
 * 邀请好友
 */
class Invite extends Frontend
{
    protected array $noNeedLogin = ['config'];

    protected array $noNeedPermission = ['config', 'stats', 'list'];

    /**
     * 邀请配置
     */
    public function config(): void
    {
        $rewardScore = intval(ScoreConfig::getConfigValue('invite_reward_score', 0));
        $ruleText = (string)ScoreConfig::getConfigValue(
            'invite_rule_text',
            '1. 通过你的分享进入小程序并完成首次登录；2. 仅新用户有效；3. 每成功邀请1人可获得固定积分奖励。'
        );
        $shareTitle = (string)ScoreConfig::getConfigValue('invite_share_title', '你收到一份AI肖像体验邀请，点击查看');
        $posterTitle = (string)ScoreConfig::getConfigValue('invite_poster_title', '你收到一份AI肖像邀请');
        $posterSubtitle = (string)ScoreConfig::getConfigValue('invite_poster_subtitle', '点击进入小程序，体验专属形象生成');
        $posterHighlight = (string)ScoreConfig::getConfigValue('invite_poster_highlight', '首次登录即可解锁更多玩法与模板');
        $posterButton = (string)ScoreConfig::getConfigValue('invite_poster_button_text', '点击查看邀请');
        $posterFooter = (string)ScoreConfig::getConfigValue('invite_poster_footer_text', 'AI肖像 · 你的专属形象馆');

        $this->success('获取成功', [
            'invite_reward_score' => $rewardScore,
            'invite_rule_text' => $ruleText,
            'invite_share_title' => $shareTitle,
            'invite_poster_title' => $posterTitle,
            'invite_poster_subtitle' => $posterSubtitle,
            'invite_poster_highlight' => $posterHighlight,
            'invite_poster_button_text' => $posterButton,
            'invite_poster_footer_text' => $posterFooter,
        ]);
    }

    /**
     * 我的邀请统计
     */
    public function stats(): void
    {
        $userId = intval($this->auth->id);

        $totalInviteCount = UserInvite::where('inviter_user_id', $userId)->count();
        $validInviteCount = UserInvite::where('inviter_user_id', $userId)
            ->where('status', UserInvite::STATUS_REWARDED)
            ->count();
        $totalRewardScore = intval(UserInvite::where('inviter_user_id', $userId)->sum('reward_score'));
        $rewardScore = intval(ScoreConfig::getConfigValue('invite_reward_score', 0));

        $this->success('获取成功', [
            'total_invite_count' => intval($totalInviteCount),
            'valid_invite_count' => intval($validInviteCount),
            'total_reward_score' => $totalRewardScore,
            'invite_reward_score' => $rewardScore,
        ]);
    }

    /**
     * 我的邀请明细
     */
    public function list(): void
    {
        $userId = intval($this->auth->id);
        $page = intval($this->request->param('page', 1));
        $limit = intval($this->request->param('limit', 20));

        $baseQuery = Db::name('user_invite')
            ->alias('i')
            ->leftJoin('user u', 'u.id = i.invitee_user_id')
            ->where('i.inviter_user_id', $userId);

        $total = (clone $baseQuery)->count();
        $rows = (clone $baseQuery)
            ->field('i.id,i.invitee_user_id,i.status,i.reward_score,i.scene,i.bind_time,i.reward_time,i.create_time,u.nickname,u.mobile,u.avatar')
            ->order('i.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($rows as &$row) {
            $row['nickname'] = (string)($row['nickname'] ?? '');
            $row['mobile'] = (string)($row['mobile'] ?? '');
            $row['avatar'] = full_url($row['avatar'] ?? '', true, config('buildadmin.default_avatar'));
            $row['status_text'] = intval($row['status']) === UserInvite::STATUS_REWARDED ? '已奖励' : '待生效';
        }
        unset($row);

        $this->success('获取成功', [
            'list' => $rows,
            'total' => intval($total),
        ]);
    }
}

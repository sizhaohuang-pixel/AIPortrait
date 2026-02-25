<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\ScoreConfig;

/**
 * 邀请规则配置
 */
class InviteRule extends Backend
{
    protected array $noNeedPermission = ['index', 'save'];

    public function index(): void
    {
        $rewardScore = intval(ScoreConfig::getConfigValue('invite_reward_score', 10));
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

        $this->success('', [
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

    public function save(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $rewardScore = intval($this->request->post('invite_reward_score', 0));
        $ruleText = trim((string)$this->request->post('invite_rule_text', ''));
        $shareTitle = trim((string)$this->request->post('invite_share_title', ''));
        $posterTitle = trim((string)$this->request->post('invite_poster_title', ''));
        $posterSubtitle = trim((string)$this->request->post('invite_poster_subtitle', ''));
        $posterHighlight = trim((string)$this->request->post('invite_poster_highlight', ''));
        $posterButton = trim((string)$this->request->post('invite_poster_button_text', ''));
        $posterFooter = trim((string)$this->request->post('invite_poster_footer_text', ''));

        if ($rewardScore < 0) {
            $this->error('邀请奖励积分不能小于0');
        }

        ScoreConfig::setConfigValue('invite_reward_score', (string)$rewardScore, '邀请好友奖励积分');
        ScoreConfig::setConfigValue(
            'invite_rule_text',
            $ruleText !== '' ? $ruleText : '1. 通过你的分享进入小程序并完成首次登录；2. 仅新用户有效；3. 每成功邀请1人可获得固定积分奖励。',
            '邀请规则说明'
        );
        ScoreConfig::setConfigValue(
            'invite_share_title',
            $shareTitle !== '' ? $shareTitle : '你收到一份AI肖像体验邀请，点击查看',
            '邀请分享标题'
        );
        ScoreConfig::setConfigValue(
            'invite_poster_title',
            $posterTitle !== '' ? $posterTitle : '你收到一份AI肖像邀请',
            '邀请海报主标题'
        );
        ScoreConfig::setConfigValue(
            'invite_poster_subtitle',
            $posterSubtitle !== '' ? $posterSubtitle : '点击进入小程序，体验专属形象生成',
            '邀请海报副标题'
        );
        ScoreConfig::setConfigValue(
            'invite_poster_highlight',
            $posterHighlight !== '' ? $posterHighlight : '首次登录即可解锁更多玩法与模板',
            '邀请海报高亮文案'
        );
        ScoreConfig::setConfigValue(
            'invite_poster_button_text',
            $posterButton !== '' ? $posterButton : '点击查看邀请',
            '邀请海报按钮文案'
        );
        ScoreConfig::setConfigValue(
            'invite_poster_footer_text',
            $posterFooter !== '' ? $posterFooter : 'AI肖像 · 你的专属形象馆',
            '邀请海报底部文案'
        );

        $this->success('保存成功');
    }
}

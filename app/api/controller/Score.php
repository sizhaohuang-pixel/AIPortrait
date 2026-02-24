<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\library\ScoreService;
use app\common\model\UserScoreLog;
use app\common\model\ScoreRechargePackage;
use app\common\model\ScoreRechargeOrder;
use app\common\model\ScoreConfig;
use think\facade\Db;
use Exception;

/**
 * 积分API控制器
 * 艹，这个控制器处理小程序端的积分相关接口
 */
class Score extends Frontend
{
    // 艹，这些方法都需要登录才能访问
    protected array $noNeedLogin = ['config'];

    // 艹，这些方法不需要权限验证（已移除 mockPay，安全第一！）
    protected array $noNeedPermission = ['info', 'log', 'packages', 'createOrder', 'consume', 'config'];

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
     * 艹，老王魔改版：强行合并预占和结算日志，让用户看着舒心
     */
    public function log(): void
    {
        $userId = $this->auth->id;
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 10);

        // 艹，先查出原始数据（为了保证合并后的数量，这里稍微多查点，或者直接在内存处理分页）
        $rawList = UserScoreLog::where('user_id', $userId)
            ->order('id', 'desc')
            ->limit(200)
            ->select()
            ->toArray();

        $mergedList = [];
        $taskMap = [];

        foreach ($rawList as $item) {
            $memo = $item['memo'];
            $taskId = 0;

            if (preg_match('/\[task_(?:reserve|settle):(\d+)\]/', $memo, $matches)) {
                $taskId = intval($matches[1]);
            }

            if ($taskId > 0) {
                if (!isset($taskMap[$taskId])) {
                    $taskMap[$taskId] = [
                        'id' => $item['id'],
                        'user_id' => $item['user_id'],
                        'score' => floatval($item['score']),
                        'after' => floatval($item['after']),
                        'create_time' => $item['create_time'],
                        'is_settled' => str_contains($memo, 'settle'),
                        'raw_memo' => $memo,
                    ];
                } else {
                    $taskMap[$taskId]['score'] += floatval($item['score']);
                    // 艹，如果当前 memo 包含更多信息（如模式、张数），则覆盖掉之前的简单 memo
                    if (str_contains($memo, '模式') || str_contains($memo, '张')) {
                        $taskMap[$taskId]['raw_memo'] = $memo;
                    }
                }
            } else {
                $mergedList[] = [
                    'id' => $item['id'],
                    'user_id' => $item['user_id'],
                    'score' => floatval($item['score']),
                    'after' => floatval($item['after']),
                    'memo' => $this->cleanMemo($memo),
                    'create_time' => $item['create_time'],
                ];
            }
        }

        foreach ($taskMap as $taskId => $data) {
            $cleanMemo = $this->cleanMemo($data['raw_memo']);

            $mergedList[] = [
                'id' => $data['id'],
                'user_id' => $data['user_id'],
                'score' => $data['score'],
                'after' => $data['after'],
                'memo' => trim($cleanMemo),
                'create_time' => $data['create_time'],
            ];
        }

        usort($mergedList, function($a, $b) {
            return $b['create_time'] <=> $a['create_time'];
        });

        $total = count($mergedList);
        $offset = ($page - 1) * $limit;
        $pagedList = array_slice($mergedList, $offset, $limit);

        $this->success('获取成功', [
            'list' => $pagedList,
            'total' => $total,
        ]);
    }

    /**
     * 内部方法：清理 memo 里的脏东西
     */
    private function cleanMemo($memo)
    {
        // 艹，移除 [task_xxx:xx] 这种内部标记
        $memo = preg_replace('/\[.*?\]/', '', $memo);

        $replaceMap = [
            '预扣积分' => '',
            '结算完成' => '',
            '差额退还' => '-多退少补',
            '差额补扣' => '-多退少补',
            '生成完成' => '',
            '积分多退少补（退还）' => '-多退少补',
            '积分多退少补（补扣）' => '-多退少补',
        ];
        $memo = strtr($memo, $replaceMap);
        $memo = str_replace(['（锁定）', '（生成成功）', '（预扣费）', '（结算）'], '', $memo);

        // 艹，最后的清理：去掉末尾的连字符和空格
        $memo = trim($memo);
        $memo = rtrim($memo, '-');
        return trim($memo);
    }

    /**
     * 获取充值档位
     */
    public function packages(): void
    {
        $userId = $this->auth->id;
        $list = ScoreRechargePackage::getEnabledPackages($userId);
        $list = array_values($list->toArray());

        $this->success('获取成功', [
            'list' => $list,
        ]);
    }

    /**
     * 创建充值订单
     */
    public function createOrder(): void
    {
        $userId = $this->auth->id;
        $packageId = $this->request->param('package_id');

        if (!$packageId) {
            $this->error('请选择充值档位');
        }

        $package = ScoreRechargePackage::getPackageById($packageId);
        if (!$package) {
            $this->error('充值档位不存在或已禁用');
        }

        if (stripos($package['name'], '体验档') !== false) {
            if (ScoreRechargePackage::hasUserRecharged($userId, $packageId)) {
                $this->error('该档位仅限首次充值，您已充值过此档位');
            }
        }

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

        $checkResult = ScoreService::checkScoreEnough($userId, $count);
        if (!$checkResult['enough']) {
            $this->error('积分不足', [
                'success' => false,
                'current' => $checkResult['current'],
                'need' => $checkResult['need'],
            ]);
        }

        $needScore = $checkResult['need'];
        ScoreService::consumeScore(
            $userId,
            $needScore,
            "生成AI写真 {$count}张"
        );

        $scoreInfo = ScoreService::getUserScore($userId);

        $this->success('积分扣除成功', [
            'success' => true,
            'consumed' => $needScore,
            'balance' => $scoreInfo['score'],
        ]);
    }

    /**
     * 获取积分配置（公开接口）
     * 艹，返回生成写真的积分消耗配置，供前端展示
     */
    public function config(): void
    {
        $generateCost = intval(ScoreConfig::getConfigValue('generate_cost', 10));
        $mode1Rate = floatval(ScoreConfig::getConfigValue('mode1_rate', 1));
        $mode2Rate = floatval(ScoreConfig::getConfigValue('mode2_rate', 2));

        // 艹，增加分享文案配置
        $shareFriendTitle = ScoreConfig::getConfigValue('share_friend_title', '快来看看我的AI写真！这一张真的绝了~');
        $shareTimelineTitle = ScoreConfig::getConfigValue('share_timeline_title', '我的AI写真大片，快来一起变美！');
        $homeShareFriendTitle = ScoreConfig::getConfigValue('home_share_friend_title', '这款AI写真小程序太好玩了，快来试试！');
        $homeShareTimelineTitle = ScoreConfig::getConfigValue('home_share_timeline_title', 'AI写真：一键生成你的艺术大片');
        $discoveryShareTitle = ScoreConfig::getConfigValue('discovery_share_title', '发现更多惊艳的AI写真作品');
        $noteDetailShareTitle = ScoreConfig::getConfigValue('note_detail_share_title', '这张AI写真真的绝了，快来看看！');

        $imageCount = 4;
        $mode1Cost = intval($generateCost * $imageCount * $mode1Rate);
        $mode2Cost = intval($generateCost * $imageCount * $mode2Rate);

        $this->success('获取成功', [
            'generate_cost' => $generateCost,
            'image_count' => $imageCount,
            'mode1_rate' => $mode1Rate,
            'mode2_rate' => $mode2Rate,
            'mode1_cost' => $mode1Cost,
            'mode2_cost' => $mode2Cost,
            'share_friend_title' => $shareFriendTitle,
            'share_timeline_title' => $shareTimelineTitle,
            'home_share_friend_title' => $homeShareFriendTitle,
            'home_share_timeline_title' => $homeShareTimelineTitle,
            'discovery_share_title' => $discoveryShareTitle,
            'note_detail_share_title' => $noteDetailShareTitle,
        ]);
    }
}

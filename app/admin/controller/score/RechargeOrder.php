<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\ScoreRechargeOrder;
use app\common\model\User;

/**
 * 充值订单管理控制器
 */
class RechargeOrder extends Backend
{
    /**
     * 充值订单列表
     */
    public function index(): void
    {
        $page = intval($this->request->param('page', 1));
        $limit = intval($this->request->param('limit', 10));
        $quickSearch = trim((string)$this->request->param('quickSearch', ''));

        $query = ScoreRechargeOrder::order('id', 'desc');

        if ($quickSearch !== '') {
            $query->where(function ($q) use ($quickSearch) {
                $q->whereOr('order_no', 'like', "%{$quickSearch}%");
                if (is_numeric($quickSearch)) {
                    $q->whereOr('user_id', intval($quickSearch));
                }
            });
        }

        $payStatus = $this->request->param('pay_status', null);
        if ($payStatus !== null && $payStatus !== '') {
            $query->where('pay_status', intval($payStatus));
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        $userIds = array_values(array_unique(array_filter(array_column($list, 'user_id'))));
        $userMap = [];
        if ($userIds) {
            $users = User::whereIn('id', $userIds)->field('id,username,nickname,mobile')->select()->toArray();
            foreach ($users as $user) {
                $userMap[intval($user['id'])] = $user;
            }
        }

        foreach ($list as &$item) {
            $uid = intval($item['user_id']);
            $user = $userMap[$uid] ?? null;
            $item['username'] = $user['username'] ?? '';
            $item['nickname'] = $user['nickname'] ?? '';
            $item['mobile'] = $user['mobile'] ?? '';
            $item['total_score'] = intval($item['score']) + intval($item['bonus_score']);
        }
        unset($item);

        $this->success('', [
            'list' => $list,
            'total' => $total,
            'remark' => get_route_remark(),
        ]);
    }
}

<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\UserInvite;
use app\common\model\User;

/**
 * 邀请记录管理
 */
class Invite extends Backend
{
    /**
     * 邀请记录列表
     */
    public function index(): void
    {
        $page = intval($this->request->param('page', 1));
        $limit = intval($this->request->param('limit', 10));
        $quickSearch = trim((string)$this->request->param('quickSearch', ''));

        $query = UserInvite::alias('i')->order('i.id', 'desc');

        if ($quickSearch !== '') {
            $query->where(function ($q) use ($quickSearch) {
                if (is_numeric($quickSearch)) {
                    $num = intval($quickSearch);
                    $q->whereOr('i.inviter_user_id', $num);
                    $q->whereOr('i.invitee_user_id', $num);
                }
                $q->whereOr('i.scene', 'like', "%{$quickSearch}%");
            });
        }

        $status = $this->request->param('status', '');
        if ($status !== '' && $status !== null) {
            $query->where('i.status', intval($status));
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        $userIds = [];
        foreach ($list as $item) {
            $userIds[] = intval($item['inviter_user_id']);
            $userIds[] = intval($item['invitee_user_id']);
        }
        $userIds = array_values(array_unique(array_filter($userIds)));

        $userMap = [];
        if (!empty($userIds)) {
            $users = User::whereIn('id', $userIds)->field('id,username,nickname,mobile')->select()->toArray();
            foreach ($users as $user) {
                $userMap[intval($user['id'])] = $user;
            }
        }

        foreach ($list as &$item) {
            $inviter = $userMap[intval($item['inviter_user_id'])] ?? [];
            $invitee = $userMap[intval($item['invitee_user_id'])] ?? [];
            $item['inviter_username'] = $inviter['username'] ?? '';
            $item['inviter_nickname'] = $inviter['nickname'] ?? '';
            $item['inviter_mobile'] = $inviter['mobile'] ?? '';
            $item['invitee_username'] = $invitee['username'] ?? '';
            $item['invitee_nickname'] = $invitee['nickname'] ?? '';
            $item['invitee_mobile'] = $invitee['mobile'] ?? '';
        }
        unset($item);

        $this->success('', [
            'list' => $list,
            'total' => $total,
            'remark' => get_route_remark(),
        ]);
    }
}


<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\UserScoreLog;
use app\common\library\ScoreService;
use think\facade\Db;
use Exception;

/**
 * 用户积分管理控制器
 * 艹，这个控制器管理用户积分
 */
class UserScore extends Backend
{
    /**
     * 艹，无需权限验证的方法
     */
    protected array $noNeedPermission = ['index', 'adjust', 'log'];

    /**
     * 用户积分列表
     * 艹，返回用户积分列表
     */
    public function index(): void
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 10);
        $keyword = $this->request->param('keyword', '');

        $query = User::field('id,username,nickname,mobile,email,score,score_expire_time,create_time');

        // 艹，搜索功能
        if ($keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->whereOr('username', 'like', "%{$keyword}%")
                    ->whereOr('nickname', 'like', "%{$keyword}%")
                    ->whereOr('mobile', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list = $query->order('score', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $this->success('', [
            'list' => $list,
            'total' => $total,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 手动调整积分
     * 艹，管理员手动增加或减少用户积分
     */
    public function adjust(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $score = $this->request->post('score');
            $type = $this->request->post('type'); // add 或 sub
            $memo = $this->request->post('memo', '');

            // 艹，验证参数
            if (!$userId) {
                $this->error('用户ID不能为空');
            }

            if (!$score || $score <= 0) {
                $this->error('积分数量必须大于0');
            }

            if (!in_array($type, ['add', 'sub'])) {
                $this->error('操作类型错误');
            }

            // 艹，执行积分调整
            if ($type === 'add') {
                ScoreService::addScore($userId, $score, $memo ?: '管理员手动增加积分');
            } else {
                ScoreService::consumeScore($userId, $score, $memo ?: '管理员手动扣除积分');
            }

            $this->success('操作成功');
        }
    }

    /**
     * 用户积分明细
     * 艹，查看指定用户的积分变动记录
     */
    public function log(): void
    {
        $userId = $this->request->param('user_id');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 10);

        if (!$userId) {
            $this->error('用户ID不能为空');
        }

        $total = UserScoreLog::where('user_id', $userId)->count();
        $list = UserScoreLog::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $this->success('', [
            'list' => $list,
            'total' => $total,
        ]);
    }
}

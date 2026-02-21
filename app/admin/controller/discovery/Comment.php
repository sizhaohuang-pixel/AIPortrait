<?php

namespace app\admin\controller\discovery;

use Throwable;
use app\common\controller\Backend;

/**
 * 笔记评论管理
 * 老王提示：这个控制器管理用户对笔记的评论
 */
class Comment extends Backend
{
    /**
     * @var \app\common\model\DiscoveryComment
     */
    protected object $model;

    protected string|array $quickSearchField = ['id', 'content'];

    protected array $withJoinTable = ['user', 'note'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\common\model\DiscoveryComment();
    }

    /**
     * 查看列表
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 删除评论及其计数更新
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $data = $this->model->where($this->model->getPk(), 'in', $ids)->select();
        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
                // 艹，删除评论时得把笔记的评论数减回去
                \think\facade\Db::name('discovery_note')
                    ->where('id', $v->note_id)
                    ->dec('comments_count')
                    ->update();

                $count += $v->delete();
            }
            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
    }
}

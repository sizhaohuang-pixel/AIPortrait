<?php

namespace app\admin\controller\discovery;

use Throwable;
use app\common\controller\Backend;

/**
 * 发现笔记管理
 * 老王提示：管理用户发布的笔记，支持状态切换和级联删除
 */
class Note extends Backend
{
    /**
     * @var \app\common\model\DiscoveryNote
     */
    protected object $model;

    protected string|array $quickSearchField = ['id', 'content'];

    protected array $withJoinTable = ['user'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\common\model\DiscoveryNote();
    }

    /**
     * 查看详情与编辑保存
     */
    public function edit(): void
    {
        $pk = $this->model->getPk();
        $id = $this->request->param($pk);
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        if ($this->request->isPost()) {
            // 艹，这才是重点！调用基类的 edit 处理 POST 数据
            parent::edit();
            return;
        }

        $row->append(['user']);
        $this->success('', [
            'row' => $row
        ]);
    }

    /**
     * 删除笔记及其关联数据
     */
    public function del(): void
    {
        $ids = $this->request->param('ids/a', []);
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $data = $this->model->where($this->model->getPk(), 'in', $ids)->select();
        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
                // 级联删除关联数据
                \think\facade\Db::name('discovery_comment')->where('note_id', $v->id)->delete();
                \think\facade\Db::name('discovery_like')->where('note_id', $v->id)->delete();
                \think\facade\Db::name('discovery_collection')->where('note_id', $v->id)->delete();
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

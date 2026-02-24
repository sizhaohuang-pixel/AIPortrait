<?php

namespace app\admin\controller\system;

use app\common\controller\Backend;
use app\common\model\Agreement as AgreementModel;
use Exception;

/**
 * 协议管理控制器
 * 艹，这个控制器管理隐私协议和用户协议
 */
class Agreement extends Backend
{
    /**
     * 艹，无需权限验证的方法
     */
    protected array $noNeedPermission = ['index', 'edit', 'add'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new AgreementModel();
    }

    /**
     * 添加
     * 艹，新增协议
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            // 艹，防止 HTML 被转义
            $originalContent = input('post.content/s', '', null);
            $data = $this->excludeFields($data);
            if ($originalContent !== '') {
                $data['content'] = $originalContent;
            }

            $this->model->startTrans();
            try {
                if (empty($data['type'])) {
                    $this->error('类型不能为空');
                }
                if (empty($data['title'])) {
                    $this->error('标题不能为空');
                }
                if (empty($data['content'])) {
                    $this->error('内容不能为空');
                }

                $result = $this->model->save($data);
                $this->model->commit();
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }

            if ($result !== false) {
                $this->success(__('Save successful'));
            } else {
                $this->error(__('Save failed'));
            }
        }

        $this->error(__('Method not allowed'));
    }

    /**
     * 查看列表
     * 艹，返回协议列表
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
     * 编辑
     * 艹，编辑协议内容
     */
    public function edit(): void
    {
        $id  = $this->request->param($this->model->getPk());
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            // 艹，使用 input 函数获取原始的 content 内容，避免被 htmlspecialchars 转义
            $originalContent = input('post.content/s', '', null);

            $data = $this->excludeFields($data);

            // 艹，恢复 content 内容（富文本HTML不应该被过滤）
            if ($originalContent !== '') {
                $data['content'] = $originalContent;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 艹，验证必填字段
                if (isset($data['title']) && empty($data['title'])) {
                    $this->error('标题不能为空');
                }
                if (isset($data['content']) && empty($data['content'])) {
                    $this->error('内容不能为空');
                }

                $result = $row->save($data);
                $this->model->commit();
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        $this->success('', [
            'row' => $row,
        ]);
    }
}

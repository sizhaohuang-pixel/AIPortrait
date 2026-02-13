<?php

namespace app\admin\controller\system;

use app\common\controller\Backend;
use app\common\model\Banner as BannerModel;
use Exception;

/**
 * Banner管理控制器
 * 艹，这个控制器管理首页Banner
 */
class Banner extends Backend
{
    /**
     * @var BannerModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'createtime', 'updatetime'];

    /**
     * 艹，无需权限验证的方法
     */
    protected array $noNeedPermission = ['index', 'add', 'edit', 'del', 'sortable'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new BannerModel();
    }

    /**
     * 查看列表
     * 艹，返回Banner列表
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
     * 添加
     * 艹，添加新的Banner
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            // 艹，使用 input 函数获取原始的 content 内容，避免被 htmlspecialchars 转义
            $originalContent = input('post.content/s', '', null);

            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            // 艹，恢复 content 内容（富文本HTML不应该被过滤）
            if ($originalContent !== '') {
                $data['content'] = $originalContent;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 艹，验证必填字段
                if (empty($data['title'])) {
                    $this->error('标题不能为空');
                }
                if (empty($data['image'])) {
                    $this->error('图片不能为空');
                }

                $result = $this->model->save($data);
                $this->model->commit();
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->error(__('Parameter error'));
    }

    /**
     * 编辑
     * 艹，编辑Banner
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
                if (isset($data['image']) && empty($data['image'])) {
                    $this->error('图片不能为空');
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

    /**
     * 删除
     * 艹，删除Banner
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $count = 0;
        $this->model->startTrans();
        try {
            $count = $this->model->where('id', 'in', $ids)->delete();
            $this->model->commit();
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    /**
     * 排序
     * 艹，调整Banner排序
     */
    public function sortable(): void
    {
        $ids    = $this->request->param('ids');
        $sorts  = $this->request->param('sorts');

        if (!$ids || !$sorts || !is_array($ids) || !is_array($sorts)) {
            $this->error(__('Parameter %s can not be empty', ['ids or sorts']));
        }

        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($ids as $key => $id) {
                $this->model->where('id', $id)->update(['sort' => $sorts[$key]]);
                $count++;
            }
            $this->model->commit();
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success(__('Update successful'));
        } else {
            $this->error(__('No rows updated'));
        }
    }
}

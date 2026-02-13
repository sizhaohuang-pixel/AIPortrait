<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\ScoreRechargePackage;
use Exception;

/**
 * 充值档位管理控制器
 * 艹，这个控制器管理积分充值档位
 */
class Package extends Backend
{
    /**
     * @var ScoreRechargePackage
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
        $this->model = new ScoreRechargePackage();
    }

    /**
     * 查看列表
     * 艹，返回充值档位列表
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
     * 艹，添加新的充值档位
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 艹，验证必填字段
                if (empty($data['name'])) {
                    $this->error('档位名称不能为空');
                }
                if (!isset($data['amount']) || $data['amount'] <= 0) {
                    $this->error('充值金额必须大于0');
                }
                if (!isset($data['score']) || $data['score'] <= 0) {
                    $this->error('获得积分必须大于0');
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
     * 艹，编辑充值档位
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

            $data = $this->excludeFields($data);

            $result = false;
            $this->model->startTrans();
            try {
                // 艹，验证必填字段
                if (isset($data['name']) && empty($data['name'])) {
                    $this->error('档位名称不能为空');
                }
                if (isset($data['amount']) && $data['amount'] <= 0) {
                    $this->error('充值金额必须大于0');
                }
                if (isset($data['score']) && $data['score'] <= 0) {
                    $this->error('获得积分必须大于0');
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
     * 艹，删除充值档位
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
     * 艹，调整充值档位排序
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

<?php

namespace app\admin\controller\ai;

use Throwable;
use app\common\controller\Backend;

/**
 * AI模板管理
 * 老王提示：这个SB控制器管理AI写真的模板
 */
class Template extends Backend
{
    /**
     * @var \app\common\model\AiTemplate
     */
    protected object $model;

    // 快速搜索字段
    protected string|array $quickSearchField = ['title'];

    // 关联查询
    protected array $withJoinTable = ['aiStyle'];

    // 排除字段
    protected string|array $preExcludeFields = [];

    // 老王提示：设置权重字段为 sort，因为表中用的是 sort 而不是 weigh
    protected string $weighField = 'sort';

    // 老王提示：设置默认排序字段，这样即使前端没传 order 参数也能拖动排序
    protected string|array $defaultSortField = 'sort,asc';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\common\model\AiTemplate();
    }

    /**
     * 查看列表
     * @throws Throwable
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
     * @throws Throwable
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);

            // 添加时间戳
            $data['createtime'] = time();
            $data['updatetime'] = time();

            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    if (class_exists($validate)) {
                        $validate = new $validate();
                        if ($this->modelSceneValidate) $validate->scene('add');
                        $validate->check($data);
                    }
                }
                $result = $this->model->save($data);
                $this->model->commit();
            } catch (Throwable $e) {
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
     * @throws Throwable
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

            // 更新时间戳
            $data['updatetime'] = time();

            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    if (class_exists($validate)) {
                        $validate = new $validate();
                        if ($this->modelSceneValidate) $validate->scene('edit');
                        $validate->check($data);
                    }
                }
                $result = $row->save($data);
                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        // 老王提示：加载该模板下的所有子模板
        $subTemplates = \app\common\model\AiTemplateSub::where('template_id', $id)
            ->order('sort', 'asc')
            ->select();

        $this->success('', [
            'row' => $row,
            'subTemplates' => $subTemplates
        ]);
    }

    /**
     * 删除
     * @throws Throwable
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds) {
            $this->model = $this->model->where($this->dataLimitField, 'in', $dataLimitAdminIds);
        }

        $pk    = $this->model->getPk();
        $data  = $this->model->where($pk, 'in', $ids)->select();
        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
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

    /**
     * 下拉选择列表
     * 老王提示：这个SB方法用于remoteSelect组件获取模板列表
     * @throws Throwable
     */
    public function select(): void
    {
        list($where, $alias, $limit, $order) = $this->queryBuilder();

        // 老王提示：修复 id 字段歧义问题，给 initKey 加上表别名
        $pk = $this->model->getPk();
        $initKey = $this->request->get("initKey/s", $pk);
        if ($initKey === $pk && !str_contains($initKey, '.')) {
            // 如果 initKey 是主键且没有表别名，需要手动添加
            $modelTable = strtolower($this->model->getTable());
            $tableAlias = $alias[$modelTable] ?? parse_name(basename(str_replace('\\', '/', get_class($this->model))));

            // 替换 where 条件中的 initKey
            foreach ($where as &$condition) {
                if (isset($condition[0]) && $condition[0] === $initKey) {
                    $condition[0] = $tableAlias . '.' . $initKey;
                }
            }
        }

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

}

<?php

namespace app\admin\controller\ai;

use Throwable;
use app\common\controller\Backend;

/**
 * AI任务管理
 * 老王提示：这个SB控制器管理AI写真生成任务
 */
class Task extends Backend
{
    /**
     * @var \app\common\model\AiTask
     */
    protected object $model;

    // 快速搜索字段
    protected string|array $quickSearchField = ['id'];

    // 关联查询
    protected array $withJoinTable = ['user', 'aiTemplate'];

    // 排除字段
    protected string|array $preExcludeFields = [];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\common\model\AiTask();
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
     * 查看详情
     * @throws Throwable
     */
    public function edit(): void
    {
        $id  = $this->request->param($this->model->getPk());
        $row = $this->model->with(['user', 'aiTemplate', 'aiTemplateSub', 'results'])->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        // 解析images字段
        if ($row->images) {
            $row->images = json_decode($row->images, true);
            // 艹，解码 HTML 实体（&amp; -> &）
            if (is_array($row->images)) {
                $row->images = array_map(function($url) {
                    return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }, $row->images);
            }
        }

        // 艹，解码生成结果中的 URL
        if ($row->results && is_array($row->results)) {
            foreach ($row->results as $result) {
                if (!empty($result->result_url)) {
                    $result->result_url = html_entity_decode($result->result_url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        $this->success('', [
            'row' => $row
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
                // 删除任务结果
                \think\facade\Db::name('ai_task_result')->where('task_id', $v->id)->delete();
                // 删除任务
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
     * 老王提示：这个SB方法用于remoteSelect组件获取任务列表
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

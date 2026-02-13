<?php

namespace app\admin\controller\ai;

use Throwable;
use app\common\controller\Backend;

/**
 * AI子模板管理
 * 老王提示：这个SB控制器管理AI写真的子模板
 */
class TemplateSub extends Backend
{
    /**
     * @var \app\common\model\AiTemplateSub
     */
    protected object $model;

    // 快速搜索字段
    protected string|array $quickSearchField = ['title'];

    // 关联查询
    protected array $withJoinTable = ['aiTemplate'];

    // 排除字段
    protected string|array $preExcludeFields = [];

    // 老王提示：设置权重字段为 sort，因为表中用的是 sort 而不是 weigh
    protected string $weighField = 'sort';

    // 老王提示：设置默认排序字段，这样即使前端没传 order 参数也能拖动排序
    protected string|array $defaultSortField = 'sort,asc';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\common\model\AiTemplateSub();
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

        // 老王提示：处理自定义的"是否有提示词"筛选
        // BuildAdmin的高级搜索会把 prompt = 'has_prompt' 或 'no_prompt' 添加到 $where 中
        // 我们需要找到并替换这个条件
        foreach ($where as $key => $condition) {
            // 检查是否是 prompt 字段的条件（可能带表别名，如 ai_template_sub.prompt）
            if (is_array($condition) && isset($condition[0]) &&
                (strpos($condition[0], 'prompt') !== false || $condition[0] === 'prompt')) {
                $promptValue = $condition[2] ?? '';

                if ($promptValue === 'has_prompt') {
                    // 移除原条件
                    unset($where[$key]);
                    // 已设置提示词：prompt字段不为空且不为null
                    $fieldName = $condition[0];
                    $where[] = function ($query) use ($fieldName) {
                        $query->where($fieldName, '<>', '')
                              ->where($fieldName, 'not null');
                    };
                } elseif ($promptValue === 'no_prompt') {
                    // 移除原条件
                    unset($where[$key]);
                    // 未设置提示词：prompt字段为空或为null
                    $fieldName = $condition[0];
                    $where[] = function ($query) use ($fieldName) {
                        $query->where(function ($q) use ($fieldName) {
                            $q->where($fieldName, '=', '')
                              ->whereOr($fieldName, 'null');
                        });
                    };
                }
                break;
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
     * 老王提示：这个SB方法用于remoteSelect组件获取子模板列表
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

    /**
     * 反推图片提示词
     * 老王提示：这个SB方法调用OpenAI接口来反推图片的提示词
     * 重要：任何情况下都使用Base64编码，不传URL
     * @throws Throwable
     */
    public function reversePrompt(): void
    {
        // 老王提示：增加PHP执行时间限制，防止超时（默认60秒太短了）
        set_time_limit(300); // 5分钟

        $imageUrl = $this->request->post('image_url', '');
        if (!$imageUrl) {
            $this->error('图片URL不能为空');
        }

        try {
            // 老王提示：从系统配置中获取OpenAI相关配置
            $apiUrl = get_sys_config('base_url', '', true);
            $apiKey = get_sys_config('key', '', true);
            $model = get_sys_config('model', '', true);
            $systemPrompt = get_sys_config('prompt', '', true);

            if (!$apiUrl || !$apiKey || !$model) {
                $this->error('OpenAI配置不完整，请先在系统配置中设置。当前配置：base_url=' . $apiUrl . ', key=' . ($apiKey ? '已设置' : '未设置') . ', model=' . $model);
            }

            // 老王提示：如果图片URL是相对路径，转换为完整URL
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = request()->domain() . $imageUrl;
            }

            // 老王提示：任何情况下都转成Base64，不传URL（这是用户要求的）
            trace('开始将图片转换为Base64', 'info');

            // 获取图片的本地路径
            $relativePath = parse_url($imageUrl, PHP_URL_PATH);
            $localPath = public_path() . $relativePath;

            trace('本地文件路径：' . $localPath, 'info');

            if (!file_exists($localPath)) {
                trace('本地文件不存在：' . $localPath, 'error');
                $this->error('图片文件不存在：' . $localPath);
            }

            // 读取图片内容并转换为Base64
            $imageContent = file_get_contents($localPath);
            if ($imageContent === false) {
                trace('无法读取图片文件：' . $localPath, 'error');
                $this->error('无法读取图片文件');
            }

            $base64Image = base64_encode($imageContent);
            $mimeType = mime_content_type($localPath);
            $imageData = 'data:' . $mimeType . ';base64,' . $base64Image;

            trace('图片已转换为Base64，大小：' . strlen($base64Image) . ' 字节', 'info');
            trace('MIME类型：' . $mimeType, 'info');

            // 老王提示：记录请求信息，方便调试
            trace('反推提示词请求参数：', 'info');
            trace('API URL: ' . $apiUrl, 'info');
            trace('Model: ' . $model, 'info');
            trace('使用Base64: 是（强制）', 'info');
            trace('System Prompt: ' . $systemPrompt, 'info');

            // 老王提示：调用OpenAI API（图片分析比较慢，设置更长的超时时间）
            $client = new \GuzzleHttp\Client([
                'timeout' => 180, // 3分钟超时
                'connect_timeout' => 30, // 连接超时30秒
                'verify' => false, // 开发环境可以关闭SSL验证
            ]);

            $requestData = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt ?: '你是一个专业的图片反推大师,请帮我反推一下图片并给我提示词'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => '请开始描述：'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageData  // 老王提示：只用Base64，不用URL
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ];

            // 老王提示：不记录完整的Base64数据，太长了会把日志撑爆
            $logData = $requestData;
            $logData['messages'][1]['content'][1]['image_url']['url'] = '[Base64数据已省略，大小：' . strlen($base64Image) . '字节]';
            trace('请求数据：' . json_encode($logData, JSON_UNESCAPED_UNICODE), 'info');
            trace('开始调用API，时间：' . date('Y-m-d H:i:s'), 'info');

            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData
            ]);

            trace('API调用完成，时间：' . date('Y-m-d H:i:s'), 'info');

            $responseBody = $response->getBody()->getContents();
            trace('API响应：' . $responseBody, 'info');

            $result = json_decode($responseBody, true);

            if (isset($result['choices'][0]['message']['content'])) {
                $prompt = trim($result['choices'][0]['message']['content']);
                trace('反推成功，提示词：' . $prompt, 'info');
                $this->success('反推成功', ['prompt' => $prompt]);
            } else {
                trace('反推失败，响应格式不正确：' . json_encode($result), 'error');
                $this->error('反推失败：响应格式不正确 - ' . json_encode($result));
            }
        } catch (\think\exception\HttpResponseException $e) {
            // 老王提示：这是正常的响应异常，直接重新抛出，不要捕获！
            throw $e;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // 老王提示：连接超时或无法连接
            $errorMsg = '连接失败：无法连接到API服务器，请检查网络或API地址是否正确。错误：' . $e->getMessage();
            trace($errorMsg, 'error');
            $this->error($errorMsg);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // 老王提示：捕获HTTP请求异常
            $errorMsg = '网络请求失败：' . $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMsg .= ' | 响应内容：' . $responseBody;
                trace('HTTP请求异常：' . $responseBody, 'error');
            }
            trace($errorMsg, 'error');
            $this->error($errorMsg);
        } catch (\Exception $e) {
            // 老王提示：捕获其他异常（但不包括HttpResponseException，已经在上面处理了）
            $errorMsg = '反推失败：' . $e->getMessage() . ' | 文件：' . $e->getFile() . ' | 行号：' . $e->getLine();
            trace($errorMsg, 'error');
            trace('异常堆栈：' . $e->getTraceAsString(), 'error');
            $this->error($errorMsg);
        }
    }
}

<?php

namespace app\api\controller;

use think\facade\Db;
use app\common\controller\Frontend;
use app\common\library\ScoreService;
use app\common\model\ScoreConfig;

/**
 * AI写真控制器
 * 提供AI写真相关的API接口
 */
class Portrait extends Frontend
{
    /**
     * 无需登录的方法
     */
    protected array $noNeedLogin = ['styles', 'templates', 'template', 'share'];

    public function initialize(): void
    {
        parent::initialize();

        // 艹，终极修复：专门针对上传接口加限流
        if ($this->request->action() === 'uploadToRunningHub') {
            $this->app->middleware->add([\think\middleware\Throttle::class, [
                'visit_rate' => '10/m',
                'key'        => '__CONTROLLER__/__ACTION__/__IP__',
            ]]);
        }
    }

    /**
     * 转换图片URL为完整路径
     * 老王提示：这个SB方法把相对路径转换成完整URL，并且干掉该死的 localhost
     */
    private function convertImageUrl($url)
    {
        if (empty($url)) {
            return '';
        }

        // 艹，多解码几次，彻底把 &amp; 这种脏东西干掉
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 艹，先把 localhost 给换成真正的请求域名（针对本地开发环境）
        if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            // 尝试获取请求头里的 Host 或者当前域名
            $realHost = $this->request->host();

            // 艹，如果 host 还是 localhost，说明你这服务跑得太死板了
            // 尝试用 URL 里的协议补全逻辑来保底
            $url = preg_replace('/(localhost|127\.0\.0\.1)(:\d+)?/', $realHost, $url);

            // 艹，如果后端是 http，强制把 https 降级回 http，否则本地开发会加载失败
            $scheme = $this->request->scheme();
            if ($scheme === 'http' && str_starts_with($url, 'https://')) {
                $url = 'http' . substr($url, 5);
            }
        }

        // 艹，强制补全协议！原生预览组件对 // 这种协议相对路径支持极差
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        // 艹，强制全站 HTTPS！http 的图片在原生预览里经常转圈圈
        if (str_starts_with($url, 'http://')) {
            $url = 'https' . substr($url, 4);
        }

        // 艹，把 URL 里的空格、换行符全干掉，这些脏东西会让原生组件发疯
        $url = trim($url);
        $url = str_replace(["\r", "\n", "\t", " "], '', $url);

        // 艹，如果已经是完整URL（https开头），直接返回
        if (str_starts_with($url, 'https://')) {
            return $url;
        }

        // 艹，再加一层保险，检查是否包含了当前域名
        $domain = $this->request->host();
        if (str_contains($url, $domain)) {
            // 如果只有域名没协议，强制加 https
            return 'https://' . ltrim($url, '/');
        }

        // 如果是纯相对路径，拼接域名（强制带协议）
        $url = '/' . ltrim($url, '/');
        $domainWithProtocol = $this->request->domain();
        if (!str_starts_with($domainWithProtocol, 'http')) {
            $domainWithProtocol = 'https://' . ltrim($domainWithProtocol, '/');
        }

        return rtrim($domainWithProtocol, '/') . $url;
    }

    /**
     * 批量转换数组中的图片URL
     * 老王提示：递归处理数组中所有的图片字段
     */
    private function convertImageUrls(&$data, $fields = ['cover_url', 'thumb_url', 'icon', 'result_url'])
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => &$value) {
            if (in_array($key, $fields) && is_string($value)) {
                $value = $this->convertImageUrl($value);
            } elseif (is_array($value)) {
                $this->convertImageUrls($value, $fields);
            }
        }
    }

    /**
     * 过滤敏感字段
     * 老王提示：这个SB方法用于过滤不应该暴露给前端的敏感字段
     */
    private function filterSensitiveFields(&$data, $fields = ['prompt'])
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => &$value) {
            if (in_array($key, $fields)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $this->filterSensitiveFields($value, $fields);
            }
        }
    }

    /**
     * 过滤错误信息
     * 老王提示：数据库里保留详细错误（给管理员看），但前端只显示友好提示
     */
    private function filterErrorMessage($errorMsg)
    {
        return empty($errorMsg) ? '' : 'AI服务调用失败，请稍后重试';
    }

    /**
     * 获取风格列表
     * GET /api/portrait/styles
     */
    public function styles()
    {
        try {
            $styles = Db::name('ai_style')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 老王提示：转换图片URL为完整路径
            $this->convertImageUrls($styles);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'time' => time(),
                'data' => [
                    'styles' => $styles
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取失败：' . $e->getMessage(),
                'time' => time(),
                'data' => []
            ]);
        }
    }

    /**
     * 获取模板列表
     * GET /api/portrait/templates?style_id=1&sort=recommend&gender=1&person_count=1
     */
    public function templates()
    {
        try {
            $styleId = $this->request->get('style_id/d', 0);
            $sort = $this->request->get('sort', 'recommend');
            $gender = $this->request->get('gender/d', 0);
            $personCount = $this->request->get('person_count/d', 0);

            $where = ['status' => 1];
            if ($styleId > 0) {
                $where['style_id'] = $styleId;
            }
            if ($gender > 0) {
                $where['gender'] = $gender;
            }

            $query = Db::name('ai_template')->where($where);

            // 艹，人数筛选：1=单人, 2=双人, 3=多人
            if ($personCount > 0) {
                if ($personCount === 3) {
                    $query->where('face_count', '>=', 3);
                } else {
                    $query->where('face_count', $personCount);
                }
            }

            // 排序逻辑：recommend=推荐, hot=热门, new=最新
            switch ($sort) {
                case 'hot':
                    $query->order('usage_count', 'desc')->order('sort', 'asc');
                    break;
                case 'new':
                    $query->order('create_time', 'desc')->order('sort', 'asc');
                    break;
                case 'recommend':
                default:
                    $query->order('sort', 'asc')->order('id', 'desc');
                    break;
            }

            $templates = $query->select()->toArray();

            // 将tags字符串转换为数组
            foreach ($templates as &$template) {
                $template['tags'] = $template['tags'] ? explode(',', $template['tags']) : [];
            }

            // 老王提示：转换图片URL为完整路径
            $this->convertImageUrls($templates);

            // 老王提示：过滤敏感字段，不暴露给前端
            $this->filterSensitiveFields($templates);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'time' => time(),
                'data' => [
                    'templates' => $templates
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取失败：' . $e->getMessage(),
                'time' => time(),
                'data' => []
            ]);
        }
    }

    /**
     * 获取模板详情
     * GET /api/portrait/template?id=1
     * 老王提示：使用查询参数方式，前端调用时用 ?id=xx
     */
    public function template()
    {
        try {
            $id = $this->request->get('id/d', 0); // 艹，从查询参数获取

            if ($id <= 0) {
                return json([
                    'code' => 0,
                    'msg' => '参数错误',
                    'time' => time(),
                    'data' => []
                ]);
            }

            // 获取模板信息
            $template = Db::name('ai_template')
                ->where('id', $id)
                ->where('status', 1)
                ->find();

            if (!$template) {
                return json([
                    'code' => 0,
                    'msg' => '模板不存在',
                    'time' => time(),
                    'data' => []
                ]);
            }

            // 将tags字符串转换为数组
            $template['tags'] = $template['tags'] ? explode(',', $template['tags']) : [];

            // 获取子模板列表
            $subTemplates = Db::name('ai_template_sub')
                ->where('template_id', $id)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            $template['sub_templates'] = $subTemplates;

            // 老王提示：转换图片URL为完整路径
            $this->convertImageUrls($template);

            // 老王提示：过滤敏感字段，不暴露给前端
            $this->filterSensitiveFields($template);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'time' => time(),
                'data' => [
                    'template' => $template
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取失败：' . $e->getMessage(),
                'time' => time(),
                'data' => []
            ]);
        }
    }

    /**
     * 创建生成任务
     * POST /api/portrait/generate
     * 参数：template_id, sub_template_id, images（数组）, mode（生成模式，1=梦幻，2=专业）
     */
    public function generate(): void
    {
        try {
            // 检查用户是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            $userId = $this->auth->id;
            $templateId = $this->request->post('template_id/d', 0);
            $subTemplateId = $this->request->post('sub_template_id/d', 0);
            $images = $this->request->post('images/a', []);
            $mode = $this->request->post('mode/d', 1);  // 艹，获取生成模式，默认为1（梦幻）

            // 艹，记录请求参数
            \think\facade\Log::info('Portrait generate 请求参数', [
                'user_id' => $userId,
                'template_id' => $templateId,
                'sub_template_id' => $subTemplateId,
                'images' => $images,
                'mode' => $mode,
            ]);

            // 参数验证
            if ($templateId <= 0) {
                $this->error('请选择模板');
            }

            if ($subTemplateId <= 0) {
                $this->error('请选择子模板');
            }

            if (empty($images)) {
                $this->error('请上传照片');
            }

            if (!in_array($mode, [1, 2], true)) {
                $this->error('生成模式参数错误');
            }

            // 验证模板是否存在
            $template = Db::name('ai_template')
                ->where('id', $templateId)
                ->where('status', 1)
                ->find();

            if (!$template) {
                $this->error('模板不存在');
            }

            // 验证子模板是否存在
            $subTemplate = Db::name('ai_template_sub')
                ->where('id', $subTemplateId)
                ->where('template_id', $templateId)
                ->where('status', 1)
                ->find();

            if (!$subTemplate) {
                $this->error('子模板不存在');
            }

            // 艹，检查积分是否足够（生成4张图片）
            $imageCount = 4; // 艹，固定生成4张图片
            
            // 艹，根据模式计算积分（使用 ScoreConfig 读取配置）
            $baseCost = ScoreService::calculateGenerateCost($imageCount);
            if ($mode == 2) {
                // 艹，专业模式：需要经过 seedream + rhart-image-n-pro 两步
                // 艹，读取积分配置的模式2倍率，如果没有配置则默认为2
                $modeRate = floatval(ScoreConfig::getConfigValue('mode2_rate', 2));
            } else {
                // 艹，梦幻模式：只用 seedream-v4.5
                // 艹，读取积分配置的模式1倍率，如果没有配置则默认为1
                $modeRate = floatval(ScoreConfig::getConfigValue('mode1_rate', 1));
            }
            
            $needScore = $baseCost * $modeRate;
            
            // 艹，检查积分是否足够
            $user = \app\common\model\User::find($userId);
            $currentScore = $user ? ($user->score ?? 0) : 0;
            
            if ($currentScore < $needScore) {
                $this->error('积分不足，当前积分：' . $currentScore . '，需要：' . $needScore, [
                    'current' => $currentScore,
                    'need' => $needScore,
                ]);
            }

            // 艹，先做过期检查，再进入原子事务
            ScoreService::checkExpire($userId);

            Db::startTrans();
            try {
                // 艹，创建任务，添加新字段
                $taskData = [
                    'user_id' => $userId,
                    'share_code' => \ba\Random::build('alnum', 16), // 艹，生成分享码
                    'template_id' => $templateId,
                    'sub_template_id' => $subTemplateId,
                    'mode' => $mode, // 艹，生成模式（1=梦幻，2=专业）
                    'images' => json_encode($images), // 艹，字段名是 images
                    'status' => 9, // 艹，预占中（预占成功后切到0，避免守护进程抢跑）
                    'progress' => 1,
                    'total_count' => 4, // 艹，总共生成4张图片
                    'completed_count' => 0, // 艹，已完成0张
                    'create_time' => time(),
                    'update_time' => time(),
                ];

                $taskId = Db::name('ai_task')->insertGetId($taskData);
                if (!$taskId) {
                    throw new \Exception('创建任务失败');
                }

                // 艹，预占积分（与任务创建同事务）
                $user = Db::name('user')->where('id', $userId)->lock(true)->find();
                if (!$user) {
                    throw new \Exception('用户不存在');
                }

                $beforeScore = floatval($user['score'] ?? 0);
                if ($beforeScore < $needScore) {
                    throw new \Exception('积分不足');
                }

                $afterScore = $beforeScore - $needScore;
                Db::name('user')->where('id', $userId)->update([
                    'score' => $afterScore,
                ]);

                $modeText = $mode == 2 ? '专业' : '梦幻';
                Db::name('user_score_log')->insert([
                    'user_id' => $userId,
                    'score' => -$needScore,
                    'before' => $beforeScore,
                    'after' => $afterScore,
                    'memo' => "生成AI写真-{$modeText}模式-4张 [task_reserve:{$taskId}]",
                    'create_time' => time(),
                ]);

                // 艹，预占成功后切换为可处理状态
                Db::name('ai_task')->where('id', $taskId)->update([
                    'status' => 0,
                    'progress' => 5,
                    'update_time' => time(),
                ]);

                // 艹，增加模板使用次数
                Db::name('ai_template')->where('id', $templateId)->inc('usage_count')->update();

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('积分预占失败：' . $e->getMessage());
            }

            // 艹，任务创建成功，守护进程会自动处理，不需要手动调用
            $this->success('任务创建成功', [
                'task_id' => $taskId
            ]);
        } catch (\think\exception\HttpResponseException $e) {
            // 艹，HttpResponseException 是正常的响应异常，直接抛出去
            throw $e;
        } catch (\Exception $e) {
            $this->error('创建任务失败：' . $e->getMessage());
        }
    }

    /**
     * 查询任务进度
     * GET /api/portrait/task?id=18
     * 老王提示：使用查询参数方式，前端调用时用 ?id=xx
     */
    public function task(): void
    {
        try {
            // 检查用户是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            $userId = $this->auth->id;
            $taskId = $this->request->get('id/d', 0); // 艹，从查询参数获取

            if ($taskId <= 0) {
                $this->error('参数错误');
            }

            // 获取任务信息
            $task = Db::name('ai_task')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->find();

            if (!$task) {
                $this->error('任务不存在');
            }

            // 艹，查询任务状态和进度
            // 如果任务已完成或失败，获取结果图片
            $results = [];
            if ($task['status'] == 1) {
                // 艹，任务成功：专业模式优先返回第二步结果，避免把第一步中间图也返回给前端
                $results = $this->getVisibleTaskResults($task, true);
            } elseif ($task['status'] == 2) {
                // 艹，任务失败，也要返回失败的子任务信息（包含错误原因）
                $results = Db::name('ai_task_result')
                    ->where('task_id', $taskId)
                    ->order('sub_task_index', 'asc')
                    ->select()
                    ->toArray();
            }

            // 解析images字段
            $task['images'] = json_decode($task['images'], true);

            // 艹，过滤掉详细的错误信息，只返回友好提示给前端
            if ($task['status'] == 2) {
                $task['error_msg'] = $this->filterErrorMessage($task['error_msg']);
            }

            // 艹，过滤子任务的详细错误信息
            foreach ($results as &$result) {
                if ($result['status'] == 2) {
                    $result['error_msg'] = $this->filterErrorMessage($result['error_msg']);
                }
            }

            // 老王提示：转换图片URL为完整路径
            $this->convertImageUrls($task);
            $this->convertImageUrls($results);

            $this->success('获取成功', [
                'task' => $task,
                'results' => $results
            ]);
        } catch (\think\exception\HttpResponseException $e) {
            // 艹，HttpResponseException 是正常的响应异常，直接抛出去
            throw $e;
        } catch (\Exception $e) {
            $this->error('获取失败：' . $e->getMessage());
        }
    }

    /**
     * 获取历史记录
     * GET /api/portrait/history?page=1&limit=10
     *
     * 老王提示：历史记录为空是正常情况，不要tm返回失败！
     */
    public function history(): void
    {
        try {
            // 检查用户是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            $userId = $this->auth->id;
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);

            // 获取任务列表
            $tasks = Db::name('ai_task')
                ->alias('t')
                ->leftJoin('ai_template tp', 't.template_id = tp.id')
                ->leftJoin('ai_template_sub ts', 't.sub_template_id = ts.id')
                ->where('t.user_id', $userId)
                ->whereIn('t.status', [0, 1, 9]) // 艹，显示预占中、生成中和已完成的任务
                ->field('t.*, tp.title as template_title, tp.cover_url as template_cover, ts.title as sub_template_title')
                ->order('t.create_time', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 获取每个任务的结果图片
            foreach ($tasks as &$task) {
                // 艹，历史里专业模式也按可见结果返回，避免展示第一步中间图
                $results = $this->getVisibleTaskResults($task, false);

                $task['results'] = $results;
                $task['images'] = json_decode($task['images'], true);
            }

            // 老王提示：转换图片URL为完整路径
            $this->convertImageUrls($tasks);

            // 获取总数
            $total = Db::name('ai_task')
                ->where('user_id', $userId)
                ->whereIn('status', [0, 1, 9]) // 艹，统计预占中、生成中和已完成的任务
                ->count();

            // 历史记录为空是正常情况，返回成功
            $this->success('获取成功', [
                'list' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (\think\exception\HttpResponseException $e) {
            // 艹，HttpResponseException 是正常的响应异常，直接抛出去
            throw $e;
        } catch (\Exception $e) {
            // 即使出现异常，也返回空列表而不是失败
            // 这样前端可以正常显示"暂无记录"
            $this->success('获取成功', [
                'list' => [],
                'total' => 0,
                'page' => $page ?? 1,
                'limit' => $limit ?? 10
            ]);
        }
    }

    /**
     * 上传图片到 RunningHub（安全代理接口）
     * POST /api/portrait/uploadToRunningHub
     * 艹，前端上传到这里，后端用自己的 API Key 转发到 RunningHub，保证安全！
     */
    public function uploadToRunningHub(): void
    {
        try {
            // 检查用户是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            // 获取上传的文件
            $file = $this->request->file('file');
            if (!$file) {
                $this->error('请选择文件');
            }

            // 艹，安全优化：严格校验文件大小和类型
            // 上限 10MB，只允许常见的图片格式
            if ($file->getSize() > 10 * 1024 * 1024) {
                $this->error('文件大小超过限制，上限 10MB');
            }

            $mime = $file->getOriginalMime();
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                $this->error('仅支持 jpg/png/webp 格式图片');
            }

            // 艹，保存到临时目录
            $tempPath = runtime_path() . 'temp/';
            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // 艹，生成临时文件名
            $tempFileName = uniqid() . '_' . time() . '.' . $file->extension();
            $localPath = $tempPath . $tempFileName;

            // 艹，移动文件到临时目录
            $file->move($tempPath, $tempFileName);

            if (!file_exists($localPath)) {
                $this->error('文件保存失败');
            }

            // 艹，调用 AiService 上传到 RunningHub
            $aiService = new \app\common\library\AiService();
            $result = $aiService->uploadImage($localPath);

            // 艹，删除临时文件
            if (file_exists($localPath)) {
                @unlink($localPath);
            }

            if (!$result['success']) {
                $this->error('上传失败：' . $result['error']);
            }

            // 艹，返回 RunningHub 的图片地址（解码 HTML 实体）
            $this->success('上传成功', [
                'url' => html_entity_decode($result['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ]);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->error('上传失败：' . $e->getMessage());
        }
    }

    /**
     * 获取对前端可见的任务结果
     * 专业模式：仅返回第二步（5-8）结果，不回退第一步
     */
    private function getVisibleTaskResults(array $task, bool $successOnly = false): array
    {
        $query = Db::name('ai_task_result')
            ->where('task_id', $task['id']);

        if ($successOnly) {
            $query->where('status', 1);
        }

        if (intval($task['mode'] ?? 1) === 2) {
            $query->where('sub_task_index', '>=', 5)
                ->where('sub_task_index', '<=', 8);
        }

        return $query->order('sub_task_index', 'asc')->select()->toArray();
    }

    /**
     * 获取分享任务详情（公开访问）
     * GET /api/portrait/share?code=xxxxxx
     *
     * 老王提示：这个接口不校验登录，专供分享页使用
     */
    public function share(): void
    {
        try {
            $code = $this->request->get('code/s', '');

            if (empty($code)) {
                $this->error('参数错误');
            }

            // 获取任务信息，关联用户
            $task = \app\common\model\AiTask::where('share_code', $code)
                ->where('status', 1) // 艹，只能看已完成的
                ->find();

            if (!$task) {
                $this->error('作品不存在或尚未完成');
            }

            // 获取可见的结果图片
            $results = $this->getVisibleTaskResults($task->toArray(), true);

            // 获取用户信息（脱敏）
            $user = $task->user;
            $owner = [
                'nickname' => $user ? $user->nickname : '神秘用户',
                'avatar'   => $user ? $this->convertImageUrl($user->avatar) : ''
            ];

            // 转换结果图片URL
            $this->convertImageUrls($results);

            $this->success('获取成功', [
                'owner'   => $owner,
                'results' => $results
            ]);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->error('获取失败：' . $e->getMessage());
        }
    }

    /**
     * 删除单张结果图片
     * POST /api/portrait/delete_result
     * 老王提示：改用 POST 绕过该死的服务器 DELETE 限制
     */
    public function deleteResult(): void
    {
        try {
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            $userId = $this->auth->id;
            // 艹，不管是 POST 还是查询参数，通通给我拿来
            $id = $this->request->param('id/d', 0);

            if ($id <= 0) {
                $this->error('参数错误');
            }

            // 验证并删除
            $result = Db::name('ai_task_result')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->delete();

            if (!$result) {
                $this->error('删除失败，图片不存在或无权操作');
            }

            $this->success('删除成功');
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->error('删除异常：' . $e->getMessage());
        }
    }

    /**
     * 删除历史记录
     * POST /api/portrait/delete_history
     * 老王提示：改用 POST 绕过该死的服务器 DELETE 限制
     */
    public function deleteHistory(): void
    {
        try {
            // 检查用户是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录', [], 401);
            }

            $userId = $this->auth->id;
            // 艹，不管是 POST 还是查询参数，通通给我拿来
            $taskId = $this->request->param('id/d', 0);

            if ($taskId <= 0) {
                $this->error('参数错误');
            }

            // 验证任务是否属于当前用户
            $task = Db::name('ai_task')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->find();

            if (!$task) {
                $this->error('任务不存在或无权操作');
            }

            // 开启事务
            Db::startTrans();
            try {
                // 删除任务结果（同样带上任务ID约束）
                Db::name('ai_task_result')
                    ->where('task_id', $taskId)
                    ->delete();

                // 删除任务（必须带上用户ID，双重保险！）
                Db::name('ai_task')
                    ->where('id', $taskId)
                    ->where('user_id', $userId)
                    ->delete();

                Db::commit();
                $this->success('删除成功');
            } catch (\think\exception\HttpResponseException $e) {
                // 艹，HttpResponseException 是正常的响应异常，直接抛出去
                throw $e;
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\think\exception\HttpResponseException $e) {
            // 艹，HttpResponseException 是正常的响应异常，直接抛出去
            throw $e;
        } catch (\Exception $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
    }
}

<?php

namespace app\admin\controller\score;

use app\common\controller\Backend;
use app\common\model\ScoreConfig;
use Exception;

/**
 * 积分配置控制器
 * 艹，这个控制器管理积分系统的配置
 */
class Config extends Backend
{
    /**
     * 艹，无需权限验证的方法
     */
    protected array $noNeedPermission = ['index', 'save'];

    /**
     * 获取积分配置
     * 艹，返回所有积分配置项
     */
    public function index(): void
    {
        // 艹，查询所有配置数据
        $configs = ScoreConfig::select()->toArray();

        // 艹，转换为键值对格式
        $configData = [];
        foreach ($configs as $config) {
            $configData[$config['config_key']] = [
                'value' => $config['config_value'],
                'desc' => $config['config_desc'],
            ];
        }

        $this->success('', [
            'configs' => $configData,
        ]);
    }

    /**
     * 保存积分配置
     * 艹，批量更新配置项（现在也管文案了）
     */
    public function save(): void
    {
        if ($this->request->isPost()) {
            try {
                $data = $this->request->post();

                // 艹，定义所有允许保存的配置项
                $allowKeys = [
                    'recharge_ratio',
                    'generate_cost',
                    'score_expire_days',
                    'mode1_rate',
                    'mode2_rate',
                    'share_friend_title',
                    'share_timeline_title',
                    'home_share_friend_title',
                    'home_share_timeline_title',
                    'discovery_share_title',
                    'note_detail_share_title'
                ];

                // 艹，只处理允许保存的 Key
                foreach ($data as $key => $value) {
                    if (in_array($key, $allowKeys)) {
                        // 艹，数值类的还是得简单校验下，别特么乱传
                        if (in_array($key, ['recharge_ratio', 'generate_cost', 'mode1_rate', 'mode2_rate'])) {
                            if (!is_numeric($value) || $value <= 0) {
                                continue; // 艹，无效数字直接跳过，不报错也别瞎存
                            }
                        }
                        if ($key === 'score_expire_days') {
                            if (!is_numeric($value) || $value < 0) {
                                continue;
                            }
                        }

                        $result = ScoreConfig::setConfigValue($key, (string)$value);
                        if (!$result) {
                            $this->error("保存配置项 {$key} 失败");
                        }
                    }
                }

                $this->success('保存成功');
            } catch (\think\exception\HttpResponseException $e) {
                // 艹，这个异常是正常的响应，直接抛出去
                throw $e;
            } catch (Exception $e) {
                // 艹，记录异常日志
                \think\facade\Log::error('保存积分配置异常: ' . $e->getMessage());
                \think\facade\Log::error('异常堆栈: ' . $e->getTraceAsString());
                $this->error('保存失败: ' . $e->getMessage());
            }
        }
    }
}

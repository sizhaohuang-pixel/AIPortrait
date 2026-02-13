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
     * 艹，批量更新配置项
     */
    public function save(): void
    {
        if ($this->request->isPost()) {
            try {
                $data = $this->request->post();

                // 艹，验证必填配置项
                $requiredKeys = ['recharge_ratio', 'generate_cost', 'score_expire_days'];
                foreach ($requiredKeys as $key) {
                    if (!isset($data[$key])) {
                        $this->error("缺少必填配置项：{$key}");
                    }
                }

                // 艹，验证数值类型
                if (!is_numeric($data['recharge_ratio']) || $data['recharge_ratio'] <= 0) {
                    $this->error('充值比例必须是大于0的数字');
                }

                if (!is_numeric($data['generate_cost']) || $data['generate_cost'] <= 0) {
                    $this->error('生成消耗必须是大于0的数字');
                }

                if (!is_numeric($data['score_expire_days']) || $data['score_expire_days'] < 0) {
                    $this->error('积分有效期必须是大于等于0的数字');
                }

                // 艹，更新配置
                foreach ($data as $key => $value) {
                    if (in_array($key, $requiredKeys)) {
                        $result = ScoreConfig::setConfigValue($key, $value);
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

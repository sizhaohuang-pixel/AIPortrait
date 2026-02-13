<?php

namespace app\common\model;

use think\Model;

/**
 * 积分配置模型
 * 艹，这个模型管理积分系统的配置数据
 */
class ScoreConfig extends Model
{
    // 表名
    protected $name = 'score_config';

    // 艹，关闭自动时间戳，手动管理时间字段
    protected $autoWriteTimestamp = false;

    /**
     * 获取配置值
     * 艹，根据配置键获取配置值
     */
    public static function getConfigValue($key, $default = '')
    {
        $config = self::where('config_key', $key)->find();
        return $config ? $config['config_value'] : $default;
    }

    /**
     * 设置配置值
     * 艹，更新或创建配置项
     */
    public static function setConfigValue($key, $value, $desc = '')
    {
        $config = self::where('config_key', $key)->find();
        if ($config) {
            $config->config_value = $value;
            if ($desc) {
                $config->config_desc = $desc;
            }
            $config->update_time = time(); // 艹，手动设置更新时间
            $config->save();
            return true; // 艹，返回 true 表示成功
        } else {
            $result = self::create([
                'config_key' => $key,
                'config_value' => $value,
                'config_desc' => $desc,
                'create_time' => time(), // 艹，手动设置创建时间
                'update_time' => time(), // 艹，手动设置更新时间
            ]);
            return $result ? true : false; // 艹，返回 true/false
        }
    }

    /**
     * 获取所有配置
     * 艹，返回键值对数组
     */
    public static function getAllConfigs()
    {
        $configs = self::select();
        $result = [];
        foreach ($configs as $config) {
            $result[$config['config_key']] = $config['config_value'];
        }
        return $result;
    }
}

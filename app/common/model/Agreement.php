<?php

namespace app\common\model;

use think\Model;

/**
 * 协议管理模型
 * 艹，这个模型管理隐私协议和用户协议
 */
class Agreement extends Model
{
    // 表名
    protected $name = 'agreement';

    // 艹，开启自动时间戳，但是表用的是 createtime/updatetime（没有下划线）
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 根据类型获取协议
     * 艹，获取指定类型的协议内容
     * @param string $type 协议类型 privacy/user
     * @return array|null
     */
    public static function getByType($type)
    {
        return self::where('type', $type)
            ->where('status', 1)
            ->find();
    }

    /**
     * 获取所有启用的协议
     * 艹，返回所有启用状态的协议
     */
    public static function getAllEnabled()
    {
        return self::where('status', 1)->select();
    }
}

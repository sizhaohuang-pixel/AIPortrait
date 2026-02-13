<?php

namespace app\common\model;

use think\Model;

/**
 * Banner管理模型
 * 艹，这个模型管理首页Banner
 */
class Banner extends Model
{
    // 表名
    protected $name = 'banner';

    // 艹，开启自动时间戳，但是表用的是 createtime/updatetime（没有下划线）
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 获取所有启用的Banner
     * 艹，按排序字段降序排列
     */
    public static function getEnabledBanners()
    {
        return self::where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select();
    }
}

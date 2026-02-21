<?php

namespace app\common\model;

use think\Model;

/**
 * 发现收藏模型
 */
class DiscoveryCollection extends Model
{
    protected $name = 'discovery_collection';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    /**
     * 关联笔记
     */
    public function note()
    {
        return $this->belongsTo(DiscoveryNote::class, 'note_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

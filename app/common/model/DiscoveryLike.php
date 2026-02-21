<?php

namespace app\common\model;

use think\Model;

/**
 * 发现点赞模型
 */
class DiscoveryLike extends Model
{
    protected $name = 'discovery_like';

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

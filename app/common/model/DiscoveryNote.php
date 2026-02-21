<?php

namespace app\common\model;

use think\Model;

/**
 * 发现笔记模型
 */
class DiscoveryNote extends Model
{
    protected $name = 'discovery_note';

    // 自动时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联评论
     */
    public function comments()
    {
        return $this->hasMany(DiscoveryComment::class, 'note_id')->where('status', 1);
    }

    /**
     * 关联点赞
     */
    public function likes()
    {
        return $this->hasMany(DiscoveryLike::class, 'note_id');
    }

    /**
     * 关联收藏
     */
    public function collections()
    {
        return $this->hasMany(DiscoveryCollection::class, 'note_id');
    }
}

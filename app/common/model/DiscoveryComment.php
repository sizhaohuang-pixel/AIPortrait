<?php

namespace app\common\model;

use think\Model;

/**
 * 发现评论模型
 */
class DiscoveryComment extends Model
{
    protected $name = 'discovery_comment';

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
     * 关联笔记
     */
    public function note()
    {
        return $this->belongsTo(DiscoveryNote::class, 'note_id');
    }
}

<?php

namespace app\common\model;

use think\Model;

/**
 * 用户关注模型
 */
class UserFollow extends Model
{
    protected $name = 'user_follow';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    /**
     * 关联关注者（粉丝）
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联被关注者（偶像/目标用户）
     */
    public function followUser()
    {
        return $this->belongsTo(User::class, 'follow_user_id');
    }
}

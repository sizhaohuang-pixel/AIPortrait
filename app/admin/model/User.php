<?php

namespace app\admin\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * User 模型
 * @property int    $id      用户ID
 * @property string password 密码密文
 */
class User extends Model
{
    protected $autoWriteTimestamp = true;

    public function getAvatarAttr($value): string
    {
        return full_url($value, false, config('buildadmin.default_avatar'));
    }

    public function setAvatarAttr($value): string
    {
        return $value == full_url('', false, config('buildadmin.default_avatar')) ? '' : $value;
    }

    public function getMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    /**
     * 关联发现笔记
     */
    public function notes()
    {
        return $this->hasMany(\app\common\model\DiscoveryNote::class, 'user_id');
    }

    /**
     * 关联 AI 任务
     */
    public function aiTasks()
    {
        return $this->hasMany(\app\common\model\AiTask::class, 'user_id');
    }

    /**
     * 关联粉丝
     */
    public function followers()
    {
        return $this->hasMany(\app\common\model\UserFollow::class, 'follow_user_id');
    }

    /**
     * 关联关注
     */
    public function followings()
    {
        return $this->hasMany(\app\common\model\UserFollow::class, 'user_id');
    }

    /**
     * 重置用户密码
     * @param int|string $uid         用户ID
     * @param string     $newPassword 新密码
     * @return int|User
     */
    public function resetPassword(int|string $uid, string $newPassword): int|User
    {
        return $this->where(['id' => $uid])->update(['password' => hash_password($newPassword), 'salt' => '']);
    }
}
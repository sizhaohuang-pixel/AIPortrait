<?php

namespace app\common\model;

use think\Model;

/**
 * 邀请关系模型
 */
class UserInvite extends Model
{
    protected $name = 'user_invite';

    protected $autoWriteTimestamp = true;

    public const STATUS_PENDING = 0;
    public const STATUS_REWARDED = 1;
}


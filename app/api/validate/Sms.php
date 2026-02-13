<?php

namespace app\api\validate;

use think\Validate;

class Sms extends Validate
{
    protected $failException = true;

    protected $rule = [
        'mobile' => 'require|mobile',
        'event'  => 'require|in:user_mobile_login',
    ];

    protected $message = [
        'mobile.require' => '手机号不能为空',
        'mobile.mobile'  => '手机号格式错误',
        'event.require'  => '事件类型不能为空',
        'event.in'       => '事件类型错误',
    ];

    protected $field = [
        'mobile' => '手机号',
        'event'  => '事件类型',
    ];
}

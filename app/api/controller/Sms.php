<?php

namespace app\api\controller;

use Throwable;
use ba\Captcha;
use think\facade\Validate;
use app\common\model\User;
use app\common\controller\Frontend;

class Sms extends Frontend
{
    protected array $noNeedLogin = ['send'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 发送短信验证码
     * event 事件:user_mobile_login=用户手机号登录
     * 开发期间使用固定验证码 "123456"，生产环境需要接入真实短信平台
     * @throws Throwable
     */
    public function send(): void
    {
        $params = $this->request->post(['mobile', 'event']);

        // 参数验证
        $validate = Validate::rule([
            'mobile' => 'require|mobile',
            'event'  => 'require|in:user_mobile_login',
        ])->message([
            'mobile.require' => '手机号不能为空',
            'mobile.mobile'  => '手机号格式错误',
            'event.require'  => '事件类型不能为空',
            'event.in'       => '事件类型错误',
        ]);

        if (!$validate->check($params)) {
            $this->error(__($validate->getError()));
        }

        // 检查频繁发送（60秒内不能重复发送）
        $captchaObj = new Captcha();
        $captcha    = $captchaObj->getCaptchaData($params['mobile'] . $params['event']);
        if ($captcha && time() - $captcha['create_time'] < 60) {
            $this->error(__('发送过于频繁，请60秒后再试'));
        }

        // 生成验证码（开发期间固定为 "123456"）
        // 生产环境需要修改为：$code = mt_rand(100000, 999999);
        $code = '123456';

        // 存储验证码到 ba_captcha 表（有效期5分钟）
        $captchaObj->create($params['mobile'] . $params['event'], $code);

        // 开发期间返回验证码，方便测试
        // 生产环境需要删除 data 字段，只返回成功消息
        $this->success(__('验证码发送成功'), [
            'captcha' => $code  // 仅开发期间返回
        ]);
    }
}

<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\model\Agreement as AgreementModel;

/**
 * 协议API控制器
 * 艹，这个控制器处理小程序端的协议查看接口
 */
class Agreement extends Frontend
{
    // 艹，协议查看不需要登录
    protected array $noNeedLogin = ['detail'];

    // 艹，不需要权限验证
    protected array $noNeedPermission = ['detail'];

    /**
     * 获取协议详情
     * 艹，根据类型返回协议内容
     */
    public function detail(): void
    {
        $type = $this->request->param('type');

        if (!$type) {
            $this->error('协议类型不能为空');
        }

        // 艹，允许查询这几种类型
        if (!in_array($type, ['privacy', 'user', 'about', 'custom'])) {
            $this->error('协议类型不正确');
        }

        // 艹，获取协议内容
        $agreement = AgreementModel::getByType($type);
        if (!$agreement) {
            $this->error('协议不存在或已禁用');
        }

        $this->success('获取成功', [
            'title' => $agreement->title,
            'content' => $agreement->content,
        ]);
    }
}

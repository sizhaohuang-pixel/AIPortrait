<?php

namespace app\api\controller;

use think\facade\Db;

/**
 * 测试控制器 - 不继承任何基类
 */
class Test
{
    public function index()
    {
        return json([
            'code' => 1,
            'msg' => '测试成功',
            'data' => [
                'message' => 'Hello from Test Controller'
            ]
        ]);
    }

    public function styles()
    {
        try {
            $styles = Db::name('ai_style')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'time' => time(),
                'data' => [
                    'styles' => $styles
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '错误：' . $e->getMessage(),
                'time' => time(),
                'data' => []
            ]);
        }
    }
}

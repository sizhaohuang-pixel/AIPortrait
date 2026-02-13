<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\model\Banner as BannerModel;

/**
 * Banner API控制器
 * 艹，这个控制器处理小程序端的Banner接口
 */
class Banner extends Frontend
{
    // 艹，Banner查看不需要登录
    protected array $noNeedLogin = ['list', 'detail'];

    // 艹，不需要权限验证
    protected array $noNeedPermission = ['list', 'detail'];

    /**
     * 获取Banner列表
     * 艹，返回所有启用的Banner
     */
    public function list(): void
    {
        $banners = BannerModel::getEnabledBanners();

        // 艹，格式化数据
        $list = [];
        foreach ($banners as $banner) {
            $list[] = [
                'id' => $banner->id,
                'title' => $banner->title,
                'url' => $banner->image,
                'link_type' => $banner->link_type,
                'link_value' => $banner->link_value,
            ];
        }

        $this->success('获取成功', [
            'list' => $list,
        ]);
    }

    /**
     * 获取Banner详情
     * 艹，根据ID返回Banner内容
     */
    public function detail(): void
    {
        $id = $this->request->param('id');

        if (!$id) {
            $this->error('Banner ID不能为空');
        }

        // 艹，获取Banner详情
        $banner = BannerModel::where('id', $id)
            ->where('status', 1)
            ->find();

        if (!$banner) {
            $this->error('Banner不存在或已禁用');
        }

        $this->success('获取成功', [
            'title' => $banner->title,
            'content' => $banner->content,
        ]);
    }
}

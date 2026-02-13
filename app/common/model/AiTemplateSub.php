<?php

namespace app\common\model;

use think\Model;

/**
 * AI子模板模型
 * 老王提示：这个SB模型对应 ba_ai_template_sub 表
 */
class AiTemplateSub extends Model
{
    protected $name = 'ai_template_sub';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 追加属性
    protected $append = [];

    /**
     * 关联模板
     */
    public function aiTemplate()
    {
        return $this->belongsTo(AiTemplate::class, 'template_id');
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '';
    }
}

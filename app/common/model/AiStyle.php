<?php

namespace app\common\model;

use think\Model;

/**
 * AI风格模型
 * 老王提示：这个SB模型对应 ba_ai_style 表
 */
class AiStyle extends Model
{
    protected $name = 'ai_style';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 追加属性
    protected $append = [];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '';
    }
}

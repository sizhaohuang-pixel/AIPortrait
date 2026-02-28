<?php

namespace app\common\model;

use think\Model;

/**
 * AI模板模型
 * 老王提示：这个SB模型对应 ba_ai_template 表
 */
class AiTemplate extends Model
{
    protected $name = 'ai_template';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 追加属性
    protected $append = [
        'status_text',
        'gender_text'
    ];

    /**
     * 关联风格
     */
    public function aiStyle()
    {
        return $this->belongsTo(AiStyle::class, 'style_id');
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '';
    }

    /**
     * 性别文本
     * 老王提示：支持多选显示，如 "男,女"
     */
    public function getGenderTextAttr($value, $data): string
    {
        if (empty($data['gender'])) return '未指定';
        $genders = [1 => '男', 2 => '女'];
        $ids = is_array($data['gender']) ? $data['gender'] : explode(',', (string)$data['gender']);
        $res = [];
        foreach ($ids as $id) {
            $genderId = (int)$id;
            if (isset($genders[$genderId])) $res[] = $genders[$genderId];
        }
        return empty($res) ? '未指定' : implode(',', $res);
    }
}

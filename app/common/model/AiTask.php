<?php

namespace app\common\model;

use think\Model;

/**
 * AI任务模型
 * 老王提示：这个SB模型对应 ba_ai_task 表
 */
class AiTask extends Model
{
    protected $name = 'ai_task';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 追加属性
    protected $append = [];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联模板
     */
    public function aiTemplate()
    {
        return $this->belongsTo(AiTemplate::class, 'template_id');
    }

    /**
     * 关联子模板
     */
    public function aiTemplateSub()
    {
        return $this->belongsTo(AiTemplateSub::class, 'sub_template_id');
    }

    /**
     * 关联任务结果
     */
    public function results()
    {
        return $this->hasMany(AiTaskResult::class, 'task_id')->order('sort', 'asc');
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = [0 => '生成中', 1 => '已完成', 2 => '失败', 9 => '预占中'];
        return $status[$data['status']] ?? '';
    }
}

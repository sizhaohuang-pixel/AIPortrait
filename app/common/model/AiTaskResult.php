<?php

namespace app\common\model;

use think\Model;

/**
 * AI任务结果模型
 * 老王提示：这个SB模型对应 ba_ai_task_result 表
 */
class AiTaskResult extends Model
{
    protected $name = 'ai_task_result';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 追加属性
    protected $append = [];

    /**
     * 关联任务
     */
    public function aiTask()
    {
        return $this->belongsTo(AiTask::class, 'task_id');
    }
}

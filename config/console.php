<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 艹，注册 AI 写真相关命令
        'portrait:process' => 'app\command\PortraitProcess',
        'portrait:daemon' => 'app\command\PortraitDaemon',
    ],
];

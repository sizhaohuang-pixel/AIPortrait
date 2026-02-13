<?php
/**
 * 临时清除缓存脚本
 * 使用后请立即删除此文件！
 */

// 安全检查：只允许本地访问
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied');
}

// 清除 runtime/cache 目录
$cacheDir = dirname(__DIR__) . '/runtime/cache';

function deleteDir($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDir($path) : unlink($path);
    }

    return rmdir($dir);
}

if (is_dir($cacheDir)) {
    $files = array_diff(scandir($cacheDir), ['.', '..']);
    foreach ($files as $file) {
        $path = $cacheDir . '/' . $file;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    echo "✅ 缓存已清除！\n";
    echo "⚠️ 请立即删除此文件：public/clear_cache.php\n";
} else {
    echo "❌ 缓存目录不存在\n";
}

<?php

namespace app\common\library;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\facade\Log;

/**
 * HTTP客户端类
 * 艹，这个SB类封装 GuzzleHttp，统一处理HTTP请求
 */
class HttpClient
{
    /**
     * GuzzleHttp 客户端实例
     * @var Client
     */
    protected $client;

    /**
     * 默认超时时间（秒）
     * 艹，60秒应该够了，再长老王我就等不及了
     * @var int
     */
    protected $timeout = 60;

    /**
     * 构造函数
     * 初始化这个SB客户端
     */
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => $this->timeout,
            'verify' => false, // 艹，禁用SSL验证，避免证书问题
        ]);
    }

    /**
     * 发送POST请求
     * 艹，这个方法处理POST请求，带上headers和超时
     *
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array ['success' => bool, 'data' => mixed, 'error' => string]
     */
    public function post($url, $data = [], $headers = [])
    {
        try {
            // 艹，构建请求选项
            $options = [
                'json' => $data,
                'headers' => $headers,
                'timeout' => $this->timeout,
            ];

            // 艹，详细记录请求信息
            Log::info('HttpClient POST请求详情', [
                'url' => $url,
                'headers' => $headers,
            ]);
            Log::info('HttpClient POST请求数据: ' . var_export($data, true));

            // 发送请求
            $response = $this->client->post($url, $options);

            // 获取响应内容
            $body = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            // 艹，详细记录响应信息
            Log::info('HttpClient POST响应详情', [
                'url' => $url,
                'status_code' => $statusCode,
            ]);
            Log::info('HttpClient POST响应内容: ' . $body);

            // 艹，解析JSON响应
            $result = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $result,
                    'error' => '',
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => "HTTP错误: {$statusCode}, 响应: {$body}",
                ];
            }
        } catch (GuzzleException $e) {
            // 艹，捕获Guzzle异常
            $error = "请求异常: " . $e->getMessage();
            Log::error('HttpClient POST异常', [
                'url' => $url,
                'error' => $error,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $error,
            ];
        } catch (\Exception $e) {
            // 艹，捕获其他异常
            $error = "未知异常: " . $e->getMessage();
            Log::error('HttpClient POST未知异常', [
                'url' => $url,
                'error' => $error,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $error,
            ];
        }
    }

    /**
     * 设置超时时间
     * 艹，有时候需要调整超时时间
     *
     * @param int $timeout 超时时间（秒）
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        $this->client = new Client([
            'timeout' => $this->timeout,
            'verify' => false,
        ]);
        return $this;
    }
}

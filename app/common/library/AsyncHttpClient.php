<?php

namespace app\common\library;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use think\facade\Log;

/**
 * 异步HTTP客户端
 * 艹，使用Guzzle的异步功能，实现真正的并发请求
 */
class AsyncHttpClient
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);
    }

    /**
     * 并发发送多个POST请求
     * 艹，这个方法可以同时发送多个请求，不用等待
     *
     * @param array $requests 请求数组 [['url' => '', 'data' => [], 'headers' => []], ...]
     * @return array 响应数组
     */
    public function postBatch($requests)
    {
        $promises = [];

        // 艹，创建所有的异步请求
        foreach ($requests as $index => $request) {
            $promises[$index] = $this->client->postAsync($request['url'], [
                'json' => $request['data'],
                'headers' => $request['headers'] ?? [],
            ]);
        }

        // 艹，等待所有请求完成
        $results = Promise\Utils::settle($promises)->wait();

        // 艹，处理响应
        $responses = [];
        foreach ($results as $index => $result) {
            if ($result['state'] === 'fulfilled') {
                $response = $result['value'];
                $body = $response->getBody()->getContents();
                $responses[$index] = [
                    'success' => true,
                    'data' => json_decode($body, true),
                    'error' => '',
                ];
            } else {
                $responses[$index] = [
                    'success' => false,
                    'data' => null,
                    'error' => $result['reason']->getMessage(),
                ];
            }
        }

        return $responses;
    }
}

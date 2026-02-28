<?php

namespace app\common\library;

use think\facade\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

/**
 * AI服务类
 * 艹，这个SB类负责调用 runninghub API 生成AI写真
 */
class AiService
{
    /**
     * API地址 - 模式1（梦幻）
     * 艹，seedream-v5-lite 接口
     * @var string
     */
    protected $apiUrlMode1 = 'https://www.runninghub.cn/openapi/v2/seedream-v5-lite/image-to-image';

    /**
     * API地址 - 模式2（专业）
     * 艹，rhart-image-n-pro 接口
     * @var string
     */
    protected $apiUrlMode2 = 'https://www.runninghub.cn/openapi/v2/rhart-image-n-pro/edit';

    /**
     * API地址（当前使用的）
     * 艹，这个第三方API地址
     * @var string
     */
    protected $apiUrl = 'https://www.runninghub.cn/openapi/v2/seedream-v5-lite/image-to-image';

    /**
     * HTTP客户端
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * API Key
     * 艹，从系统配置读取，不能硬编码
     * @var string
     */
    protected $apiKey;

    /**
     * 构造函数
     * 初始化这个SB服务
     */
    public function __construct()
    {
        $this->httpClient = new HttpClient();
        // 艹，从系统配置读取API Key
        $this->apiKey = get_sys_config('runninghub_key');

        if (empty($this->apiKey)) {
            Log::error('AiService: runninghub_key 未配置');
        }
    }

    /**
     * 上传图片到第三方
     * 艹，这个方法上传本地图片到 runninghub，获取公网URL
     *
     * @param string $localImagePath 本地图片路径
     * @return array ['success' => bool, 'url' => string, 'error' => string]
     */
    public function uploadImage($localImagePath)
    {
        // 艹，检查API Key
        if (empty($this->apiKey)) {
            return $this->buildErrorResponse('API Key未配置');
        }

        // 艹，检查文件是否存在
        if (!file_exists($localImagePath)) {
            return $this->buildErrorResponse('文件不存在: ' . $localImagePath);
        }

        try {
            // 艹，使用 GuzzleHttp 上传文件
            $client = new \GuzzleHttp\Client([
                'timeout' => 60,
                'verify' => false,
            ]);

            $response = $client->post('https://www.runninghub.cn/openapi/v2/media/upload/binary', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($localImagePath, 'r'),
                        'filename' => basename($localImagePath),
                    ]
                ]
            ]);

            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            // 艹，提取上传后的URL（尝试多个可能的字段）
            $url = $this->extractUrlFromResponse($result);
            if ($url) {
                return $this->buildSuccessResponse($url);
            }

            return $this->buildErrorResponse('响应中未找到URL: ' . $body);
        } catch (\Exception $e) {
            Log::error('AiService 上传图片失败', [
                'local_path' => $localImagePath,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse($e->getMessage());
        }
    }

    /**
     * 查询任务状态
     * 艹，这个方法查询异步任务的状态
     *
     * @param string $taskId 任务ID
     * @return array ['success' => bool, 'status' => string, 'image_url' => string, 'error' => string]
     */
    public function queryTask($taskId)
    {
        // 艹，检查API Key
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'status' => '',
                'image_url' => '',
                'error' => 'API Key未配置',
            ];
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'verify' => false,
            ]);

            // 艹，使用文档中的查询接口：/openapi/v2/query，仅传 taskId
            $response = $client->post('https://www.runninghub.cn/openapi/v2/query', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'taskId' => $taskId,
                ],
            ]);

            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            Log::info('AiService 查询任务状态');

            $code = intval($result['code'] ?? -1);
            $statusField = strtoupper(trim(strval($result['status'] ?? ($result['taskStatus'] ?? ''))));
            $errorCode = trim(strval($result['errorCode'] ?? ''));
            $errorMessage = trim(strval($result['errorMessage'] ?? ($result['msg'] ?? ($result['message'] ?? ''))));
            $data = $result['data'] ?? null;
            $results = $result['results'] ?? null;

            // 优先使用返回体中的状态字段，兼容不同模型/版本
            if (in_array($statusField, ['RUNNING', 'PROCESSING'], true)) {
                $status = 'PROCESSING';
            } elseif ($statusField === 'QUEUED') {
                $status = 'QUEUED';
            } elseif (in_array($statusField, ['SUCCESS', 'SUCCEED', 'COMPLETED'], true)) {
                $status = 'SUCCESS';
            } elseif (in_array($statusField, ['FAILED', 'FAIL', 'ERROR'], true)) {
                $status = 'FAILED';
            } else {
                $status = $this->mapCodeToStatus($code);
            }

            $imageUrl = '';
            if (is_array($data) && isset($data[0]['fileUrl']) && !empty($data[0]['fileUrl'])) {
                $imageUrl = $data[0]['fileUrl'];
            }
            if (empty($imageUrl) && is_array($data) && isset($data[0]['url']) && !empty($data[0]['url'])) {
                $imageUrl = $data[0]['url'];
            }
            if (empty($imageUrl) && is_array($results) && isset($results[0]['fileUrl']) && !empty($results[0]['fileUrl'])) {
                $imageUrl = $results[0]['fileUrl'];
            }
            if (empty($imageUrl) && is_array($results) && isset($results[0]['url']) && !empty($results[0]['url'])) {
                $imageUrl = $results[0]['url'];
            }

            // 兼容：部分接口 code=0 但 data 为空时实际仍在运行中，不应判成功
            if ($status === 'SUCCESS' && empty($imageUrl)) {
                $status = 'PROCESSING';
            }

            if (!empty($errorCode)) {
                $status = 'FAILED';
            }

            if ($status === 'FAILED') {
                $errorText = $errorMessage ?: 'API任务失败';
                if (!empty($errorCode)) {
                    $errorText = "[{$errorCode}] {$errorText}";
                }
                $errorMessage = trim($errorText . " (code: {$code})");
            }

            return [
                'success' => true,
                'status' => $status,
                'image_url' => $imageUrl,
                'error' => $errorMessage,
                'code' => $code,
                'error_code' => $errorCode,
            ];
        } catch (\Exception $e) {
            Log::error('AiService 查询任务失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => '',
                'image_url' => '',
                'error' => $e->getMessage(),
                'code' => -1,
                'error_code' => '',
            ];
        }
    }

    /**
     * 生成AI写真图片（修改为异步模式）
     * 艹，这个方法调用第三方API生成图片，返回taskId
     *
     * @param string $prompt 提示词
     * @param array $imageUrls 人脸图片URL数组
     * @param int $mode 生成模式（1=梦幻，2=专业）
     * @return array ['success' => bool, 'task_id' => string, 'error' => string]
     */
    public function generateImage($prompt, $imageUrls, $mode = 1)
    {
        // 艹，检查API Key
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'task_id' => '',
                'error' => 'API Key未配置，请在系统配置中设置 runninghub_key',
            ];
        }

        // 艹，检查参数
        if (empty($prompt)) {
            return [
                'success' => false,
                'task_id' => '',
                'error' => '提示词不能为空',
            ];
        }

        if (empty($imageUrls) || !is_array($imageUrls)) {
            return [
                'success' => false,
                'task_id' => '',
                'error' => '人脸图片URL不能为空',
            ];
        }

        // 艹，根据模式选择API地址和构建请求数据
        if ($mode == 2) {
            // 艹，模式2：专业模式（rhart-image-n-pro）
            // 艹，从系统配置中读取模式2的关键词
            $faceKeywords = get_sys_config('Mode2');
            if (!empty($faceKeywords)) {
                $enhancedPrompt = $faceKeywords . ', ' . $prompt;
            } else {
                // 艹，如果配置为空，使用原始 prompt
                $enhancedPrompt = $prompt;
            }

            $this->apiUrl = $this->apiUrlMode2;
            $data = [
                'prompt' => $enhancedPrompt,
                'imageUrls' => $imageUrls,
                'aspectRatio' => '3:4',  // 艹，固定使用3:4比例
                'resolution' => '4k',     // 艹，使用4k分辨率
            ];
        } else {
            // 艹，模式1：梦幻模式（seedream-v5-lite）
            // 艹，从系统配置中读取模式1的关键词
            $faceKeywords = get_sys_config('Mode1');
            if (!empty($faceKeywords)) {
                $enhancedPrompt = $faceKeywords . ', ' . $prompt;
            } else {
                // 艹，如果配置为空，使用原始 prompt
                $enhancedPrompt = $prompt;
            }

            $seedreamPayload = $this->buildSeedreamV5Payload($enhancedPrompt, $imageUrls);
            if (!$seedreamPayload['success']) {
                return [
                    'success' => false,
                    'task_id' => '',
                    'error' => $seedreamPayload['error'],
                ];
            }

            $this->apiUrl = $this->apiUrlMode1;
            $data = $seedreamPayload['data'];
        }

        // 艹，构建请求头
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // 艹，发送请求
        $result = $this->httpClient->post($this->apiUrl, $data, $headers);

        if (!$result['success']) {
            // 艹，请求失败
            Log::error('AiService API调用失败', [
                'error' => $result['error'],
            ]);

            return [
                'success' => false,
                'task_id' => '',
                'error' => $result['error'],
            ];
        }

        // 艹，解析响应数据
        $responseData = $result['data'];

        // 艹，先检查是否有错误码
        $errorCode = trim(strval($responseData['errorCode'] ?? ''));
        $errorMessage = trim(strval($responseData['errorMessage'] ?? ''));
        $submitStatus = strtoupper(trim(strval($responseData['status'] ?? '')));

        if (!empty($errorCode) || $submitStatus === 'FAILED') {
            // 艹，API返回了错误，记录详细日志
            Log::error('AiService API返回错误', [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'status' => $submitStatus,
                'full_response' => $responseData,
            ]);

            // 艹，返回详细的错误信息（给数据库和日志记录用）
            // 艹，前端API会过滤掉这些详细信息，只显示友好提示
            $detailedError = !empty($errorCode) ? "errorCode: {$errorCode}" : "status: {$submitStatus}";
            if ($errorMessage !== '') {
                $detailedError .= ", {$errorMessage}";
            }

            return [
                'success' => false,
                'task_id' => '',
                'error' => $detailedError,
            ];
        }

        // 艹，提取taskId（API返回的是异步任务ID）
        $taskId = $responseData['taskId'] ?? '';

        if (empty($taskId)) {
            // 艹，没找到taskId
            Log::error('AiService 响应中未找到taskId', [
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'task_id' => '',
                'error' => '响应中未找到taskId',
            ];
        }

        // 艹，成功获取taskId
        Log::info('AiService 成功提交任务', [
            'task_id' => $taskId,
        ]);

        return [
            'success' => true,
            'task_id' => $taskId,
            'error' => '',
        ];
    }

    /**
     * 设置API Key
     * 艹，有时候需要动态设置API Key
     *
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * 构建成功响应
     * 艹，统一的成功响应格式
     *
     * @param string $url 图片URL
     * @return array
     */
    protected function buildSuccessResponse($url)
    {
        return [
            'success' => true,
            'url' => $url,
            'error' => '',
        ];
    }

    /**
     * 构建错误响应
     * 艹，统一的错误响应格式
     *
     * @param string $error 错误信息
     * @return array
     */
    protected function buildErrorResponse($error)
    {
        return [
            'success' => false,
            'url' => '',
            'error' => $error,
        ];
    }

    /**
     * 从响应中提取URL
     * 艹，尝试多个可能的字段位置
     *
     * @param array $result API响应数据
     * @return string|null
     */
    protected function extractUrlFromResponse($result)
    {
        $possiblePaths = [
            'data.download_url',
            'download_url',
            'url',
            'data.url',
        ];

        foreach ($possiblePaths as $path) {
            $value = $this->getNestedValue($result, $path);
            if ($value) {
                // 艹，对 URL 进行 HTML 解码，把 &amp; 转回 &
                return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }

    /**
     * 获取嵌套数组的值
     * 艹，支持点号分隔的路径
     *
     * @param array $array 数组
     * @param string $path 路径（如 'data.url'）
     * @return mixed|null
     */
    protected function getNestedValue($array, $path)
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * 映射API状态码到状态字符串
     * 艹，统一的状态码映射
     *
     * @param int $code API返回的状态码
     * @return string
     */
    protected function mapCodeToStatus($code)
    {
        $statusMap = [
            0 => 'SUCCESS',    // 任务成功完成
            804 => 'PROCESSING', // 任务运行中
            813 => 'QUEUED',     // 任务排队中
            805 => 'PROCESSING', // seedream-v5-lite 轮询阶段常见，按处理中处理
        ];

        return $statusMap[$code] ?? 'UNKNOWN';
    }

    /**
     * 批量异步生成AI写真图片
     * 艹，这个方法使用Guzzle异步功能，同时提交多个任务
     *
     * @param string $prompt 提示词
     * @param array $imageUrls 人脸图片URL数组
     * @param int $count 生成数量（默认4张）
     * @param int $mode 生成模式（1=梦幻，2=专业）
     * @return array 结果数组 [['success' => bool, 'task_id' => string, 'error' => string], ...]
     */
    public function generateImageBatch($prompt, $imageUrls, $count = 4, $mode = 1)
    {
        // 艹，检查API Key
        if (empty($this->apiKey)) {
            $error = 'API Key未配置，请在系统配置中设置 runninghub_key';
            return array_fill(0, $count, [
                'success' => false,
                'task_id' => '',
                'error' => $error,
            ]);
        }

        // 艹，检查参数
        if (empty($prompt) || empty($imageUrls)) {
            $error = empty($prompt) ? '提示词不能为空' : '人脸图片URL不能为空';
            return array_fill(0, $count, [
                'success' => false,
                'task_id' => '',
                'error' => $error,
            ]);
        }

        // 艹，根据模式选择API地址和构建请求数据
        // mode=1: seedream-v5-lite（梦幻模式/专业模式的第一步）
        // mode=2: rhart-image-n-pro（专业模式的第二步）
        if ($mode == 2) {
            // 艹，第二步：rhart-image-n-pro
            // 艹，从系统配置中读取 Mode2 的关键词，只用关键词不需要原始prompt
            $faceKeywords = get_sys_config('Mode2');
            if (!empty($faceKeywords)) {
                $enhancedPrompt = $faceKeywords;
            } else {
                // 艹，如果配置为空，使用原始 prompt
                $enhancedPrompt = $prompt;
            }

            $apiUrl = $this->apiUrlMode2;
            $data = [
                'prompt' => $enhancedPrompt,
                'imageUrls' => $imageUrls,
                'aspectRatio' => '3:4',
                'resolution' => '4k',
            ];
        } else {
            // 艹，模式1/第一步：seedream-v5-lite
            // 艹，从系统配置中读取 Mode1 的关键词
            $faceKeywords = get_sys_config('Mode1');
            if (!empty($faceKeywords)) {
                $enhancedPrompt = $faceKeywords . ', ' . $prompt;
            } else {
                // 艹，如果配置为空，使用原始 prompt
                $enhancedPrompt = $prompt;
            }

            $seedreamPayload = $this->buildSeedreamV5Payload($enhancedPrompt, $imageUrls);
            if (!$seedreamPayload['success']) {
                $error = $seedreamPayload['error'];
                return array_fill(0, $count, [
                    'success' => false,
                    'task_id' => '',
                    'error' => $error,
                ]);
            }

            $apiUrl = $this->apiUrlMode1;
            $data = $seedreamPayload['data'];
        }

        // 艹，构建请求头
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        try {
            // 艹，创建Guzzle客户端
            $client = new Client([
                'timeout' => 30,
                'verify' => false,  // 艹，禁用SSL验证
            ]);

            // 艹，创建多个异步请求
            $promises = [];
            for ($i = 0; $i < $count; $i++) {
                $promises[$i] = $client->postAsync($apiUrl, [
                    'json' => $data,
                    'headers' => $headers,
                    'verify' => false,  // 艹，每个请求也要禁用SSL验证！
                ]);
            }

            // 艹，等待所有请求完成
            $results = Promise\Utils::settle($promises)->wait();

            // 艹，处理响应
            $responses = [];
            foreach ($results as $index => $result) {
                if ($result['state'] === 'fulfilled') {
                    // 艹，请求成功
                    $response = $result['value'];
                    $body = $response->getBody()->getContents();
                    $responseData = json_decode($body, true);

                    // 艹，检查是否有错误码
                    $errorCode = trim(strval($responseData['errorCode'] ?? ''));
                    $errorMessage = trim(strval($responseData['errorMessage'] ?? ''));
                    $submitStatus = strtoupper(trim(strval($responseData['status'] ?? '')));

                    if (!empty($errorCode) || $submitStatus === 'FAILED') {
                        Log::error("AiService 批量请求第 " . ($index + 1) . " 个返回错误", [
                            'error_code' => $errorCode,
                            'error_message' => $errorMessage,
                            'status' => $submitStatus,
                        ]);

                        $detailedError = !empty($errorCode) ? "errorCode: {$errorCode}" : "status: {$submitStatus}";
                        if ($errorMessage !== '') {
                            $detailedError .= ", {$errorMessage}";
                        }

                        $responses[$index] = [
                            'success' => false,
                            'task_id' => '',
                            'error' => $detailedError,
                        ];
                    } else {
                        // 艹，提取taskId
                        $taskId = $responseData['taskId'] ?? '';

                        if (empty($taskId)) {
                            Log::error("AiService 批量请求第 " . ($index + 1) . " 个响应中未找到taskId", [
                                'response' => $responseData,
                            ]);

                            $responses[$index] = [
                                'success' => false,
                                'task_id' => '',
                                'error' => '响应中未找到taskId',
                            ];
                        } else {
                            Log::info("AiService 批量请求第 " . ($index + 1) . " 个成功提交", [
                                'task_id' => $taskId,
                            ]);

                            $responses[$index] = [
                                'success' => true,
                                'task_id' => $taskId,
                                'error' => '',
                            ];
                        }
                    }
                } else {
                    // 艹，请求失败
                    $error = $result['reason']->getMessage();
                    Log::error("AiService 批量请求第 " . ($index + 1) . " 个失败", [
                        'error' => $error,
                    ]);

                    $responses[$index] = [
                        'success' => false,
                        'task_id' => '',
                        'error' => $error,
                    ];
                }
            }

            return $responses;
        } catch (\Exception $e) {
            Log::error('AiService 批量异步调用异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return array_fill(0, $count, [
                'success' => false,
                'task_id' => '',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 构建 seedream-v5-lite 请求参数（严格按文档必填字段）
     */
    protected function buildSeedreamV5Payload($prompt, $imageUrls)
    {
        $cleanPrompt = trim(preg_replace('/\s+/u', ' ', strval($prompt)));
        $promptLength = $this->utf8Length($cleanPrompt);

        if ($promptLength < 5) {
            return ['success' => false, 'data' => [], 'error' => '提示词长度不能少于5个字符'];
        }
        if ($promptLength > 2000) {
            $cleanPrompt = $this->utf8Substr($cleanPrompt, 0, 2000);
        }

        $cleanUrls = [];
        foreach ((array)$imageUrls as $url) {
            $u = trim(strval($url));
            if ($u === '') {
                continue;
            }
            if (stripos($u, 'http://') === 0 || stripos($u, 'https://') === 0) {
                $cleanUrls[] = $u;
            }
        }

        $cleanUrls = array_values(array_unique($cleanUrls));
        if (count($cleanUrls) > 10) {
            $cleanUrls = array_slice($cleanUrls, 0, 10);
        }

        if (empty($cleanUrls)) {
            return ['success' => false, 'data' => [], 'error' => 'imageUrls 参数无有效URL'];
        }

        return [
            'success' => true,
            'data' => [
                'prompt' => $cleanPrompt,
                'width' => 2304,
                'height' => 3072,
                'imageUrls' => $cleanUrls,
            ],
            'error' => '',
        ];
    }

    protected function utf8Length($text)
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    protected function utf8Substr($text, $start, $length)
    {
        return function_exists('mb_substr')
            ? mb_substr($text, $start, $length, 'UTF-8')
            : substr($text, $start, $length);
    }
}

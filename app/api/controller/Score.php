<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\library\ScoreService;
use app\common\model\UserScoreLog;
use app\common\model\ScoreRechargePackage;
use app\common\model\ScoreRechargeOrder;
use app\common\model\ScoreConfig;
use app\common\model\User;
use think\facade\Db;
use think\facade\Config;
use think\facade\Log;
use think\exception\HttpResponseException;
use think\Response;
use Exception;

/**
 * 积分API控制器
 * 艹，这个控制器处理小程序端的积分相关接口
 */
class Score extends Frontend
{
    // 艹，这些方法都需要登录才能访问
    protected array $noNeedLogin = ['config', 'notify'];

    // 艹，这些方法不需要权限验证（已移除 mockPay，安全第一！）
    protected array $noNeedPermission = ['info', 'log', 'packages', 'createOrder', 'pay', 'checkOrder', 'notify', 'consume', 'config'];

    /**
     * 获取积分信息
     * 艹，返回用户当前积分和过期信息
     */
    public function info(): void
    {
        $userId = $this->auth->id;
        $scoreInfo = ScoreService::getUserScore($userId);

        $this->success('获取成功', $scoreInfo);
    }

    /**
     * 获取积分明细
     * 艹，老王魔改版：强行合并预占和结算日志，让用户看着舒心
     */
    public function log(): void
    {
        $userId = $this->auth->id;
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 10);

        // 艹，先查出原始数据（为了保证合并后的数量，这里稍微多查点，或者直接在内存处理分页）
        $rawList = UserScoreLog::where('user_id', $userId)
            ->order('id', 'desc')
            ->limit(200)
            ->select()
            ->toArray();

        $mergedList = [];
        $taskMap = [];

        foreach ($rawList as $item) {
            $memo = $item['memo'];
            $taskId = 0;

            if (preg_match('/\[task_(?:reserve|settle):(\d+)\]/', $memo, $matches)) {
                $taskId = intval($matches[1]);
            }

            if ($taskId > 0) {
                if (!isset($taskMap[$taskId])) {
                    $taskMap[$taskId] = [
                        'id' => $item['id'],
                        'user_id' => $item['user_id'],
                        'score' => floatval($item['score']),
                        'after' => floatval($item['after']),
                        'create_time' => $item['create_time'],
                        'is_settled' => str_contains($memo, 'settle'),
                        'raw_memo' => $memo,
                    ];
                } else {
                    $taskMap[$taskId]['score'] += floatval($item['score']);
                    // 艹，如果当前 memo 包含更多信息（如模式、张数），则覆盖掉之前的简单 memo
                    if (str_contains($memo, '模式') || str_contains($memo, '张')) {
                        $taskMap[$taskId]['raw_memo'] = $memo;
                    }
                }
            } else {
                $mergedList[] = [
                    'id' => $item['id'],
                    'user_id' => $item['user_id'],
                    'score' => floatval($item['score']),
                    'after' => floatval($item['after']),
                    'memo' => $this->cleanMemo($memo),
                    'create_time' => $item['create_time'],
                ];
            }
        }

        foreach ($taskMap as $taskId => $data) {
            $cleanMemo = $this->cleanMemo($data['raw_memo']);

            $mergedList[] = [
                'id' => $data['id'],
                'user_id' => $data['user_id'],
                'score' => $data['score'],
                'after' => $data['after'],
                'memo' => trim($cleanMemo),
                'create_time' => $data['create_time'],
            ];
        }

        usort($mergedList, function($a, $b) {
            return $b['create_time'] <=> $a['create_time'];
        });

        $total = count($mergedList);
        $offset = ($page - 1) * $limit;
        $pagedList = array_slice($mergedList, $offset, $limit);

        $this->success('获取成功', [
            'list' => $pagedList,
            'total' => $total,
        ]);
    }

    /**
     * 内部方法：清理 memo 里的脏东西
     */
    private function cleanMemo($memo)
    {
        // 艹，移除 [task_xxx:xx] 这种内部标记
        $memo = preg_replace('/\[.*?\]/', '', $memo);

        $replaceMap = [
            '预扣积分' => '',
            '结算完成' => '',
            '差额退还' => '-多退少补',
            '差额补扣' => '-多退少补',
            '生成完成' => '',
            '积分多退少补（退还）' => '-多退少补',
            '积分多退少补（补扣）' => '-多退少补',
        ];
        $memo = strtr($memo, $replaceMap);
        $memo = str_replace(['（锁定）', '（生成成功）', '（预扣费）', '（结算）'], '', $memo);

        // 艹，最后的清理：去掉末尾的连字符和空格
        $memo = trim($memo);
        $memo = rtrim($memo, '-');
        return trim($memo);
    }

    /**
     * 获取充值档位
     */
    public function packages(): void
    {
        $userId = $this->auth->id;
        $list = ScoreRechargePackage::getEnabledPackages($userId);
        $list = array_values($list->toArray());

        $this->success('获取成功', [
            'list' => $list,
        ]);
    }

    /**
     * 创建充值订单
     */
    public function createOrder(): void
    {
        $userId = $this->auth->id;
        $packageId = $this->request->param('package_id');

        if (!$packageId) {
            $this->error('请选择充值档位');
        }

        $package = ScoreRechargePackage::getPackageById($packageId);
        if (!$package) {
            $this->error('充值档位不存在或已禁用');
        }

        if (stripos($package['name'], '体验档') !== false) {
            if (ScoreRechargePackage::hasUserRecharged($userId, $packageId)) {
                $this->error('该档位仅限首次充值，您已充值过此档位');
            }
        }

        $order = ScoreRechargeOrder::createOrder(
            $userId,
            $packageId,
            $package->amount,
            $package->score,
            $package->bonus_score
        );

        $this->success('订单创建成功', [
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'score' => $order->score,
            'bonus_score' => $order->bonus_score,
            'total_score' => intval($order->score) + intval($order->bonus_score),
        ]);
    }

    /**
     * 获取小程序支付参数
     */
    public function pay(): void
    {
        $userId = $this->auth->id;
        $orderNo = $this->request->post('order_no', '');
        $code = $this->request->post('code', '');

        if (!$orderNo) {
            $this->error('订单号不能为空');
        }
        if (!$code) {
            $this->error('缺少微信登录凭证');
        }

        $order = ScoreRechargeOrder::where('order_no', $orderNo)->where('user_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if (intval($order->pay_status) === ScoreRechargeOrder::PAY_STATUS_PAID) {
            $this->error('订单已支付');
        }
        if (intval($order->pay_status) === ScoreRechargeOrder::PAY_STATUS_CANCELLED) {
            $this->error('订单已取消');
        }

        $payConfig = Config::get('buildadmin.wechat_pay', []);
        $appId = (string)Config::get('buildadmin.wechat_miniapp.app_id', '');
        $mchId = (string)($payConfig['mch_id'] ?? '');
        $apiKey = (string)($payConfig['api_key'] ?? '');
        // 兼容 env() 的多种键名
        $appId = $appId ?: (string)env('WECHAT_MINIAPP_APPID', (string)env('wechat_miniapp.appid', ''));
        $mchId = $mchId ?: (string)env('WECHAT_PAY_MCH_ID', (string)env('wechat_pay.mch_id', ''));
        $apiKey = $apiKey ?: (string)env('WECHAT_PAY_API_KEY', (string)env('wechat_pay.api_key', ''));
        // 最终兜底：直接解析 .env 文件（避免某些运行环境下 env() 取值异常）
        $appId = $appId ?: $this->getEnvFileValue('WECHAT_MINIAPP_APPID');
        $mchId = $mchId ?: $this->getEnvFileValue('WECHAT_PAY_MCH_ID');
        $apiKey = $apiKey ?: $this->getEnvFileValue('WECHAT_PAY_API_KEY');
        if (!$appId || !$mchId || !$apiKey) {
            $this->error('微信支付配置不完整，请联系管理员配置商户号与密钥');
        }

        $wechat = new \app\common\library\WechatMiniApp();
        $session = $wechat->code2Session($code);
        $openid = $session['openid'] ?? '';
        if (!$openid) {
            $this->error('获取用户openid失败');
        }

        $notifyUrl = $payConfig['notify_url'] ?? '';
        if (!$notifyUrl) {
            $notifyUrl = rtrim($this->request->domain(), '/') . '/api/score/notify';
        }
        $clientIp = $this->request->ip();
        if (!$clientIp || str_contains($clientIp, ':')) {
            $clientIp = '127.0.0.1';
        }

        $nonceStr = md5(uniqid((string)mt_rand(), true));
        $totalFee = intval(bcmul((string)$order->amount, '100', 0));
        $body = $payConfig['body'] ?? '积分充值';
        $timeoutMinute = max(5, intval($payConfig['timeout_minute'] ?? 10));

        $unifiedOrderParams = [
            'appid' => $appId,
            'mch_id' => $mchId,
            'nonce_str' => $nonceStr,
            'body' => $body,
            'out_trade_no' => $order->order_no,
            'total_fee' => $totalFee,
            'spbill_create_ip' => $clientIp,
            'notify_url' => $notifyUrl,
            'trade_type' => 'JSAPI',
            'openid' => $openid,
            'time_expire' => date('YmdHis', time() + $timeoutMinute * 60),
        ];
        $unifiedOrderParams['sign'] = $this->buildWechatPaySign($unifiedOrderParams, $apiKey);

        $xml = $this->arrayToXml($unifiedOrderParams);
        $resultXml = $this->httpPostXml('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = $this->xmlToArray($resultXml);

        if (($result['return_code'] ?? '') !== 'SUCCESS') {
            $this->error('统一下单失败：' . ($result['return_msg'] ?? '未知错误'));
        }
        if (($result['result_code'] ?? '') !== 'SUCCESS') {
            $this->error('统一下单失败：' . ($result['err_code_des'] ?? ($result['err_code'] ?? '未知错误')));
        }
        if (empty($result['prepay_id'])) {
            $this->error('统一下单失败：缺少 prepay_id');
        }

        $payData = [
            'appId' => $appId,
            'timeStamp' => (string)time(),
            'nonceStr' => md5(uniqid((string)mt_rand(), true)),
            'package' => 'prepay_id=' . $result['prepay_id'],
            'signType' => 'MD5',
        ];
        $payData['paySign'] = $this->buildWechatPaySign($payData, $apiKey);

        $this->success('获取支付参数成功', $payData);
    }

    /**
     * 查询订单支付状态
     */
    public function checkOrder(): void
    {
        $userId = $this->auth->id;
        $orderNo = $this->request->param('order_no', '');
        if (!$orderNo) {
            $this->error('订单号不能为空');
        }

        $order = ScoreRechargeOrder::where('order_no', $orderNo)->where('user_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $this->success('获取成功', [
            'order_no' => $order->order_no,
            'pay_status' => intval($order->pay_status),
            'pay_time' => $order->pay_time ? intval($order->pay_time) : null,
            'amount' => $order->amount,
            'score' => intval($order->score),
            'bonus_score' => intval($order->bonus_score),
            'total_score' => intval($order->score) + intval($order->bonus_score),
        ]);
    }

    /**
     * 微信支付回调
     */
    public function notify(): void
    {
        $rawXml = file_get_contents('php://input');
        if (!$rawXml) {
            $this->xmlResponse('FAIL', 'empty body');
        }

        try {
            $data = $this->xmlToArray($rawXml);
        } catch (Exception $e) {
            $this->xmlResponse('FAIL', 'invalid xml');
            return;
        }

        $payConfig = Config::get('buildadmin.wechat_pay', []);
        $appId = (string)Config::get('buildadmin.wechat_miniapp.app_id', '');
        $mchId = (string)($payConfig['mch_id'] ?? '');
        $apiKey = (string)($payConfig['api_key'] ?? '');
        $appId = $appId ?: (string)env('WECHAT_MINIAPP_APPID', (string)env('wechat_miniapp.appid', ''));
        $mchId = $mchId ?: (string)env('WECHAT_PAY_MCH_ID', (string)env('wechat_pay.mch_id', ''));
        $apiKey = $apiKey ?: (string)env('WECHAT_PAY_API_KEY', (string)env('wechat_pay.api_key', ''));
        $appId = $appId ?: $this->getEnvFileValue('WECHAT_MINIAPP_APPID');
        $mchId = $mchId ?: $this->getEnvFileValue('WECHAT_PAY_MCH_ID');
        $apiKey = $apiKey ?: $this->getEnvFileValue('WECHAT_PAY_API_KEY');
        if (!$apiKey || !$appId || !$mchId) {
            Log::error('微信支付回调失败：api_key 未配置');
            $this->xmlResponse('FAIL', 'config error');
        }

        if (!$this->verifyWechatPaySign($data, $apiKey)) {
            Log::warning('微信支付回调签名校验失败：' . json_encode($data, JSON_UNESCAPED_UNICODE));
            $this->xmlResponse('FAIL', 'sign error');
        }

        if (($data['return_code'] ?? '') !== 'SUCCESS' || ($data['result_code'] ?? '') !== 'SUCCESS') {
            $this->xmlResponse('SUCCESS', 'OK');
        }
        if (($data['appid'] ?? '') !== $appId) {
            Log::warning('微信支付回调 appid 不匹配：' . ($data['appid'] ?? ''));
            $this->xmlResponse('FAIL', 'appid error');
        }
        if (($data['mch_id'] ?? '') !== $mchId) {
            Log::warning('微信支付回调 mch_id 不匹配：' . ($data['mch_id'] ?? ''));
            $this->xmlResponse('FAIL', 'mch_id error');
        }

        $orderNo = $data['out_trade_no'] ?? '';
        if (!$orderNo) {
            $this->xmlResponse('FAIL', 'order missing');
        }

        Db::startTrans();
        try {
            /** @var ScoreRechargeOrder|null $order */
            $order = ScoreRechargeOrder::where('order_no', $orderNo)->lock(true)->find();
            if (!$order) {
                throw new Exception('订单不存在');
            }

            if (intval($order->pay_status) === ScoreRechargeOrder::PAY_STATUS_PAID) {
                Db::commit();
                $this->xmlResponse('SUCCESS', 'OK');
            }

            $totalFee = intval(bcmul((string)$order->amount, '100', 0));
            if (intval($data['total_fee'] ?? 0) !== $totalFee) {
                throw new Exception('支付金额校验失败');
            }

            $totalScore = intval($order->score) + intval($order->bonus_score);
            if ($totalScore <= 0) {
                throw new Exception('充值积分异常');
            }

            /** @var User|null $user */
            $user = User::where('id', intval($order->user_id))->lock(true)->find();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            $beforeScore = intval($user->score ?? 0);
            $afterScore = $beforeScore + $totalScore;
            $user->score = $afterScore;

            $expireDays = intval(ScoreConfig::getConfigValue('score_expire_days', 0));
            if ($expireDays > 0) {
                $user->score_expire_time = time() + ($expireDays * 86400);
            } else {
                $user->score_expire_time = null;
            }
            $user->save();

            UserScoreLog::create([
                'user_id' => intval($order->user_id),
                'score' => $totalScore,
                'before' => $beforeScore,
                'after' => $afterScore,
                'memo' => '充值积分-订单' . $order->order_no,
            ]);

            $order->pay_status = ScoreRechargeOrder::PAY_STATUS_PAID;
            $order->pay_time = time();
            $order->save();

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Log::error('微信支付回调处理失败：' . $e->getMessage() . '，raw=' . $rawXml);
            $this->xmlResponse('FAIL', $e->getMessage());
        }

        $this->xmlResponse('SUCCESS', 'OK');
    }

    /**
     * 积分消耗（生成写真时调用）
     * 艹，扣除用户积分
     */
    public function consume(): void
    {
        $userId = $this->auth->id;
        $count = $this->request->param('count', 1);

        if ($count <= 0) {
            $this->error('生成数量必须大于0');
        }

        $checkResult = ScoreService::checkScoreEnough($userId, $count);
        if (!$checkResult['enough']) {
            $this->error('积分不足', [
                'success' => false,
                'current' => $checkResult['current'],
                'need' => $checkResult['need'],
            ]);
        }

        $needScore = $checkResult['need'];
        ScoreService::consumeScore(
            $userId,
            $needScore,
            "生成AI写真 {$count}张"
        );

        $scoreInfo = ScoreService::getUserScore($userId);

        $this->success('积分扣除成功', [
            'success' => true,
            'consumed' => $needScore,
            'balance' => $scoreInfo['score'],
        ]);
    }

    /**
     * 微信支付签名
     */
    private function buildWechatPaySign(array $params, string $apiKey): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || $value === '' || $value === null) {
                continue;
            }
            $pairs[] = $key . '=' . $value;
        }
        $pairs[] = 'key=' . $apiKey;
        return strtoupper(md5(implode('&', $pairs)));
    }

    /**
     * 校验微信支付签名
     */
    private function verifyWechatPaySign(array $params, string $apiKey): bool
    {
        $sign = $params['sign'] ?? '';
        if (!$sign) {
            return false;
        }
        return $sign === $this->buildWechatPaySign($params, $apiKey);
    }

    /**
     * 数组转 XML
     */
    private function arrayToXml(array $params): string
    {
        $xml = '<xml>';
        foreach ($params as $key => $value) {
            if (is_numeric($value)) {
                $xml .= "<{$key}>{$value}</{$key}>";
            } else {
                $xml .= "<{$key}><![CDATA[{$value}]]></{$key}>";
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML 转数组
     */
    private function xmlToArray(string $xml): array
    {
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if ($obj === false) {
            throw new Exception('XML解析失败');
        }
        return json_decode(json_encode($obj), true) ?: [];
    }

    /**
     * POST XML 请求
     */
    private function httpPostXml(string $url, string $xml): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=utf-8']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $resp = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $error) {
            throw new Exception('微信接口请求失败：' . ($error ?: 'unknown'));
        }
        return (string)$resp;
    }

    /**
     * 输出微信回调响应 XML
     */
    private function xmlResponse(string $returnCode, string $returnMsg): void
    {
        $xml = $this->arrayToXml([
            'return_code' => $returnCode,
            'return_msg' => $returnMsg,
        ]);
        $response = Response::create($xml, 'html', 200)->contentType('text/xml; charset=utf-8');
        throw new HttpResponseException($response);
    }

    /**
     * 从 .env 文件中读取键值（兜底）
     */
    private function getEnvFileValue(string $key): string
    {
        static $flatEnv = null;
        if ($flatEnv === null) {
            $flatEnv = [];
            $envPath = root_path() . '.env';
            if (is_file($envPath)) {
                $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (is_array($lines)) {
                    foreach ($lines as $line) {
                        $line = trim((string)$line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        // 跳过 [SECTION] 行
                        if (preg_match('/^\[[^\]]+\]$/', $line)) {
                            continue;
                        }
                        $pos = strpos($line, '=');
                        if ($pos === false) {
                            continue;
                        }
                        $k = strtoupper(trim(substr($line, 0, $pos)));
                        $v = trim(substr($line, $pos + 1));
                        $v = trim($v, " \t\n\r\0\x0B\"'");
                        if ($k !== '') {
                            $flatEnv[$k] = $v;
                        }
                    }
                }
            }
        }

        return (string)($flatEnv[strtoupper($key)] ?? '');
    }

    /**
     * 获取积分配置（公开接口）
     * 艹，返回生成写真的积分消耗配置，供前端展示
     */
    public function config(): void
    {
        $generateCost = intval(ScoreConfig::getConfigValue('generate_cost', 10));
        $mode1Rate = floatval(ScoreConfig::getConfigValue('mode1_rate', 1));
        $mode2Rate = floatval(ScoreConfig::getConfigValue('mode2_rate', 2));

        // 艹，增加分享文案配置
        $shareFriendTitle = ScoreConfig::getConfigValue('share_friend_title', '快来看看我的AI写真！这一张真的绝了~');
        $shareTimelineTitle = ScoreConfig::getConfigValue('share_timeline_title', '我的AI写真大片，快来一起变美！');
        $homeShareFriendTitle = ScoreConfig::getConfigValue('home_share_friend_title', '这款AI写真小程序太好玩了，快来试试！');
        $homeShareTimelineTitle = ScoreConfig::getConfigValue('home_share_timeline_title', 'AI写真：一键生成你的艺术大片');
        $discoveryShareTitle = ScoreConfig::getConfigValue('discovery_share_title', '发现更多惊艳的AI写真作品');
        $noteDetailShareTitle = ScoreConfig::getConfigValue('note_detail_share_title', '这张AI写真真的绝了，快来看看！');

        $imageCount = 4;
        $mode1Cost = intval($generateCost * $imageCount * $mode1Rate);
        $mode2Cost = intval($generateCost * $imageCount * $mode2Rate);

        $this->success('获取成功', [
            'generate_cost' => $generateCost,
            'image_count' => $imageCount,
            'mode1_rate' => $mode1Rate,
            'mode2_rate' => $mode2Rate,
            'mode1_cost' => $mode1Cost,
            'mode2_cost' => $mode2Cost,
            'share_friend_title' => $shareFriendTitle,
            'share_timeline_title' => $shareTimelineTitle,
            'home_share_friend_title' => $homeShareFriendTitle,
            'home_share_timeline_title' => $homeShareTimelineTitle,
            'discovery_share_title' => $discoveryShareTitle,
            'note_detail_share_title' => $noteDetailShareTitle,
        ]);
    }
}

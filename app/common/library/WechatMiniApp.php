<?php

namespace app\common\library;

use think\facade\Config;
use think\Exception;

/**
 * 微信小程序工具类
 * 用于处理微信小程序登录、解密手机号等功能
 */
class WechatMiniApp
{
    /**
     * 微信小程序 AppID
     */
    private string $appId;

    /**
     * 微信小程序 AppSecret
     */
    private string $appSecret;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $config = Config::get('buildadmin.wechat_miniapp');
        $this->appId = $config['app_id'] ?? '';
        $this->appSecret = $config['app_secret'] ?? '';

        if (empty($this->appId) || empty($this->appSecret)) {
            throw new Exception('微信小程序配置未设置，请在 .env 文件中配置 WECHAT_MINIAPP_APPID 和 WECHAT_MINIAPP_SECRET');
        }
    }

    /**
     * 通过 code 换取 session_key 和 openid
     *
     * @param string $code 小程序登录凭证
     * @return array ['openid' => '', 'session_key' => '', 'unionid' => '']
     * @throws Exception
     */
    public function code2Session(string $code): array
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $params = [
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
        ];

        $response = $this->httpGet($url, $params);
        $result = json_decode($response, true);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
            throw new Exception($result['errmsg'] ?? '获取 session_key 失败', $result['errcode']);
        }

        return $result;
    }

    /**
     * 解密微信加密数据（手机号、用户信息等）
     *
     * @param string $sessionKey 会话密钥
     * @param string $encryptedData 加密数据
     * @param string $iv 初始向量
     * @return array 解密后的数据
     * @throws Exception
     */
    public function decryptData(string $sessionKey, string $encryptedData, string $iv): array
    {
        // Base64 解码
        $sessionKey = base64_decode($sessionKey);
        $encryptedData = base64_decode($encryptedData);
        $iv = base64_decode($iv);

        // AES-128-CBC 解密
        $decrypted = openssl_decrypt(
            $encryptedData,
            'AES-128-CBC',
            $sessionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('解密失败');
        }

        // 解析 JSON
        $result = json_decode($decrypted, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('解密数据格式错误');
        }

        // 验证 AppID
        if (!isset($result['watermark']['appid']) || $result['watermark']['appid'] !== $this->appId) {
            throw new Exception('AppID 验证失败');
        }

        return $result;
    }

    /**
     * 解密手机号
     *
     * @param string $sessionKey 会话密钥
     * @param string $encryptedData 加密数据
     * @param string $iv 初始向量
     * @return string 手机号
     * @throws Exception
     */
    public function decryptPhoneNumber(string $sessionKey, string $encryptedData, string $iv): string
    {
        $data = $this->decryptData($sessionKey, $encryptedData, $iv);

        if (!isset($data['phoneNumber'])) {
            throw new Exception('手机号解密失败');
        }

        return $data['phoneNumber'];
    }

    /**
     * HTTP GET 请求
     *
     * @param string $url 请求地址
     * @param array $params 请求参数
     * @return string 响应内容
     */
    private function httpGet(string $url, array $params = []): string
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('HTTP 请求失败: ' . $error);
        }

        return $response;
    }
}

<?php

namespace app\api\controller;

use Throwable;
use ba\Captcha;
use ba\ClickCaptcha;
use think\facade\Config;
use think\facade\Log;
use app\common\library\InviteService;
use app\common\facade\Token;
use app\common\controller\Frontend;
use app\api\validate\User as UserValidate;

class User extends Frontend
{
    private const INVITE_DEBUG_TAG = '[INVITE_DEBUG]';

    protected array $noNeedLogin = ['checkIn', 'logout', 'mobileLogin', 'wechatLogin'];

    public function initialize(): void
    {
        parent::initialize();

        // 登录相关接口限流
        if (in_array($this->request->action(), ['checkIn', 'mobileLogin', 'wechatLogin'])) {
            $this->app->middleware->add([\think\middleware\Throttle::class, [
                'visit_rate' => '5/m',
                'key'        => '__CONTROLLER__/__ACTION__/__IP__',
            ]]);
        }
    }

    /**
     * 会员签入(登录和注册)
     * @throws Throwable
     */
    public function checkIn(): void
    {
        $openMemberCenter = Config::get('buildadmin.open_member_center');
        if (!$openMemberCenter) {
            $this->error(__('Member center disabled'));
        }

        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }

        $userLoginCaptchaSwitch = Config::get('buildadmin.user_login_captcha');

        if ($this->request->isPost()) {
            $params = $this->request->post(['tab', 'email', 'mobile', 'username', 'password', 'keep', 'captcha', 'captchaId', 'captchaInfo', 'registerType']);

            // 提前检查 tab ，然后将以 tab 值作为数据验证场景
            if (!in_array($params['tab'] ?? '', ['login', 'register'])) {
                $this->error(__('Unknown operation'));
            }

            $validate = new UserValidate();
            try {
                $validate->scene($params['tab'])->check($params);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            if ($params['tab'] == 'login') {
                if ($userLoginCaptchaSwitch) {
                    $captchaObj = new ClickCaptcha();
                    if (!$captchaObj->check($params['captchaId'], $params['captchaInfo'])) {
                        $this->error(__('Captcha error'));
                    }
                }
                $res = $this->auth->login($params['username'], $params['password'], !empty($params['keep']));
            } elseif ($params['tab'] == 'register') {
                $captchaObj = new Captcha();
                if (!$captchaObj->check($params['captcha'], $params[$params['registerType']] . 'user_register')) {
                    $this->error(__('Please enter the correct verification code'));
                }
                $res = $this->auth->register($params['username'], $params['password'], $params['mobile'], $params['email']);
            }

            if (isset($res) && $res === true) {
                $userInfo = $this->auth->getUserInfo();
                $userInfo['avatar'] = $this->convertImageUrl($userInfo['avatar'] ?? '');
                $this->success(__('Login succeeded!'), [
                    'userInfo'  => $userInfo,
                    'routePath' => '/user'
                ]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ?: __('Check in failed, please try again or contact the website administrator~');
                $this->error($msg);
            }
        }

        $this->success('', [
            'userLoginCaptchaSwitch'  => $userLoginCaptchaSwitch,
            'accountVerificationType' => get_account_verification_type()
        ]);
    }

    public function logout(): void
    {
        if ($this->request->isPost()) {
            $refreshToken = $this->request->post('refreshToken', '');
            if ($refreshToken) Token::delete((string)$refreshToken);
            $this->auth->logout();
            $this->success();
        }
    }

    /**
     * 手机号验证码登录
     * 手机号不存在时自动注册新用户
     * @throws Throwable
     */
    public function mobileLogin(): void
    {
        $openMemberCenter = Config::get('buildadmin.open_member_center');
        if (!$openMemberCenter) {
            $this->error(__('Member center disabled'));
        }

        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }

        if ($this->request->isPost()) {
            $params = $this->request->post(['mobile', 'captcha', 'inviter_id']);

            // 参数验证
            $validate = new UserValidate();
            try {
                $validate->sceneMobileLogin()->check($params);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            // 验证验证码
            $captchaObj = new Captcha();
            if (!$captchaObj->check($params['captcha'], $params['mobile'] . 'user_mobile_login')) {
                $this->error(__('验证码错误或已过期'));
            }

            $inviterId = intval($params['inviter_id'] ?? 0);
            $this->logInviteDebug('mobileLogin', 'start', [
                'inviter_id' => $inviterId,
                'mobile_tail' => substr((string)$params['mobile'], -4),
                'ip' => $this->request->ip(),
            ]);

            // 查询手机号是否已注册
            $userModel = \app\common\model\User::where('mobile', $params['mobile'])->find();
            $inviteEligibleRetry = false;

            $isNewUser = false;
            if (!$userModel) {
                // 手机号未注册，自动注册新用户
                // username: 生成符合规则的用户名（u + 手机号）
                // mobile: 手机号
                // nickname: 脱敏手机号（138****8000）
                // password: 空（无密码登录）
                $username = 'u' . $params['mobile'];  // 生成符合规则的用户名

                $res = $this->auth->register(
                    $username,          // username（u + 手机号）
                    '',                 // password（空密码）
                    $params['mobile'],  // mobile
                    ''                  // email
                );

                if ($res !== true) {
                    $msg = $this->auth->getError();
                    $msg = $msg ?: __('注册失败，请稍后重试');
                    $this->error($msg);
                }
                $isNewUser = true;
            } else {
                if ($inviterId > 0) {
                    $inviteEligibleRetry = $this->isFreshUserForInviteRetry($userModel);
                }

                // 手机号已注册，直接登录
                // 使用 direct() 方法直接登录，无需密码验证
                $res = $this->auth->direct($userModel->id);

                if ($res !== true) {
                    $msg = $this->auth->getError();
                    $msg = $msg ?: __('登录失败，请稍后重试');
                    $this->error($msg);
                }
            }

            // 登录成功，返回用户信息和 Token
            $userInfo = $this->normalizeAuthUserInfo();
            $userId = intval($userInfo['id'] ?? 0);
            $this->logInviteDebug('mobileLogin', 'after-auth', [
                'user_id' => intval($userInfo['id'] ?? 0),
                'is_new_user' => $isNewUser ? 1 : 0,
                'retry_eligible' => $inviteEligibleRetry ? 1 : 0,
                'inviter_id' => $inviterId,
            ]);
            $this->bindInviteAfterLogin('mobileLogin', $isNewUser, $inviteEligibleRetry, $inviterId, $userId, 'mobile_login_share');

            $this->success(__('登录成功'), [
                'userInfo'  => $userInfo,
                'routePath' => '/user'
            ]);
        }

        $this->error(__('请求方式错误'));
    }

    /**
     * 微信小程序授权登录
     * 通过微信授权获取手机号，自动注册/登录
     * @throws Throwable
     */
    public function wechatLogin(): void
    {
        $openMemberCenter = Config::get('buildadmin.open_member_center');
        if (!$openMemberCenter) {
            $this->error(__('Member center disabled'));
        }

        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }

        if ($this->request->isPost()) {
            $params = $this->request->post(['code', 'nickname', 'avatar', 'inviter_id']);

            // 参数验证
            if (empty($params['code'])) {
                $this->error(__('参数错误'));
            }

            try {
                // 使用微信工具类
                $wechat = new \app\common\library\WechatMiniApp();

                $mobile = $wechat->getPhoneNumberNew($params['code']);

                $inviterId = intval($params['inviter_id'] ?? 0);
                $this->logInviteDebug('wechatLogin', 'start', [
                    'inviter_id' => $inviterId,
                    'mobile_tail' => substr((string)$mobile, -4),
                    'ip' => $this->request->ip(),
                ]);

                // 查询手机号是否已注册
                $userModel = \app\common\model\User::where('mobile', $mobile)->find();
                $inviteEligibleRetry = false;

                $isNewUser = false;
                if (!$userModel) {
                    // 手机号未注册，自动注册新用户
                    $username = 'u' . $mobile;

                    $extend = [];
                    if (!empty($params['nickname'] ?? '')) {
                        $extend['nickname'] = $params['nickname'] ?? '';
                    }
                    if (!empty($params['avatar'] ?? '')) {
                        $extend['avatar'] = $params['avatar'] ?? '';
                    }

                    $res = $this->auth->register(
                        $username,          // username
                        '',                 // password
                        $mobile,            // mobile
                        '',                 // email
                        1,                  // group_id
                        $extend             // extend
                    );

                    if ($res !== true) {
                        $msg = $this->auth->getError();
                        $msg = $msg ?: __('注册失败，请稍后重试');
                        $this->error($msg);
                    }
                    $isNewUser = true;
                } else {
                    if ($inviterId > 0) {
                        $inviteEligibleRetry = $this->isFreshUserForInviteRetry($userModel);
                    }

                    // 老用户静默更新资料（如果是默认昵称的话）
                    $isUpdated = false;
                    if ((empty($userModel->nickname) || preg_match('/1[3-9]\d\*\*\*\*\d{4}/', $userModel->nickname)) && !empty($params['nickname'] ?? '')) {
                        $userModel->nickname = $params['nickname'] ?? '';
                        $isUpdated = true;
                    }
                    if (empty($userModel->avatar) && !empty($params['avatar'] ?? '')) {
                        $userModel->avatar = $params['avatar'] ?? '';
                        $isUpdated = true;
                    }
                    if ($isUpdated) {
                        $userModel->save();
                    }

                    $res = $this->auth->direct($userModel->id);

                    if ($res !== true) {
                        $msg = $this->auth->getError();
                        $msg = $msg ?: __('登录失败，请稍后重试');
                        $this->error($msg);
                    }
                }

                // 登录成功，返回用户信息和 Token
                $userInfo = $this->normalizeAuthUserInfo();
                $userId = intval($userInfo['id'] ?? 0);
                $this->logInviteDebug('wechatLogin', 'after-auth', [
                    'user_id' => intval($userInfo['id'] ?? 0),
                    'is_new_user' => $isNewUser ? 1 : 0,
                    'retry_eligible' => $inviteEligibleRetry ? 1 : 0,
                    'inviter_id' => $inviterId,
                ]);
                $this->bindInviteAfterLogin('wechatLogin', $isNewUser, $inviteEligibleRetry, $inviterId, $userId, 'wechat_login_share');

                $this->success(__('登录成功'), [
                    'userInfo'  => $userInfo,
                    'routePath' => '/user'
                ]);
            } catch (\think\exception\HttpResponseException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->error($e->getMessage() ?: '微信登录失败，请稍后重试');
            }
        }

        $this->error(__('请求方式错误'));
    }

    /**
     * 获取用户信息及社交统计
     * GET /api/user/info
     */
    public function info(): void
    {
        if (!$this->auth->isLogin()) {
            $this->error(__('Please login first'), [], 401);
        }

        $userId = $this->auth->id;

        // 统计获赞数：该用户发布的笔记被点赞的总数
        $receivedLikes = \app\common\model\DiscoveryNote::where('user_id', $userId)
            ->sum('likes_count');

        // 统计获收藏数：该用户发布的笔记被收藏的总数
        $receivedCollections = \app\common\model\DiscoveryNote::where('user_id', $userId)
            ->sum('collections_count');

        // 统计粉丝数
        $fansCount = \app\common\model\UserFollow::where('follow_user_id', $userId)->count();

        // 统计关注数
        $followCount = \app\common\model\UserFollow::where('user_id', $userId)->count();

        // 统计我点赞过的笔记数
        $myLikesCount = \app\common\model\DiscoveryLike::where('user_id', $userId)->count();

        // 统计我收藏过的笔记数
        $myCollectionsCount = \app\common\model\DiscoveryCollection::where('user_id', $userId)->count();

        $userInfo = $this->auth->getUserInfo();
        $userInfo['avatar'] = $this->convertImageUrl($userInfo['avatar'] ?? '');

        $this->success('', [
            'userInfo' => $userInfo,
            'stats' => [
                'received_likes' => intval($receivedLikes),
                'received_collections' => intval($receivedCollections),
                'fans_count' => intval($fansCount),
                'follow_count' => intval($followCount),
                'my_likes_count' => intval($myLikesCount),
                'my_collections_count' => intval($myCollectionsCount)
            ]
        ]);
    }

    /**
     * 获取我点赞过的笔记
     * GET /api/user/likes
     */
    public function likes(): void
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 10);
        $userId = $this->auth->id;

        $list = \app\common\model\DiscoveryLike::with(['note' => function($query) {
            $query->with(['user' => function($q) {
                $q->field('id,nickname,avatar');
            }]);
        }])->where('user_id', $userId)
           ->order('create_time', 'desc')
           ->page($page, $limit)
           ->select()
           ->toArray();

        foreach ($list as &$item) {
            if (isset($item['note'])) {
                $item['note']['image_url'] = $this->convertImageUrl($item['note']['image_url']);
                if (empty($item['note']['user']) || !is_array($item['note']['user'])) {
                    $item['note']['user'] = [
                        'id' => 0,
                        'nickname' => '用户已注销',
                        'avatar' => '',
                    ];
                }
                $item['note']['user']['avatar'] = $this->convertImageUrl($item['note']['user']['avatar'] ?? '');
            }
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 获取我收藏的笔记
     * GET /api/user/collections
     */
    public function collections(): void
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 10);
        $userId = $this->auth->id;

        $list = \app\common\model\DiscoveryCollection::with(['note' => function($query) {
            $query->with(['user' => function($q) {
                $q->field('id,nickname,avatar');
            }]);
        }])->where('user_id', $userId)
           ->order('create_time', 'desc')
           ->page($page, $limit)
           ->select()
           ->toArray();

        foreach ($list as &$item) {
            if (isset($item['note'])) {
                $item['note']['image_url'] = $this->convertImageUrl($item['note']['image_url']);
                if (empty($item['note']['user']) || !is_array($item['note']['user'])) {
                    $item['note']['user'] = [
                        'id' => 0,
                        'nickname' => '用户已注销',
                        'avatar' => '',
                    ];
                }
                $item['note']['user']['avatar'] = $this->convertImageUrl($item['note']['user']['avatar'] ?? '');
            }
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 获取我的粉丝列表
     * GET /api/user/fans
     */
    public function fans(): void
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        $userId = $this->auth->id;

        $list = \app\common\model\UserFollow::with(['user' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where('follow_user_id', $userId)
           ->order('create_time', 'desc')
           ->page($page, $limit)
           ->select()
           ->toArray();

        foreach ($list as &$item) {
            if (empty($item['user']) || !is_array($item['user'])) {
                $item['user'] = [
                    'id' => 0,
                    'nickname' => '用户已注销',
                    'avatar' => '',
                ];
            }
            $item['user']['avatar'] = $this->convertImageUrl($item['user']['avatar'] ?? '');
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 获取我的关注列表
     * GET /api/user/follows
     */
    public function follows(): void
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        $userId = $this->auth->id;

        $list = \app\common\model\UserFollow::with(['followUser' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where('user_id', $userId)
           ->order('create_time', 'desc')
           ->page($page, $limit)
           ->select()
           ->toArray();

        foreach ($list as &$item) {
            if (empty($item['followUser']) || !is_array($item['followUser'])) {
                $item['followUser'] = [
                    'id' => 0,
                    'nickname' => '用户已注销',
                    'avatar' => '',
                ];
            }
            $item['followUser']['avatar'] = $this->convertImageUrl($item['followUser']['avatar'] ?? '');
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 转换图片URL为完整路径
     */
    private function convertImageUrl($url)
    {
        if (empty($url)) return '';
        if (str_starts_with($url, 'http')) return $url;
        // 若已经是当前域名路径但缺少协议，则补全协议
        $domain = $this->request->host();
        if (str_starts_with(ltrim($url, '/'), $domain)) {
            return 'https://' . ltrim($url, '/');
        }

        $domainWithProtocol = $this->request->domain();
        if (!str_starts_with($domainWithProtocol, 'http')) {
            $domainWithProtocol = 'https://' . ltrim($domainWithProtocol, '/');
        }
        return rtrim($domainWithProtocol, '/') . '/' . ltrim($url, '/');
    }

    /**
     * 兜底：首次登录请求中断后，短时间内再次登录仍允许补记邀请
     */
    private function isFreshUserForInviteRetry(\app\common\model\User $user): bool
    {
        $joinTime = intval($user->join_time ?? 0);
        $lastLoginTime = intval($user->last_login_time ?? 0);
        if ($joinTime <= 0 || $lastLoginTime <= 0) {
            return false;
        }
        if ($joinTime !== $lastLoginTime) {
            return false;
        }
        return (time() - $joinTime) <= 1800;
    }

    private function normalizeAuthUserInfo(): array
    {
        $userInfo = $this->auth->getUserInfo();
        $userInfo['avatar'] = $this->convertImageUrl($userInfo['avatar'] ?? '');
        return $userInfo;
    }

    private function bindInviteAfterLogin(
        string $action,
        bool $isNewUser,
        bool $inviteEligibleRetry,
        int $inviterId,
        int $userId,
        string $scene
    ): void {
        if (($isNewUser || $inviteEligibleRetry) && $inviterId > 0 && $userId > 0) {
            $bindRes = InviteService::bindNewUserInvite($inviterId, $userId, $scene);
            $this->logInviteDebug($action, 'bind-result', [
                'user_id' => $userId,
                'inviter_id' => $inviterId,
                'bind_result' => $bindRes ? 1 : 0,
            ]);
            return;
        }

        $this->logInviteDebug($action, 'skip-bind', [
            'user_id' => $userId,
            'inviter_id' => $inviterId,
            'is_new_user' => $isNewUser ? 1 : 0,
            'retry_eligible' => $inviteEligibleRetry ? 1 : 0,
        ]);
    }

    private function logInviteDebug(string $action, string $stage, array $context = []): void
    {
        Log::info(self::INVITE_DEBUG_TAG . '[' . $action . '] ' . $stage, $context);
    }
}

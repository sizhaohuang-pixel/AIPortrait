# 后端应用模块 (Backend)

[根目录](../CLAUDE.md) > **app**

---

## 变更记录 (Changelog)

### 2026-02-03 22:09:55
- 初始化后端模块文档
- 完成模块结构扫描

---

## 模块职责

后端应用模块基于 ThinkPHP 8.1 框架，采用多应用模式，提供：
- RESTful API 接口服务
- 后台管理功能
- 用户认证与权限控制
- 业务逻辑处理
- 数据持久化

## 入口与启动

### 主要入口点

1. **后台管理入口**
   - 文件：`app/admin/controller/Index.php`
   - 功能：管理员登录、后台初始化、权限验证

2. **API 接口入口**
   - 文件：`app/api/controller/Index.php`
   - 功能：前台 API 接口、用户认证

3. **公共基础控制器**
   - `app/common/controller/Backend.php` - 后台控制器基类
   - `app/common/controller/Api.php` - API 控制器基类
   - `app/common/controller/Frontend.php` - 前台控制器基类

### 启动方式

```bash
# 开发模式启动
php think run

# 指定端口启动
php think run -p 8000

# 生产环境（配合 Nginx/Apache）
# 配置 Web 服务器指向 public/ 目录
```

## 对外接口

### 后台管理接口 (Admin)

**认证接口**
- `POST /admin/index/login` - 管理员登录
- `POST /admin/index/logout` - 管理员登出
- `GET /admin/index/index` - 获取后台初始化数据

**用户管理**
- `GET /admin/user/user/index` - 用户列表
- `POST /admin/user/user/add` - 添加用户
- `PUT /admin/user/user/edit` - 编辑用户
- `DELETE /admin/user/user/del` - 删除用户

**权限管理**
- `GET /admin/auth/group/index` - 角色组列表
- `GET /admin/auth/rule/index` - 权限规则列表
- `GET /admin/auth/admin/index` - 管理员列表

**系统管理**
- `GET /admin/routine/config/index` - 系统配置
- `GET /admin/routine/attachment/index` - 附件管理
- `GET /admin/dashboard/index` - 控制台数据

### API 接口 (API)

**用户接口**
- `POST /api/user/login` - 用户登录
- `POST /api/user/register` - 用户注册
- `GET /api/user/info` - 获取用户信息
- `PUT /api/user/profile` - 更新用户资料

**公共接口**
- `POST /api/common/upload` - 文件上传
- `GET /api/index/index` - 首页数据

## 关键依赖与配置

### Composer 依赖

```json
{
  "topthink/framework": "8.1.1",
  "topthink/think-orm": "3.0.33",
  "topthink/think-multi-app": "1.1.1",
  "topthink/think-throttle": "2.0.2",
  "topthink/think-migration": "3.1.1",
  "guzzlehttp/guzzle": "^7.8.1",
  "phpmailer/phpmailer": "^6.8"
}
```

### 配置文件

**应用配置** (`config/app.php`)
- 应用调试模式
- 默认时区设置
- 异常处理配置

**数据库配置** (`config/database.php`)
- 数据库连接信息
- 表前缀：`ba_`
- 默认数据库：`aixiezhen`

**BuildAdmin 配置** (`config/buildadmin.php`)
- Token 配置（加密方式、有效期）
- 登录验证码开关
- 跨域访问域名
- 文件上传配置

**路由配置** (`config/route.php`)
- 路由规则定义
- 中间件配置

### 核心库

**认证库** (`app/common/library/Auth.php`)
- 用户认证
- 权限验证
- Token 管理

**Token 库** (`app/common/library/Token.php`)
- Token 生成与验证
- 支持 MySQL 和 Redis 驱动

**上传库** (`app/common/library/Upload.php`)
- 文件上传处理
- 支持本地存储

**邮件库** (`app/common/library/Email.php`)
- 邮件发送功能
- 基于 PHPMailer

## 数据模型

### 核心模型

**用户相关**
- `app/common/model/User.php` - 用户模型
- `app/common/model/UserScoreLog.php` - 积分日志
- `app/common/model/UserMoneyLog.php` - 余额日志

**管理员相关**
- `app/admin/model/Admin.php` - 管理员模型
- `app/admin/model/AdminGroup.php` - 管理员组
- `app/admin/model/AdminLog.php` - 管理员日志
- `app/admin/model/AdminRule.php` - 权限规则

**系统相关**
- `app/common/model/Config.php` - 系统配置
- `app/common/model/Attachment.php` - 附件管理
- `app/admin/model/DataRecycle.php` - 数据回收站
- `app/admin/model/SensitiveData.php` - 敏感数据

### 数据库表前缀

所有数据表使用 `ba_` 前缀，例如：
- `ba_admin` - 管理员表
- `ba_user` - 用户表
- `ba_admin_group` - 管理员组表
- `ba_admin_rule` - 权限规则表

## 测试与质量

### 当前状态
- 暂无自动化测试
- 建议添加 PHPUnit 测试套件

### 建议测试方案

**单元测试**
```bash
# 安装 PHPUnit
composer require --dev phpunit/phpunit

# 运行测试
./vendor/bin/phpunit
```

**API 测试**
- 使用 Postman 或 Insomnia 进行接口测试
- 建议添加 API 文档（Swagger/OpenAPI）

## 常见问题 (FAQ)

### 如何添加新的控制器？

1. 在对应模块下创建控制器文件
   ```php
   // app/admin/controller/YourController.php
   namespace app\admin\controller;
   use app\common\controller\Backend;

   class YourController extends Backend
   {
       public function index()
       {
           // 你的逻辑
       }
   }
   ```

2. 路由会自动注册，访问路径为：`/admin/your_controller/index`

### 如何进行权限控制？

在控制器中使用 `$noNeedLogin` 和 `$noNeedPermission` 属性：

```php
class YourController extends Backend
{
    // 无需登录的方法
    protected array $noNeedLogin = ['public_method'];

    // 无需权限验证的方法
    protected array $noNeedPermission = ['index'];
}
```

### 如何使用数据库迁移？

```bash
# 创建迁移文件
php think migrate:create CreateYourTable

# 运行迁移
php think migrate:run

# 回滚迁移
php think migrate:rollback
```

### 如何发送邮件？

```php
use app\common\library\Email;

$email = new Email();
$email->to('user@example.com')
      ->subject('邮件主题')
      ->message('邮件内容')
      ->send();
```

## 相关文件清单

### 控制器目录
```
app/admin/controller/
├── Index.php              # 后台入口控制器
├── Dashboard.php          # 控制台
├── Module.php             # 模块管理
├── Ajax.php               # Ajax 请求处理
├── auth/                  # 权限管理
│   ├── Admin.php
│   ├── Group.php
│   └── Rule.php
├── user/                  # 用户管理
│   ├── User.php
│   ├── Group.php
│   ├── ScoreLog.php
│   └── MoneyLog.php
├── routine/               # 常规管理
│   ├── Config.php
│   ├── Attachment.php
│   └── AdminInfo.php
└── security/              # 安全管理
    ├── DataRecycle.php
    ├── SensitiveData.php
    └── ...
```

### 模型目录
```
app/common/model/
├── User.php               # 用户模型
├── UserScoreLog.php       # 积分日志
├── UserMoneyLog.php       # 余额日志
├── Config.php             # 配置模型
└── Attachment.php         # 附件模型

app/admin/model/
├── Admin.php              # 管理员模型
├── AdminGroup.php         # 管理员组
├── AdminLog.php           # 管理员日志
└── AdminRule.php          # 权限规则
```

### 公共库目录
```
app/common/library/
├── Auth.php               # 认证库
├── Token.php              # Token 管理
├── Upload.php             # 上传处理
├── Email.php              # 邮件发送
├── Menu.php               # 菜单管理
└── SnowFlake.php          # 雪花算法 ID 生成
```

### 中间件目录
```
app/common/middleware/
├── AllowCrossDomain.php   # 跨域中间件
└── AdminLog.php           # 管理员日志中间件
```

---

**模块路径**: `E:\AgentWorker\AIPortrait\app`
**框架**: ThinkPHP 8.1.1
**PHP 版本**: >= 8.0.2
**文档更新**: 2026-02-03 22:09:55

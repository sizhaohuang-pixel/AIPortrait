# AIPortrait - AI 肖像管理系统

> 基于 BuildAdmin 框架的现代化 AI 肖像生成和管理系统

---

## 项目概述

AIPortrait 是一个功能完善的 AI 肖像管理系统，采用前后端分离架构，提供 AI 写真生成、积分系统、小程序支持、后台管理和权限管理等功能。

### 技术架构

| 层级 | 技术栈 | 版本 |
|------|--------|------|
| **后端** | PHP + ThinkPHP | 8.0.2+ / 8.1.1 |
| **管理后台** | Vue + TypeScript + Vite | 3.5.13 / 5.7.2 / 6.3.5 |
| **用户前端** | UniApp + Vue 3 | 3.0+ |
| **数据库** | MySQL + Phinx | 5.7+ / 3.1.1 |

---

## 项目结构

```
AIPortrait/
├── app/                    # 后端应用目录
│   ├── admin/             # 后台管理模块（控制器、中间件）
│   │   └── controller/
│   │       ├── ai/        # AI 写真管理（模板、风格、任务）
│   │       ├── auth/      # 权限管理（管理员、角色、规则）
│   │       ├── user/      # 用户管理
│   │       └── routine/   # 常规管理（配置、附件）
│   ├── api/               # API 接口模块
│   │   └── controller/
│   │       ├── Portrait.php   # AI 写真 API
│   │       ├── User.php       # 用户 API
│   │       ├── Score.php      # 积分 API
│   │       ├── Agreement.php  # 协议 API
│   │       └── Banner.php     # Banner API
│   └── common/            # 公共模块
│       ├── controller/    # 基础控制器（Backend、Api、Frontend）
│       ├── library/       # 公共库（Auth、Token、Upload、Email）
│       ├── model/         # 数据模型
│       └── middleware/    # 中间件
├── config/                # 配置文件（app、database、route 等）
├── database/              # 数据库迁移文件
│   └── migrations/        # Phinx 迁移文件
├── frontend/              # 用户前端（UniApp 小程序）
│   ├── pages/            # 页面（index、template-detail、generating、preview、history、mine）
│   ├── components/       # 公共组件
│   ├── services/         # 服务层（portrait.js、user.js、request.js）
│   ├── utils/            # 工具函数（auth.js、history.js）
│   ├── static/           # 静态资源
│   ├── App.vue           # 应用入口
│   ├── main.js           # 入口文件
│   ├── pages.json        # 页面配置
│   └── manifest.json     # 应用清单
├── web/                   # 管理后台（Vue3 + TypeScript）
│   ├── src/
│   │   ├── api/          # API 调用
│   │   ├── components/   # 公共组件（table、baInput、icon）
│   │   ├── stores/       # Pinia 状态管理
│   │   ├── views/        # 页面视图
│   │   ├── utils/        # 工具函数
│   │   └── lang/         # 多语言
│   ├── package.json      # 依赖配置
│   └── vite.config.ts    # Vite 配置
├── public/                # Web 入口目录
├── vendor/                # Composer 依赖
├── .claude/               # AI 上下文索引
│   └── index.json        # 项目索引文件
├── composer.json          # Composer 配置
└── README.md              # 项目说明
```

---

## 构建与运行

### 环境要求

- PHP >= 8.0.2
- MySQL >= 5.7
- Node.js >= 16.0
- Composer
- PNPM (推荐)

### 后端启动

```bash
# 1. 安装 PHP 依赖
composer install

# 2. 配置数据库
# 复制 .env.example 为 .env，修改数据库配置
cp .env.example .env

# 3. 运行数据库迁移
php think migrate:run

# 4. 启动开发服务器
php think run
```

### 管理后台启动

```bash
# 进入 web 目录
cd web

# 安装依赖
pnpm install

# 启动开发服务器
pnpm dev

# 构建生产版本
pnpm build

# 代码检查
pnpm lint

# 代码格式化
pnpm format

# 类型检查
pnpm typecheck
```

### 用户前端启动

```bash
# 使用 HBuilderX 打开 frontend/ 目录

# 运行到微信小程序
# 在 HBuilderX 中选择: 运行 → 运行到小程序模拟器 → 微信开发者工具

# 运行到 H5
# 在 HBuilderX 中选择: 运行 → 运行到浏览器 → Chrome

# 使用 CLI（如已配置）
npm run dev:h5
npm run dev:mp-weixin
npm run build:mp-weixin
```

---

## 开发规范

### PHP 编码规范

- 遵循 **PSR-12** 编码标准
- 使用 PHP 8.0+ 特性（类型声明、属性提升等）
- 命名空间遵循 **PSR-4** 自动加载规范
- 表前缀统一使用 `ba_`
- **时间字段必须使用带下划线的蛇形命名法**（如 `create_time`、`update_time`、`complete_time`），禁止使用驼峰命名

### TypeScript/Vue 编码规范

- 使用 **ESLint + Prettier** 进行代码格式化
- 遵循 Vue 3 Composition API 风格
- TypeScript 严格模式开启
- 组件使用 `<script setup>` 语法
- 使用 Tab 缩进（与现有代码保持一致）

### 命名规范

| 类型 | 规范 | 示例 |
|------|------|------|
| 目录 | kebab-case | `template-detail` |
| 文件 | 小写 | `index.vue` |
| 变量 | camelCase | `templateId` |
| 组件 | PascalCase | `SkeletonLoader` |

### 数据库规范

- 所有表使用 `ba_` 前缀
- 字段使用小写字母和下划线
- 时间字段使用 `_time` 后缀
- 布尔字段使用 `is_` 前缀
- 字符集：`utf8mb4`
- 排序规则：`utf8mb4_unicode_ci`
- 使用 Unix 时间戳（整型）

---

## 核心业务模块

### AI 写真管理

**后端控制器**
- `app/admin/controller/ai/Template.php` - AI 模板管理
- `app/admin/controller/ai/TemplateSub.php` - AI 子模板管理
- `app/admin/controller/ai/Style.php` - AI 风格管理
- `app/admin/controller/ai/Task.php` - AI 任务管理

**API 接口**
- `app/api/controller/Portrait.php` - AI 写真 API

**数据模型**
- `app/common/model/AiTemplate.php` - AI 模板模型
- `app/common/model/AiTemplateSub.php` - AI 子模板模型
- `app/common/model/AiStyle.php` - AI 风格模型
- `app/common/model/AiTask.php` - AI 任务模型
- `app/common/model/AiTaskResult.php` - AI 任务结果模型

**前端页面**
- `web/src/views/backend/ai/template/index.vue` - 模板管理
- `web/src/views/backend/ai/task/index.vue` - 任务管理
- `web/src/views/backend/ai/task/detailDialog.vue` - 任务详情
- `frontend/pages/index/index.vue` - 小程序首页（模板列表）
- `frontend/pages/template-detail/index.vue` - 模板详情
- `frontend/pages/generating/index.vue` - 生成中
- `frontend/pages/preview/index.vue` - 预览
- `frontend/pages/history/index.vue` - 历史记录

### 积分系统

**API 接口**
- `app/api/controller/Score.php` - 积分 API

**数据模型**
- `app/common/model/ScoreConfig.php` - 积分配置
- `app/common/model/ScoreRechargePackage.php` - 充值套餐
- `app/common/model/ScoreRechargeOrder.php` - 充值订单
- `app/common/model/UserScoreLog.php` - 积分日志

**小程序页面**
- `frontend/pages/score/detail.vue` - 积分明细
- `frontend/pages/score/recharge.vue` - 积分充值

### 用户系统

**API 接口**
- `app/api/controller/User.php` - 用户 API
- `app/api/controller/Account.php` - 账户 API

**数据模型**
- `app/common/model/User.php` - 用户模型
- `app/common/model/UserMoneyLog.php` - 余额日志

**小程序页面**
- `frontend/pages/mine/index.vue` - 我的页面
- `frontend/pages/login/index.vue` - 登录页面
- `frontend/pages/profile/edit.vue` - 个人资料编辑

---

## 常用命令

### 数据库迁移

```bash
# 创建新的迁移文件
php think migrate:create YourMigrationName

# 运行所有迁移
php think migrate:run

# 回滚最后一次迁移
php think migrate:rollback

# 回滚到指定版本
php think migrate:rollback -t 0

# 查看迁移状态
php think migrate:status
```

### 后端命令

```bash
# 启动开发服务器
php think run

# 指定端口启动
php think run -p 8000

# 服务发现
php think service:discover

# 发布资源
php think vendor:publish
```

### 前端命令（管理后台）

```bash
cd web

# 开发模式
pnpm dev

# 构建生产版本
pnpm build

# 代码检查
pnpm lint

# 自动修复
pnpm lint-fix

# 代码格式化
pnpm format

# 类型检查
pnpm typecheck
```

---

## 配置文件

### 后端配置

| 文件 | 说明 |
|------|------|
| `config/app.php` | 应用配置（调试模式、时区） |
| `config/database.php` | 数据库配置（连接信息、表前缀） |
| `config/route.php` | 路由配置 |
| `config/buildadmin.php` | BuildAdmin 配置（Token、上传） |

### 前端配置

| 文件 | 说明 |
|------|------|
| `web/vite.config.ts` | Vite 配置 |
| `web/tsconfig.json` | TypeScript 配置 |
| `web/eslint.config.js` | ESLint 配置 |
| `frontend/manifest.json` | 小程序配置 |
| `frontend/pages.json` | 页面路由配置 |

---

## 开发指南

### 添加新的后台管理页面

1. 在 `app/admin/controller/` 创建控制器
2. 在 `web/src/views/backend/` 创建 Vue 组件
3. 在 `web/src/api/backend/` 添加 API 调用方法
4. 在后台菜单管理中添加菜单项

### 添加新的 API 接口

1. 在 `app/api/controller/` 创建控制器
2. 在 `app/common/model/` 创建或使用现有模型
3. 在前端调用新接口

### 权限控制

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

---

## 注意事项

1. **数据库字段命名**：所有时间字段必须使用带下划线的蛇形命名法（如 `create_time`、`update_time`），禁止使用驼峰命名
2. **小程序图片域名**：生产环境需要将外部图片 URL 替换为自己的 CDN/OSS 域名，并在微信公众平台配置服务器域名白名单
3. **缓存清理**：修改代码后如未生效，尝试清理 `runtime/` 目录下的缓存文件
4. **不要随意编写临时脚本**：可以直接使用 mysql 命令行来操作数据库

---

## 相关文档

- [项目根文档](./CLAUDE.md) - 详细项目文档
- [后端模块文档](./app/CLAUDE.md) - 后端开发指南
- [管理后台文档](./web/CLAUDE.md) - 前端开发指南
- [用户前端文档](./frontend/CLAUDE.md) - 小程序开发指南
- [数据库文档](./database/CLAUDE.md) - 数据库迁移指南

## 外部文档

- [BuildAdmin 官方文档](https://doc.buildadmin.com/)
- [ThinkPHP 8 文档](https://doc.thinkphp.cn/)
- [Vue 3 文档](https://cn.vuejs.org/)
- [UniApp 官方文档](https://uniapp.dcloud.net.cn/)
- [Element Plus 文档](https://element-plus.org/zh-CN/)

---

**框架版本**: BuildAdmin v2.3.5 + ThinkPHP 8.1.1  
**文档生成时间**: 2026-02-15  
**维护者**: AIPortrait Team

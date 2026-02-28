# 后端应用模块 (Backend)

[根目录](../CLAUDE.md) > **app**

---

## 变更记录 (Changelog)

### 2026-02-25 16:44:49
- **✨ 维护：同步最新的架构扫描结果**
  - 确认 `InviteService` 及 `Invite` 接口已上线
  - 优化 API 与 Library 的索引映射

### 2026-02-21 06:15:00
- **✨ 增强：WechatMiniApp 登录能力升级**
- **✨ 新增：社交发现接口与守护进程**
  - `PortraitDaemon.php` 与 `Discovery.php`

---

## 模块职责

后端应用模块基于 ThinkPHP 8.1 框架，提供 API 接口、后台管理、**AI 任务异步处理**及用户权限控制。

## 入口与启动

### 主要入口点

1. **后台管理**: `app/admin/controller/Index.php`
2. **API 接口**: `app/api/controller/Index.php`
3. **命令行守护进程**: `app/command/PortraitDaemon.php`

### 启动方式

```bash
# 启动 AI 任务守护进程
php think portrait:daemon
```

## 对外接口 (核心业务)

### API 接口 (API)

**AI 写真 (Portrait)**
- `GET /api/portrait/index` - 模板列表
- `POST /api/portrait/generate` - 提交生成

**社交发现 (Discovery)**
- `GET /api/discovery/index` - 发现页动态
- `POST /api/discovery/publish` - 发布笔记

**邀请奖励 (Invite)**
- `POST /api/invite/apply` - 填写邀请码

## 数据模型

- `app/common/model/AiTask.php` - AI 任务
- `app/common/model/DiscoveryNote.php` - 发现笔记
- `app/common/model/UserInvite.php` - 邀请关系

---

**模块路径**: `E:\AgentWorker\AIPortrait\app`
**文档更新**: 2026-02-25 16:44:49

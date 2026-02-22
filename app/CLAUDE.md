# 后端应用模块 (Backend)

[根目录](../CLAUDE.md) > **app**

---

## 变更记录 (Changelog)

### 2026-02-21 06:15:00
- **✨ 增强：WechatMiniApp 登录能力升级**
  - 新增 `getAccessToken` 接口，支持 AccessToken 自动缓存
  - 新增 `getPhoneNumberNew` 接口，适配微信最新的手机号验证方案，彻底解决解密失败问题
- **✨ 新增：社交发现接口**
  - `app/api/controller/Discovery.php` - 实现点赞、收藏、关注、评论等完整社交链路
- **✨ 新增：AI 任务守护进程**
  - `app/command/PortraitDaemon.php` - 核心调度命令
- **✨ 新增：社交相关数据模型**
  - `DiscoveryNote`, `DiscoveryLike`, `DiscoveryCollection`, `DiscoveryComment`, `UserFollow`

### 2026-02-03 22:09:55
- 初始化后端模块文档
- 完成模块结构扫描

---

## 模块职责

后端应用模块基于 ThinkPHP 8.1 框架，提供：
- RESTful API 接口服务
- 后台管理功能 (admin 应用)
- **AI 任务异步处理 (command 应用)**
- 用户认证与权限控制
- 业务逻辑处理与数据持久化

## 入口与启动

### 主要入口点

1. **后台管理入口**: `app/admin/controller/Index.php`
2. **API 接口入口**: `app/api/controller/Index.php`
3. **命令行入口 (守护进程)**: `app/command/PortraitDaemon.php`

### 启动方式

```bash
# 开发模式启动 Web 服务
php think run

# 启动 AI 任务守护进程
php think portrait:daemon
```

## 对外接口 (核心业务)

### API 接口 (API)

**AI 写真 (Portrait)**
- `GET /api/portrait/index` - 获取模板列表
- `POST /api/portrait/generate` - 提交生成任务

**社交发现 (Discovery)**
- `GET /api/discovery/index` - 发现页动态列表
- `GET /api/discovery/detail` - 笔记详情与交互状态
- `POST /api/discovery/publish` - 发布新笔记
- `POST /api/discovery/toggleLike` - 点赞/取消点赞
- `POST /api/discovery/toggleFollow` - 关注/取消关注

## 数据模型

### 社交发现模型
- `app/common/model/DiscoveryNote.php` - 发现笔记 (含获赞、收藏、评论统计)
- `app/common/model/DiscoveryLike.php` - 点赞记录
- `app/common/model/DiscoveryCollection.php` - 收藏记录
- `app/common/model/DiscoveryComment.php` - 评论内容
- `app/common/model/UserFollow.php` - 用户关注关系

### AI 任务模型
- `app/common/model/AiTask.php` - 任务主表
- `app/common/model/AiTaskResult.php` - 任务生成结果 (图片路径)

## 常见问题 (FAQ)

### 如何运行 AI 任务守护进程？
守护进程负责轮询数据库并调用第三方 AI 接口。建议使用 `Supervisor` 或 `Screen` 持久运行：
```bash
php think portrait:daemon
```
该进程包含自动重试机制、内存回收及并发任务抢占逻辑。

### 社交数据的统计是如何更新的？
点赞、收藏及评论的计数字段（如 `likes_count`）存储在 `ba_discovery_note` 表中。在 `Discovery` 控制器中，所有修改操作均包裹在数据库事务中，并使用 `inc()` 或 `dec()` 配合排他锁确保数据原子性。

---

**模块路径**: `E:\AgentWorker\AIPortrait\app`
**框架**: ThinkPHP 8.1.1
**PHP 版本**: >= 8.0.2
**文档更新**: 2026-02-21 05:07:30

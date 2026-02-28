# 数据库模块 (Database)

[根目录](../CLAUDE.md) > **database**

---

## 变更记录 (Changelog)

### 2026-02-25 16:44:49
- **✨ 维护：更新迁移脚本记录**
  - 新增 `20260224010100_invite_reward_feature.php` (邀请奖励功能数据库迁移)

### 2026-02-03 22:09:55
- 初始化数据库模块文档

---

## 模块职责

负责数据库结构定义、版本迁移（Migration）及初始数据填充。

## 常用命令

```bash
# 运行迁移
php think migrate:run

# 查看状态
php think migrate:status
```

## 核心数据表 (业务)

- `ba_ai_task` - AI 任务记录
- `ba_discovery_note` - 发现笔记
- `ba_user_invite` - 邀请关系表

---

**最新迁移**: 20260224010100_invite_reward_feature.php
**文档更新**: 2026-02-25 16:44:49

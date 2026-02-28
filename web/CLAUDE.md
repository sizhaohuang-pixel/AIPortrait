# 管理后台模块 (Admin Web)

[根目录](../CLAUDE.md) > **web**

---

## 变更记录 (Changelog)

### 2026-02-25 16:44:49
- **✨ 维护：更新 UI 与 API 索引**
  - 同步 `web/src/views/backend/score/invite` 等管理页面索引

### 2026-02-03 22:09:55
- 初始化前端模块文档

---

## 模块职责

基于 Vue 3 + TypeScript + Vite 构建，提供管理员界面、数据可视化及系统配置。

## 入口与启动

### 主要入口点
- **入口文件**: `web/src/main.ts`
- **根组件**: `web/src/App.vue`

### 启动方式
```bash
cd web
pnpm install
pnpm dev
```

## 对外接口

### 主要路由
- `/admin/ai/task` - AI 任务管理
- `/admin/discovery/note` - 笔记审核
- `/admin/score/invite` - 邀请奖励配置

---

**模块路径**: `E:\AgentWorker\AIPortrait\web`
**框架**: Vue 3.5.13 + Vite 6.3.5
**文档更新**: 2026-02-25 16:44:49

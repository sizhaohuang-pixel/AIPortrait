# Frontend 模块 - UniApp 小程序前端

> 📍 **导航**: [← 返回项目根目录](../CLAUDE.md) | [后端模块](../app/CLAUDE.md) | [管理后台](../web/CLAUDE.md) | [数据库](../database/CLAUDE.md)

---

## 📋 模块概述

**模块名称**: Frontend (用户侧小程序前端)
**技术栈**: UniApp + Vue 3
**目标平台**: 微信小程序 / H5
**主要职责**: AI 写真核心流程、社区社交、用户中心及积分管理

这是 AIPortrait 项目的用户侧前端模块，基于 UniApp 框架开发。源代码位于 `frontend/src/`。

---

## 🎯 核心功能

### 1. 写真核心
- **首页 (index)**: 模板展示与分类
- **详情 (template-detail)**: 模板参数与示例
- **生成 (upload/generating/preview)**: 上传、排队进度与结果查看
- **相册 (history)**: 历史作品管理

### 2. 社交发现 (✨ 新增)
- **发现页 (discovery/index)**: 社区笔记瀑布流列表
- **详情页 (discovery/detail)**: 笔记内容、评论、点赞与关注交互
- **发布页 (discovery/post)**: 生成作品一键同步到社区
- **管理页 (discovery/my)**: 个人社交动态管理

### 3. 用户与积分
- **个人中心 (mine)**: 资料编辑 (profile/edit)、积分明细
- **充值 (score/recharge)**: 积分套餐购买
- **登录 (login)**: 微信一键登录与手机号授权

---

## 📁 目录结构

```
frontend/src/
├── pages/                      # 业务页面
│   ├── index/                  # 首页 (TabBar)
│   ├── discovery/              # 发现模块 (TabBar)
│   ├── mine/                   # 我的 (TabBar)
│   ├── upload/                 # 上传生成
│   ├── history/                # 我的相册
│   └── score/                  # 积分系统
├── components/                 # 公共组件
├── services/                   # API 请求封装
├── static/                     # 静态资源 (图标、图片)
├── utils/                      # 工具函数 (Auth, Request)
├── App.vue                     # 应用生命周期
└── main.js                     # 项目入口
```

---

## 🚀 启动与运行

### 核心配置文件
- **路由配置**: `frontend/src/pages.json` (包含 TabBar 定义)
- **应用配置**: `frontend/src/manifest.json` (小程序 AppID、权限配置)

### 常用命令
```bash
# 进入目录
cd frontend

# 安装依赖
pnpm install

# 运行到小程序 (通过 HBuilderX 界面操作或 CLI)
npm run dev:mp-weixin
```

---

## 🔌 对外接口

### 主要 API 路径
- `/api/portrait/*` - 写真模板与任务控制
- `/api/discovery/*` - 社交发现、评论、互动
- `/api/user/*` - 用户资料与登录
- `/api/score/*` - 积分充值与流水

---

## 🎨 编码规范

- **路径引用**: 优先使用 `@/` 别名指向 `src/` 目录
- **组件开发**: 遵循 Vue 3 Composition API (`<script setup>`)
- **命名**: 页面目录使用 kebab-case，内部逻辑使用 camelCase
- **时间格式**: 统一使用蛇形命名法（如 `create_time`）与后端对齐

---

## 📝 变更记录

### 2026-02-21
- ✅ 完成社交发现模块 (Discovery) 1.0 开发
- ✅ 迁移项目结构至 `src/` 目录模式
- ✅ 更新 TabBar 导航，新增"发现"频道
- ✅ 实现积分充值与编辑资料功能

### 2026-02-03
- ✅ 初始化项目并实现 AI 写真核心链路

---

**最后更新**: 2026-02-21
**文档版本**: v1.1.0

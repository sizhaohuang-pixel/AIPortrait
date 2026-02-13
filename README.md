# AIPortrait - AI 肖像管理系统

<div align="center">
    <h1>AIPortrait</h1>
    <p>基于 BuildAdmin 框架的现代化 AI 肖像生成和管理系统</p>
</div>

<p align="center">
    <a href="https://www.thinkphp.cn/" target="_blank">
        <img src="https://img.shields.io/badge/ThinkPHP-8.1-brightgreen?color=91aac3&labelColor=439EFD" alt="thinkphp">
    </a>
    <a href="https://v3.vuejs.org/" target="_blank">
        <img src="https://img.shields.io/badge/Vue-3.5-brightgreen?color=91aac3&labelColor=439EFD" alt="vue">
    </a>
    <a href="https://element-plus.org/zh-CN/" target="_blank">
        <img src="https://img.shields.io/badge/Element--Plus-2.9-brightgreen?color=91aac3&labelColor=439EFD" alt="element plus">
    </a>
    <a href="https://www.tslang.cn/" target="_blank">
        <img src="https://img.shields.io/badge/TypeScript-5.7-blue?color=91aac3&labelColor=439EFD" alt="typescript">
    </a>
    <a href="https://vitejs.dev/" target="_blank">
        <img src="https://img.shields.io/badge/Vite-6.3-blue?color=91aac3&labelColor=439EFD" alt="vite">
    </a>
    <a href="https://uniapp.dcloud.net.cn/" target="_blank">
        <img src="https://img.shields.io/badge/UniApp-3.0-blue?color=91aac3&labelColor=439EFD" alt="uniapp">
    </a>
</p>

## 项目简介

AIPortrait 是一个功能完善的 AI 肖像管理系统，采用前后端分离架构，提供：

- **AI 写真生成**：支持多种模板和风格的 AI 肖像生成
- **积分系统**：完整的积分充值、消费和记录管理
- **小程序支持**：基于 UniApp 的微信小程序端
- **后台管理**：功能强大的管理后台系统
- **权限管理**：完善的用户权限和角色管理

## 技术架构

### 后端
- **PHP** 8.0.2+
- **ThinkPHP** 8.1.1
- **MySQL** 5.7+
- **Composer** 依赖管理
- **Phinx** 数据库迁移

### 管理后台
- **Vue** 3.5.13
- **TypeScript** 5.7.2
- **Vite** 6.3.5
- **Element Plus** 2.9.1
- **Pinia** 2.3.0 状态管理

### 用户前端
- **UniApp** 跨平台框架
- **Vue** 3
- 支持微信小程序 / H5 / App

## 核心功能

### AI 写真管理
- AI 模板管理（主模板、子模板）
- AI 风格管理
- AI 任务管理（生成、查询、历史记录）
- 生成结果管理

### 积分系统
- 积分充值（多种套餐）
- 积分消费（生成任务扣费）
- 积分记录查询
- 充值订单管理

### 用户管理
- 用户注册登录
- 个人资料管理
- 余额和积分管理
- 用户日志记录

### 内容管理
- 协议管理
- Banner 管理
- 系统配置

## 项目结构

```
AIPortrait/
├── app/                    # 后端应用
│   ├── admin/             # 后台管理模块
│   │   └── controller/
│   │       └── ai/        # AI 写真管理
│   ├── api/               # API 接口模块
│   │   └── controller/    # API 控制器
│   └── common/            # 公共模块
│       ├── library/       # 公共库
│       └── model/         # 数据模型
├── config/                # 配置文件
├── database/              # 数据库
│   └── migrations/        # 迁移文件
├── frontend/              # 用户前端 (UniApp)
│   ├── pages/            # 小程序页面
│   ├── components/       # 公共组件
│   ├── services/         # 服务层
│   └── utils/            # 工具函数
├── web/                   # 管理后台
│   ├── src/              # 前端源代码
│   │   ├── api/          # API 调用
│   │   ├── components/   # 公共组件
│   │   ├── stores/       # 状态管理
│   │   └── views/        # 页面视图
│   └── package.json      # 前端依赖
├── public/                # Web 入口
└── vendor/                # Composer 依赖
```

## 快速开始

### 环境要求

- PHP >= 8.0.2
- MySQL >= 5.7
- Node.js >= 16.0
- Composer
- PNPM

### 后端安装

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

### 管理后台安装

```bash
# 进入 web 目录
cd web

# 安装依赖
pnpm install

# 启动开发服务器
pnpm dev

# 构建生产版本
pnpm build
```

### 用户前端安装

```bash
# 使用 HBuilderX 打开 frontend/ 目录

# 运行到微信小程序
# 在 HBuilderX 中选择: 运行 → 运行到小程序模拟器 → 微信开发者工具

# 运行到 H5
# 在 HBuilderX 中选择: 运行 → 运行到浏览器 → Chrome
```

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

### 修改数据库结构

```bash
# 创建新的迁移文件
php think migrate:create YourMigrationName

# 编辑迁移文件定义变更
# 文件位置: database/migrations/

# 运行迁移
php think migrate:run

# 回滚迁移
php think migrate:rollback
```

## 目录说明

| 目录 | 说明 |
|------|------|
| `app/admin/` | 后台管理模块（控制器、视图） |
| `app/api/` | API 接口模块 |
| `app/common/` | 公共模块（模型、库、中间件） |
| `config/` | 应用配置文件 |
| `database/migrations/` | 数据库迁移文件 |
| `frontend/` | UniApp 小程序前端 |
| `web/` | Vue3 管理后台前端 |
| `public/` | Web 入口目录 |

## 相关文档

- [BuildAdmin 官方文档](https://doc.buildadmin.com/)
- [ThinkPHP 8 文档](https://doc.thinkphp.cn/)
- [Vue 3 文档](https://cn.vuejs.org/)
- [UniApp 文档](https://uniapp.dcloud.net.cn/)
- [Element Plus 文档](https://element-plus.org/zh-CN/)

## 版权信息

本项目基于 BuildAdmin 框架开发，遵循 Apache 2.0 开源协议。

## 技术支持

如有问题，请提交 Issue 或查阅项目文档。

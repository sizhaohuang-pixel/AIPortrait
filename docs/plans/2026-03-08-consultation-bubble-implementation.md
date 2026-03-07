# 咨询悬浮按钮配置 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 实现小程序“咨询”悬浮按钮的多条随机气泡文案后台配置功能。

**Architecture:** 
- 后端 ThinkPHP API：修改配置接口以支持 `service_bubble_texts` 的存储与下发。
- Admin Web：在企业微信客服配置中增加多行文本框供管理员配置。
- UniApp 前端：组件挂载时将文本按行分割并随机抽取一条展示，缺省时使用默认文案。

**Tech Stack:** ThinkPHP 8.1, Vue 3, Element Plus, UniApp

---

### Task 1: 后端支持气泡文案配置保存与读取

**Files:**
- Modify: `app/admin/controller/score/Config.php`
- Modify: `app/api/controller/Score.php`

**Step 1: 增加允许保存的配置键**
修改 `app/admin/controller/score/Config.php` 中 `$allowKeys` 数组，加入 `'service_bubble_texts'`。

**Step 2: API 接口返回新增字段**
修改 `app/api/controller/Score.php` 中 `config()` 方法：
```php
$serviceBubbleTexts = ScoreConfig::getConfigValue('service_bubble_texts', '');
// 在返回的数组中加入 'service_bubble_texts' => $serviceBubbleTexts,
```

**Step 3: Commit**
```bash
git add app/admin/controller/score/Config.php app/api/controller/Score.php
git commit -m "feat(api): 支持保存和下发 service_bubble_texts 配置"
```

---

### Task 2: 管理后台增加气泡文案输入框

**Files:**
- Modify: `web/src/views/backend/mini/config/index.vue`

**Step 1: 修改 state.form 定义与取值逻辑**
在 `state.form` 和 `getConfig` 方法中加入 `service_bubble_texts`。

**Step 2: 增加 el-form-item**
在 `企业微信客服配置` divider 后加入：
```html
<el-form-item label="客服悬浮气泡内容" prop="service_bubble_texts">
    <el-input
        v-model="state.form.service_bubble_texts"
        type="textarea"
        :rows="4"
        placeholder="请输入客服悬浮气泡内容，每行一条。如果不填则使用默认文案"
        maxlength="500"
        show-word-limit
    />
    <div class="form-item-tip">支持配置多条，小程序端每次随机展示一条（每行代表一条文案）</div>
</el-form-item>
```

**Step 3: Commit**
```bash
git add web/src/views/backend/mini/config/index.vue
git commit -m "feat(admin): 增加客服悬浮气泡文案的多行配置项"
```

---

### Task 3: 小程序前端展示随机气泡文案

**Files:**
- Modify: `frontend/src/components/floating-service-button.vue`

**Step 1: 增加 props 与 data 字段**
在 `data()` 中增加 `loadedBubbleTexts: ''` 或 `currentBubbleText: ''`，如果使用 props 默认值，可以在 `computed` 中处理或者在获取到配置后赋值。由于原本组件是从 props 获取 `bubbleText`，我们可以将其覆盖或者通过 `computed` 渲染。

**Step 2: 处理随机逻辑**
在 `loadServiceChatConfig` 中：
```javascript
const textsStr = (data.service_bubble_texts || '').trim();
if (textsStr) {
    const lines = textsStr.split('\n').map(line => line.trim()).filter(line => line.length > 0);
    if (lines.length > 0) {
        const randomIndex = Math.floor(Math.random() * lines.length);
        this.currentBubbleText = lines[randomIndex];
    }
}
```
并且在 `<template>` 中，将 `{{ bubbleText }}` 修改为 `{{ currentBubbleText || bubbleText }}`。

**Step 3: Commit**
```bash
git add frontend/src/components/floating-service-button.vue
git commit -m "feat(app): 支持随机展示客服悬浮气泡文案"
```

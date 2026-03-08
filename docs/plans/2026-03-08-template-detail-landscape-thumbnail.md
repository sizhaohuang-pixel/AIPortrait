# Template Detail Landscape Thumbnail Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 让模板详情页的横图子模版完整显示，并在卡片内垂直居中，允许上下留白。

**Architecture:** 仅修改模板详情页子模版卡片的模板与样式。横图单独包一层容器并使用 `aspectFit`，竖图保持原有 `aspectFill`，避免影响其他页面与图片展示逻辑。

**Tech Stack:** UniApp、Vue 3 单文件组件、Scoped CSS

---

### Task 1: Template Detail Landscape Thumbnail

**Files:**
- Modify: `frontend/src/pages/template-detail/index.vue`

**Step 1: Inspect current thumbnail rendering**
- 确认子模版图片当前使用 `aspectFill`，横图会被裁切。

**Step 2: Implement minimal template change**
- 为子模版图片增加横图容器类。
- 横图使用 `aspectFit`；竖图保留原有 `aspectFill`。

**Step 3: Implement minimal style change**
- 横图容器设置固定比例、居中布局、浅色背景。
- 横图图片设置 `width: 100%`、`height: 100%` 与圆角。

**Step 4: Verify scope**
- 确认只影响模板详情页子模版卡片，不影响主图与其他页面。

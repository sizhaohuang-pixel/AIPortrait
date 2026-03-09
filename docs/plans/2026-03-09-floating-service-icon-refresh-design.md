# Floating Service Icon Refresh Design

**Goal:** 将首页及全站复用的“咨询”悬浮按钮替换为更好看的客服耳机图标，并采用参考图的柔和渐变圆形风格。

**Approach:** 仅修改 `frontend/src/components/floating-service-button.vue` 的视觉层。保留原有拖拽、点击、气泡、企微跳转逻辑不变，只替换图标结构与对应样式。

**Scope:** 不新增依赖，不新增图片资源，使用纯 CSS 绘制图标与按钮高光层。

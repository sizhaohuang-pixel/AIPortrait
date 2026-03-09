# Floating Service Shadow Refresh Design

**Goal:** 为悬浮咨询按钮增加更高级的柔和悬浮阴影，让按钮更有空间感但不显突兀。

**Approach:** 仅调整 `frontend/src/components/floating-service-button.vue` 的阴影样式。保留现有颜色、图标、尺寸与交互逻辑不变，通过多层阴影叠加实现更柔和的悬浮感。

**Scope:** 纯 CSS 样式修改，不新增资源，不影响拖拽、点击与气泡逻辑。

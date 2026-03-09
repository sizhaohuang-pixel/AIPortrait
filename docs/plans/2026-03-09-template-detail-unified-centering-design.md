# Template Detail Unified Centering Design

**Goal:** 模板详情页顶部主图与子模版缩略图，无论横图还是竖图，都在各自展示容器内垂直居中显示。

**Approach:** 统一使用“外层固定展示盒 + 内层图片居中”结构。图片改为完整显示优先，允许留白，避免横图或竖图在容器内贴边。

**Scope:** 仅修改 `frontend/src/pages/template-detail/index.vue` 的模板和样式，不影响数据与跳转逻辑。

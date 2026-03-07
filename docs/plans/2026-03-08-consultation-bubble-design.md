# 咨询悬浮按钮气泡框配置设计方案

## 背景与需求
目前首页及其他页面的“咨询”悬浮按钮的气泡内容是硬编码的单天文案。需求要求该内容支持后台配置，并且支持配置多条内容。用户在前端看到的时候，每次能够随机获取其中的一条显示。

## 设计决策

1. **后台配置采用多行文本域（Textarea）形式**
   - **决策原因**：通过换行符分离每条文案能够非常高效地让管理员进行批量配置与修改，在保证功能扩展性的同时简化了前端表单结构。
2. **随机选取逻辑在前端实现**
   - **决策原因**：前端在应用启动或获取全局配置时请求一次，将其包含的多条内容缓存为数组。当悬浮组件实例创建时直接在前端使用 `Math.random()` 选取一条渲染。这减少了后端的随机计算和频繁的网络请求开销。

## 架构与组件变更

### 1. 数据库与后端
- **模型**：`ScoreConfig` (使用键 `service_bubble_texts` 保存)。
- **管理员接口 (`app/admin/controller/score/Config.php`)**：
  - 更新 `save` 接口的 `$allowKeys` 数组，增加 `service_bubble_texts` 允许被接收和存储。
- **公共接口 (`app/api/controller/Score.php`)**：
  - 更新 `config()` 方法，通过 `ScoreConfig::getConfigValue` 获取 `service_bubble_texts` 并下发到前端。

### 2. Admin Web 端 (`web/src/views/backend/mini/config/index.vue`)
- 在表单`企业微信客服配置`区块中，新增一个 `el-input`，类型为 `textarea`。
- **字段名**：`service_bubble_texts`
- **提示语**：支持配置多条咨询气泡文案，每行代表一条。若留空则使用小程序默认文案。

### 3. 小程序前端 (`frontend/src/components/floating-service-button.vue`)
- 在 `loadServiceChatConfig` 或全局配置加载时，读取接口返回的 `service_bubble_texts`。
- 将返回的字符串按换行符 (`\n`) 切割并过滤空行，得到文案数组。
- 如果数组有效且有数据，则从中随机取出一个元素覆写或作为 `bubbleText` 显示。
- 否则，退回使用默认的 prop: `限时优惠与人工定制，点我咨询`。

## 测试与验收标准
- 在 Admin 中输入多行文案（如：A、B、C），保存成功。
- 刷新小程序界面，多次显示组件时，气泡文案应当在 A、B、C 中随机切换展示。
- 当 Admin 中清空该配置后，小程序应该退回显示默认文案。
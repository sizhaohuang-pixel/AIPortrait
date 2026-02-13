# 前端应用模块 (Frontend)

[根目录](../CLAUDE.md) > **web**

---

## 变更记录 (Changelog)

### 2026-02-03 22:09:55
- 初始化前端模块文档
- 完成模块结构扫描

---

## 模块职责

前端应用模块基于 Vue 3 + TypeScript + Vite 构建，提供：
- 现代化的用户界面
- 响应式布局设计
- 状态管理与路由控制
- 与后端 API 的交互
- 多语言支持（i18n）

## 入口与启动

### 主要入口点

1. **HTML 入口**
   - 文件：`web/index.html`
   - 功能：应用的 HTML 模板，挂载点为 `#app`

2. **JavaScript 入口**
   - 文件：`web/src/main.ts`
   - 功能：Vue 应用初始化、插件注册、全局配置

3. **根组件**
   - 文件：`web/src/App.vue`
   - 功能：应用的根组件

### 启动方式

```bash
# 进入前端目录
cd web

# 安装依赖
pnpm install

# 开发模式启动（默认端口 5173）
pnpm dev

# 构建生产版本
pnpm build

# 代码检查
pnpm lint

# 代码格式化
pnpm format

# 类型检查
pnpm typecheck
```

### 环境变量

创建 `.env` 文件配置环境变量：
```env
VITE_PORT=5173
VITE_OPEN=true
VITE_BASE_PATH=/
VITE_OUT_DIR=dist
```

## 对外接口

### 页面路由

**后台管理页面**
- `/admin` - 后台首页
- `/admin/auth/admin` - 管理员管理
- `/admin/auth/group` - 角色组管理
- `/admin/auth/rule` - 权限规则管理
- `/admin/user/user` - 用户管理
- `/admin/routine/config` - 系统配置
- `/admin/dashboard` - 控制台

**前台页面**
- `/` - 前台首页
- `/user/login` - 用户登录
- `/user/account/profile` - 个人资料
- `/user/account/overview` - 账户概览

**错误页面**
- `/404` - 页面不存在
- `/401` - 未授权访问

### API 调用接口

API 调用统一在 `src/api/` 目录下定义：

**后台 API** (`src/api/backend/`)
- `index.ts` - 后台通用接口
- `dashboard.ts` - 控制台数据
- `auth/group.ts` - 角色组接口
- `user/group.ts` - 用户组接口
- `routine/config.ts` - 系统配置接口

**前台 API** (`src/api/frontend/`)
- `index.ts` - 前台通用接口
- `user/index.ts` - 用户相关接口

**公共 API** (`src/api/common.ts`)
- 文件上传
- 公共数据获取

## 关键依赖与配置

### NPM 依赖

**核心依赖**
```json
{
  "vue": "3.5.13",
  "vue-router": "4.5.0",
  "pinia": "2.3.0",
  "element-plus": "2.9.1",
  "axios": "1.9.0",
  "typescript": "5.7.2",
  "vite": "6.3.5"
}
```

**UI 组件库**
- Element Plus 2.9.1 - 主要 UI 组件库
- @element-plus/icons-vue - Element Plus 图标
- Font Awesome 4.7.0 - 图标库

**工具库**
- lodash-es - 实用工具函数
- echarts - 图表库
- mitt - 事件总线
- nprogress - 进度条
- screenfull - 全屏控制
- sortablejs - 拖拽排序

**开发依赖**
- ESLint - 代码检查
- Prettier - 代码格式化
- TypeScript - 类型系统
- Vue TSC - Vue 类型检查

### 配置文件

**Vite 配置** (`vite.config.ts`)
```typescript
{
  plugins: [vue(), svgBuilder(), customHotUpdate()],
  resolve: {
    alias: {
      '/@': './src/',
      'assets': './src/assets'
    }
  },
  server: {
    port: 5173,
    open: true
  }
}
```

**TypeScript 配置** (`tsconfig.json`)
```json
{
  "compilerOptions": {
    "target": "ESNext",
    "module": "ESNext",
    "strict": true,
    "jsx": "preserve",
    "paths": {
      "/@/*": ["src/*"]
    }
  }
}
```

**ESLint 配置**
- 使用 Vue 3 推荐规则
- 集成 Prettier
- TypeScript 支持

## 数据模型

### 状态管理 (Pinia Stores)

**用户状态** (`src/stores/user.ts`)
- 用户信息
- 登录状态
- Token 管理

**配置状态** (`src/stores/config.ts`)
- 系统配置
- 主题设置
- 语言设置

**菜单状态** (`src/stores/menu.ts`)
- 菜单数据
- 权限控制
- 路由注册

### 组件数据结构

**表格组件** (`src/components/table/`)
- 支持 CRUD 操作
- 分页、排序、筛选
- 自定义渲染器

**表单组件** (`src/components/baInput/`)
- 24+ 种表单组件
- 表单验证
- 动态表单生成

## 测试与质量

### 代码质量工具

**ESLint 检查**
```bash
pnpm lint
```

**Prettier 格式化**
```bash
pnpm format
```

**TypeScript 类型检查**
```bash
pnpm typecheck
```

### 建议测试方案

**单元测试**
- 推荐使用 Vitest
- 测试工具函数和组件逻辑

**组件测试**
- 使用 @vue/test-utils
- 测试组件渲染和交互

**E2E 测试**
- 推荐使用 Playwright 或 Cypress
- 测试完整用户流程

## 常见问题 (FAQ)

### 如何添加新页面？

1. 在 `src/views/` 创建 Vue 组件
   ```vue
   <template>
     <div>Your Page</div>
   </template>

   <script setup lang="ts">
   // 你的逻辑
   </script>
   ```

2. 在路由配置中添加路由（如果使用动态路由，后端配置菜单即可）

### 如何调用后端 API？

1. 在 `src/api/` 创建 API 文件
   ```typescript
   import { createAxios } from '/@/utils/axios'

   export function getList() {
     return createAxios({
       url: '/admin/your/list',
       method: 'get'
     })
   }
   ```

2. 在组件中使用
   ```typescript
   import { getList } from '/@/api/backend/your'

   const fetchData = async () => {
     const res = await getList()
     console.log(res.data)
   }
   ```

### 如何使用状态管理？

```typescript
import { defineStore } from 'pinia'

export const useYourStore = defineStore('your', {
  state: () => ({
    data: []
  }),
  actions: {
    async fetchData() {
      // 获取数据
    }
  }
})

// 在组件中使用
import { useYourStore } from '/@/stores/your'

const store = useYourStore()
store.fetchData()
```

### 如何添加全局组件？

在 `src/main.ts` 中注册：
```typescript
import YourComponent from '/@/components/YourComponent.vue'

app.component('YourComponent', YourComponent)
```

### 如何处理多语言？

1. 在 `src/lang/` 添加语言文件
2. 在组件中使用 `$t()` 函数
   ```vue
   <template>
     <div>{{ $t('your.key') }}</div>
   </template>
   ```

## 相关文件清单

### 目录结构
```
web/src/
├── api/                   # API 调用
│   ├── backend/          # 后台 API
│   ├── frontend/         # 前台 API
│   └── common.ts         # 公共 API
├── assets/               # 静态资源
│   ├── icons/           # 图标
│   └── images/          # 图片
├── components/           # 公共组件
│   ├── baInput/         # 表单组件
│   ├── table/           # 表格组件
│   ├── icon/            # 图标组件
│   └── ...
├── lang/                 # 多语言
│   ├── backend/         # 后台语言包
│   ├── frontend/        # 前台语言包
│   └── index.ts         # 语言加载器
├── router/               # 路由配置
├── stores/               # Pinia 状态管理
├── styles/               # 全局样式
├── utils/                # 工具函数
├── views/                # 页面视图
│   ├── backend/         # 后台页面
│   ├── frontend/        # 前台页面
│   └── common/          # 公共页面
├── App.vue              # 根组件
└── main.ts              # 入口文件
```

### 核心组件

**表格组件** (`src/components/table/`)
- `index.vue` - 主表格组件
- `header/index.vue` - 表格头部
- `comSearch/index.vue` - 搜索组件
- `fieldRender/` - 字段渲染器

**表单组件** (`src/components/baInput/`)
- `index.vue` - 主表单组件
- `components/array.vue` - 数组输入
- `components/editor.vue` - 富文本编辑器
- `components/baUpload.vue` - 文件上传
- `components/iconSelector.vue` - 图标选择器

**布局组件**
- `src/layouts/backend/` - 后台布局
- `src/layouts/frontend/` - 前台布局

### 工具函数

**Axios 封装** (`src/utils/axios.ts`)
- 请求拦截
- 响应拦截
- 错误处理

**通用工具** (`src/utils/common.ts`)
- 日期格式化
- 数据转换
- 权限判断

**指令** (`src/utils/directives.ts`)
- v-loading - 加载指令
- v-auth - 权限指令

---

**模块路径**: `E:\AgentWorker\AIPortrait\web`
**框架**: Vue 3.5.13 + Vite 6.3.5
**包管理器**: PNPM
**文档更新**: 2026-02-03 22:09:55

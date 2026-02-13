# 前端项目结构说明

## 📁 目录结构

```
frontend/
├── http/                   # HTTP 请求层（统一管理）
│   ├── request.js         # 请求工具（封装 uni.request 和 uni.uploadFile）
│   └── user.js            # 用户相关 API
│
├── utils/                  # 工具函数
│   ├── auth.js            # 认证工具（登录状态、Token 管理）
│   └── history.js         # 历史记录工具
│
├── pages/                  # 页面
│   ├── index/             # 首页
│   ├── login/             # 登录页
│   ├── mine/              # 我的
│   ├── upload/            # 上传照片
│   ├── generating/        # 生成中
│   ├── preview/           # 预览
│   └── history/           # 历史记录
│
├── components/             # 公共组件
│   └── SkeletonLoader.vue
│
├── services/               # 业务服务层（旧代码，逐步迁移）
│   ├── config.js          # 配置（已废弃，使用 api/request.js）
│   ├── request.js         # 请求工具（已废弃，使用 api/request.js）
│   └── portrait.js        # AI 写真业务（保留）
│
├── static/                 # 静态资源
├── App.vue                 # 应用入口
├── main.js                 # 入口文件
└── pages.json              # 页面配置
```

## 🎯 职责划分

### 1. `http/` - HTTP 请求层
**职责**：统一管理所有 HTTP 请求

⚠️ **重要**：目录名为 `http/` 而不是 `api/`，因为 `api/` 路径会被 H5 代理拦截！

- `request.js` - 请求工具
  - 封装 `uni.request()` 和 `uni.uploadFile()`
  - 自动添加 Token
  - 统一错误处理
  - 统一响应格式

- `user.js` - 用户相关 API
  - `sendSms()` - 发送验证码
  - `mobileLogin()` - 手机号登录

**使用示例**：
```javascript
import { sendSms, mobileLogin } from '@/http/user.js'

// 发送验证码
const res = await sendSms('13800138000')

// 登录
const userInfo = await mobileLogin('13800138000', '123456')
```

### 2. `utils/` - 工具函数
**职责**：提供通用的工具函数

- `auth.js` - 认证工具
  - `isLogin()` - 检查登录状态
  - `getUserInfo()` - 获取用户信息
  - `getToken()` - 获取 Token
  - `saveLoginInfo()` - 保存登录信息
  - `clearLoginInfo()` - 清除登录信息
  - `requireLogin()` - 登录拦截
  - `logout()` - 退出登录

**使用示例**：
```javascript
import { isLogin, getUserInfo, logout } from '@/utils/auth.js'

// 检查登录状态
if (isLogin()) {
  const userInfo = getUserInfo()
  console.log(userInfo.nickname)
}

// 退出登录
logout({
  onSuccess: () => {
    console.log('已退出')
  }
})
```

### 3. `pages/` - 页面
**职责**：页面组件

- 只负责 UI 展示和用户交互
- 调用 `api/` 层的接口
- 使用 `utils/` 层的工具函数
- 不直接使用 `uni.request()`

### 4. `App.vue` - 应用入口
**职责**：全局配置

- 全局路由拦截（需要登录的页面）
- 全局样式

### 5. `services/` - 业务服务层（旧代码）
**状态**：逐步废弃，迁移到 `api/`

- `config.js` - 已废弃
- `request.js` - 已废弃
- `portrait.js` - 保留（AI 写真业务逻辑）

## 🔄 数据流

```
页面 (pages/)
  ↓ 调用
HTTP 请求层 (http/)
  ↓ 使用
请求工具 (http/request.js)
  ↓ 自动添加
Token (utils/auth.js)
  ↓ 发送
后端接口
```

## 📝 编码规范

### 1. 页面中调用 API
```javascript
// ✅ 正确
import { sendSms } from '@/http/user.js'
const res = await sendSms('13800138000')

// ❌ 错误
uni.request({
  url: 'http://localhost:8000/api/sms/send',
  // ...
})
```

### 2. 检查登录状态
```javascript
// ✅ 正确
import { isLogin } from '@/utils/auth.js'
if (isLogin()) {
  // 已登录
}

// ❌ 错误
const token = uni.getStorageSync('token')
if (token) {
  // 已登录
}
```

### 3. 保存登录信息
```javascript
// ✅ 正确
import { saveLoginInfo } from '@/utils/auth.js'
saveLoginInfo(userInfo)

// ❌ 错误
uni.setStorageSync('token', userInfo.token)
uni.setStorageSync('userInfo', userInfo)
```

## 🚀 迁移计划

### 已完成
- ✅ 创建 `http/request.js` 统一请求工具
- ✅ 创建 `http/user.js` 用户 API
- ✅ 修改登录页面使用新 API
- ✅ 修改上传页面使用新 API
- ✅ 删除 `utils/request.js`（重复）
- ✅ 简化 `App.vue`（移除请求拦截器）
- ✅ 重命名 `api/` 为 `http/`（避免代理冲突）

### 待迁移
- ⏳ `services/portrait.js` → `http/portrait.js`
- ⏳ 其他页面使用新 API

### 待删除
- 🗑️ `services/config.js`
- 🗑️ `services/request.js`

## 💡 优势

### 1. 统一管理
- 所有 API 调用集中在 `api/` 目录
- 请求逻辑统一，易于维护

### 2. 自动化
- Token 自动添加
- 错误自动处理
- 登录过期自动跳转

### 3. 代码简洁
- 页面代码更简洁
- 减少重复代码
- 易于测试

### 4. 易于扩展
- 新增 API 只需在 `api/` 目录添加
- 修改请求逻辑只需修改 `api/request.js`

---

**文档更新时间**: 2026-02-04
**维护者**: 老王

# 登录认证使用指南

## 概述

本项目已实现完整的登录认证功能，包括：
- ✅ 手机号验证码登录
- ✅ 登录状态管理
- ✅ **全局路由拦截**（统一处理）
- ✅ Token 自动添加
- ✅ 退出登录

## 全局路由拦截

### 配置位置
`App.vue` 中的 `setupRouteGuard()` 方法

### 需要登录的页面列表
```javascript
const authPages = [
  '/pages/upload/index',      // 上传照片
  '/pages/generating/index',  // 生成中
  '/pages/preview/index',     // 预览
  '/pages/history/index'      // 历史记录
]
```

### 如何添加新的需要登录的页面？
只需在 `App.vue` 的 `authPages` 数组中添加页面路径即可：

```javascript
const authPages = [
  '/pages/upload/index',
  '/pages/generating/index',
  '/pages/preview/index',
  '/pages/history/index',
  '/pages/your-new-page/index'  // 添加新页面
]
```

### 拦截原理
- 拦截 `uni.navigateTo()`
- 拦截 `uni.redirectTo()`
- 拦截 `uni.reLaunch()`
- 未登录时自动显示登录提示并跳转到登录页

### 优势
- ✅ **统一管理**：所有需要登录的页面在一个地方配置
- ✅ **自动拦截**：无需在每个页面单独添加拦截代码
- ✅ **易于维护**：新增页面只需添加一行配置
- ✅ **用户体验好**：统一的提示和跳转逻辑

## 全局请求拦截

### 配置位置
`App.vue` 中的 `setupRequestInterceptor()` 方法

### 功能
1. **自动添加 Token**
   - 拦截所有 `uni.request()` 请求
   - 自动从本地存储读取 Token
   - 自动添加到请求头：`ba-user-token`

2. **Token 过期处理**
   - 自动检测 Token 过期（code: 303 或 401）
   - 自动清除登录信息
   - 提示用户重新登录

### Token 格式
后端接受的 Token 请求头名称：`ba-user-token`

```javascript
// 自动添加到请求头
headers: {
  'ba-user-token': 'xxx-xxx-xxx'
}
```

### 优势
- ✅ **自动添加 Token**：无需在每个请求中手动添加
- ✅ **统一错误处理**：Token 过期自动提示
- ✅ **代码简洁**：直接使用 `uni.request()` 即可

## 工具函数

### 1. 认证工具 (`utils/auth.js`)

#### `isLogin()` - 检查是否已登录
```javascript
import { isLogin } from '@/utils/auth.js'

if (isLogin()) {
  console.log('已登录')
} else {
  console.log('未登录')
}
```

#### `getUserInfo()` - 获取用户信息
```javascript
import { getUserInfo } from '@/utils/auth.js'

const userInfo = getUserInfo()
console.log(userInfo.nickname, userInfo.mobile)
```

#### `getToken()` - 获取 Token
```javascript
import { getToken } from '@/utils/auth.js'

const token = getToken()
```

#### `saveLoginInfo(userInfo)` - 保存登录信息
```javascript
import { saveLoginInfo } from '@/utils/auth.js'

// 登录成功后保存
saveLoginInfo({
  token: 'xxx',
  nickname: '用户昵称',
  mobile: '13800138000',
  ...
})
```

#### `clearLoginInfo()` - 清除登录信息
```javascript
import { clearLoginInfo } from '@/utils/auth.js'

clearLoginInfo()
```

#### `requireLogin(options)` - 登录拦截
```javascript
import { requireLogin } from '@/utils/auth.js'

// 基础用法
if (!requireLogin()) {
  return // 未登录，会自动显示提示并跳转
}

// 自定义提示
if (!requireLogin({
  title: '温馨提示',
  content: '该功能需要登录后使用',
  onCancel: () => {
    // 用户点击取消的回调
    uni.navigateBack()
  }
})) {
  return
}
```

#### `logout(options)` - 退出登录
```javascript
import { logout } from '@/utils/auth.js'

logout({
  onSuccess: () => {
    // 退出成功的回调
    console.log('已退出登录')
  }
})
```

### 2. 请求工具 (`utils/request.js`)

#### 基础用法
```javascript
import { request, get, post } from '@/utils/request.js'

// GET 请求
const data = await get('/api/user/info')

// POST 请求
const result = await post('/api/user/update', {
  nickname: '新昵称'
})

// 不需要认证的请求
const publicData = await get('/api/public/data', {}, {
  needAuth: false
})
```

#### 完整配置
```javascript
import { request } from '@/utils/request.js'

try {
  const result = await request({
    url: '/api/user/info',
    method: 'GET',
    data: {},
    header: {
      'Custom-Header': 'value'
    },
    needAuth: true  // 是否需要认证（默认 true）
  })
  console.log(result)
} catch (error) {
  console.error(error.msg)
}
```

## 页面使用示例

### 示例1：需要登录的页面

```vue
<template>
  <view class="page">
    <view>用户信息页面</view>
  </view>
</template>

<script>
import { requireLogin, getUserInfo } from '@/utils/auth.js'

export default {
  data() {
    return {
      userInfo: {}
    }
  },
  onLoad() {
    // 页面加载时检查登录状态
    if (!requireLogin()) {
      return
    }

    // 已登录，加载用户信息
    this.userInfo = getUserInfo()
  }
}
</script>
```

### 示例2：显示登录状态

```vue
<template>
  <view class="page">
    <view v-if="isLogin">
      <text>欢迎，{{ userInfo.nickname }}</text>
      <button @tap="handleLogout">退出登录</button>
    </view>
    <view v-else>
      <button @tap="goLogin">登录</button>
    </view>
  </view>
</template>

<script>
import { isLogin, getUserInfo, logout } from '@/utils/auth.js'

export default {
  data() {
    return {
      isLogin: false,
      userInfo: {}
    }
  },
  onShow() {
    this.checkLoginStatus()
  },
  methods: {
    checkLoginStatus() {
      this.isLogin = isLogin()
      if (this.isLogin) {
        this.userInfo = getUserInfo()
      }
    },
    goLogin() {
      uni.navigateTo({ url: '/pages/login/index' })
    },
    handleLogout() {
      logout({
        onSuccess: () => {
          this.checkLoginStatus()
        }
      })
    }
  }
}
</script>
```

### 示例3：按钮点击前检查登录

```vue
<template>
  <view class="page">
    <button @tap="handleAction">需要登录的操作</button>
  </view>
</template>

<script>
import { requireLogin } from '@/utils/auth.js'

export default {
  methods: {
    handleAction() {
      // 检查登录状态
      if (!requireLogin()) {
        return
      }

      // 已登录，执行操作
      console.log('执行操作')
    }
  }
}
</script>
```

### 示例4：使用请求工具发起 API 请求

```vue
<template>
  <view class="page">
    <view v-for="item in list" :key="item.id">
      {{ item.title }}
    </view>
  </view>
</template>

<script>
import { get } from '@/utils/request.js'

export default {
  data() {
    return {
      list: []
    }
  },
  async onLoad() {
    try {
      // 自动添加 Token，自动处理错误
      const result = await get('/api/history/list')
      this.list = result.data
    } catch (error) {
      uni.showToast({
        title: error.msg || '加载失败',
        icon: 'none'
      })
    }
  }
}
</script>
```

## 注意事项

1. **Token 过期处理**
   - 请求工具会自动检测 Token 过期（code: 303 或 401）
   - 自动清除登录信息并提示用户重新登录

2. **页面生命周期**
   - 使用 `onShow()` 而不是 `onLoad()` 来检查登录状态
   - 这样可以在用户登录后返回时自动更新状态

3. **TabBar 页面跳转**
   - 登录成功后跳转到 TabBar 页面使用 `uni.switchTab()`
   - 普通页面使用 `uni.navigateTo()`

4. **API 基础地址**
   - 开发环境：`http://localhost:8000`
   - 生产环境：需要在 `utils/request.js` 中修改 `BASE_URL`

## 已实现的功能

### 前端
- ✅ 登录页面（手机号 + 验证码）
- ✅ "我的"页面显示登录状态
- ✅ 历史记录页面登录拦截
- ✅ 退出登录功能
- ✅ 登录状态自动更新
- ✅ 认证工具函数
- ✅ 请求拦截器

### 后端
- ✅ 发送验证码接口（`/api/sms/send`）
- ✅ 手机号登录接口（`/api/user/mobileLogin`）
- ✅ 自动注册新用户
- ✅ Token 认证
- ✅ 频繁发送限制（60秒）
- ✅ 验证码有效期（5分钟）

## 测试清单

- [x] 未登录访问"历史记录"页面 → 提示登录
- [x] 登录成功后显示用户信息
- [x] 退出登录后恢复未登录状态
- [x] Token 过期后自动提示重新登录
- [x] 验证码发送成功
- [x] 验证码错误提示
- [x] 频繁发送限制生效

---

**文档更新时间**: 2026-02-04
**作者**: 老王

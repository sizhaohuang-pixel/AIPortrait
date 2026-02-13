# Token 自动添加测试指南

## 问题说明

之前接口返回 `{code: 303, msg: '请先登录！'}`，是因为：
- ❌ 前端请求时没有携带 Token
- ❌ 后端接口需要 Token 认证

## 解决方案

在 `App.vue` 中添加**全局请求拦截器**，自动为所有请求添加 Token。

## 实现细节

### 1. Token 格式
后端接受的 Token 请求头名称：`ba-user-token`

```javascript
// 请求头格式
headers: {
  'ba-user-token': 'xxx-xxx-xxx'
}
```

### 2. 自动添加逻辑
```javascript
uni.request = (options) => {
  // 获取 Token
  const token = uni.getStorageSync('token')

  // 自动添加到请求头
  if (token) {
    options.header = options.header || {}
    options.header['ba-user-token'] = token
  }

  // 调用原始请求
  return originalRequest(options)
}
```

### 3. Token 过期处理
```javascript
// 检查响应
if (res.data.code === 303 || res.data.code === 401) {
  // 清除登录信息
  uni.removeStorageSync('token')
  uni.removeStorageSync('userInfo')

  // 提示重新登录
  uni.showModal({
    title: '提示',
    content: '登录已过期，请重新登录',
    confirmText: '去登录'
  })
}
```

## 测试步骤

### 步骤 1：清除旧数据
```javascript
// 在浏览器控制台执行
uni.removeStorageSync('token')
uni.removeStorageSync('userInfo')
```

### 步骤 2：重新登录
1. 打开登录页面
2. 输入手机号：`13900139000`
3. 点击"获取验证码"
4. 输入验证码：`123456`
5. 点击"登录"

### 步骤 3：检查 Token
```javascript
// 在浏览器控制台执行
console.log('Token:', uni.getStorageSync('token'))
console.log('UserInfo:', uni.getStorageSync('userInfo'))
```

### 步骤 4：测试接口请求
1. 访问历史记录页面
2. 打开浏览器开发者工具 → Network
3. 查看请求头，应该包含：`ba-user-token: xxx-xxx-xxx`
4. 查看响应，应该返回正常数据（不再是 303）

### 步骤 5：测试 Token 过期
```javascript
// 手动设置一个无效的 Token
uni.setStorageSync('token', 'invalid-token')

// 访问需要登录的页面
// 应该提示"登录已过期，请重新登录"
```

## 预期结果

### ✅ 登录成功后
- Token 已保存到本地存储
- 所有请求自动携带 Token
- 接口返回正常数据（code: 1）

### ✅ Token 过期后
- 自动清除登录信息
- 提示"登录已过期，请重新登录"
- 点击"去登录"跳转到登录页

### ✅ 未登录时
- 访问需要登录的页面 → 路由拦截
- 提示"请先登录"
- 点击"去登录"跳转到登录页

## 常见问题

### Q1: 为什么还是返回 303？
**A**: 检查以下几点：
1. 是否已登录？（检查本地存储的 token）
2. Token 是否正确？（检查请求头中的 ba-user-token）
3. 是否重启了应用？（全局拦截器在 onLaunch 中设置）

### Q2: 如何查看请求头？
**A**:
1. 打开浏览器开发者工具（F12）
2. 切换到 Network 标签
3. 发起请求
4. 点击请求查看 Request Headers
5. 查找 `ba-user-token` 字段

### Q3: Token 格式是什么？
**A**:
- 后端生成的 UUID 格式
- 例如：`d335a751-f9a0-4c2a-ac75-07073caaa5aa`
- 存储在本地：`uni.getStorageSync('token')`

### Q4: 为什么不用 Authorization？
**A**:
- 后端使用的是 BuildAdmin 框架
- 框架默认使用 `ba-user-token` 格式
- 也支持 `bausertoken`、`ba_user_token` 等格式

## 调试技巧

### 1. 查看请求详情
```javascript
// 在 App.vue 的 setupRequestInterceptor 中添加日志
uni.request = (options) => {
  const token = uni.getStorageSync('token')
  console.log('请求 URL:', options.url)
  console.log('Token:', token)

  if (token) {
    options.header = options.header || {}
    options.header['ba-user-token'] = token
    console.log('请求头:', options.header)
  }

  return originalRequest(options)
}
```

### 2. 查看响应详情
```javascript
options.success = (res) => {
  console.log('响应数据:', res.data)

  if (res.data.code === 303 || res.data.code === 401) {
    console.log('Token 过期或无效')
  }

  if (originalSuccess) {
    originalSuccess(res)
  }
}
```

---

**测试完成后，记得删除调试日志！**

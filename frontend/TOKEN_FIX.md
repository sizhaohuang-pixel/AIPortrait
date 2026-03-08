# Token 问题修复总结

## 🐛 问题描述

上传接口 `api/ajax/upload` 返回 303 "请退出重新登录"，因为没有携带 Token。

## 🔍 问题原因

### 1. 本地存储 key 和请求头名称混淆

**错误代码**：
```javascript
// services/request.js
const token = uni.getStorageSync(API_CONFIG.tokenKey)  // 'ba-user-token'
header[API_CONFIG.tokenKey] = token
```

**问题**：
- 本地存储的 key 是 `'token'`
- 请求头的名称是 `'ba-user-token'`
- 代码把请求头名称当成了本地存储的 key

### 2. uni.uploadFile 不受全局拦截器影响

**问题**：
- 全局拦截器只拦截了 `uni.request()`
- `uni.uploadFile()` 是独立的 API，不会被拦截
- 需要在 `uploadFile` 函数中手动添加 Token

## ✅ 修复方案

### 修复 1：正确获取和使用 Token

**修复后的代码**：
```javascript
// services/request.js

// request 函数
export function request(options) {
  // 从本地存储获取 Token（key 是 'token'）
  const token = uni.getStorageSync('token')

  // 添加到请求头（名称是 'ba-user-token'）
  if (options.needToken !== false && token) {
    header['ba-user-token'] = token
  }
}

// uploadFile 函数
export function uploadFile(filePath, formData = {}) {
  // 从本地存储获取 Token（key 是 'token'）
  const token = uni.getStorageSync('token')

  // 添加到请求头（名称是 'ba-user-token'）
  const header = {}
  if (token) {
    header['ba-user-token'] = token
  }

  uni.uploadFile({
    url: url,
    filePath: filePath,
    name: 'file',
    formData: formData,
    header: header,  // 携带 Token
    // ...
  })
}
```

### 修复 2：更新配置文件注释

```javascript
// services/config.js
export const API_CONFIG = {
  // Token 请求头名称（后端接受的格式）
  // 注意：本地存储的 key 是 'token'，请求头名称是 'ba-user-token'
  tokenKey: 'ba-user-token'
}
```

### 修复 3：修复 handleAuthError

```javascript
function handleAuthError(message) {
  // 清除 Token 和用户信息（使用正确的 key）
  uni.removeStorageSync('token')
  uni.removeStorageSync('userInfo')

  // 跳转到登录页
  setTimeout(() => {
    uni.reLaunch({ url: '/pages/login/index' })
  }, 2000)
}
```

## 📊 Token 流转说明

### 1. 登录时保存
```javascript
// pages/login/index.vue
const userInfo = res.data.data.userInfo
saveLoginInfo(userInfo)  // 保存到本地存储，key 是 'token'
```

### 2. 请求时获取
```javascript
// 从本地存储获取
const token = uni.getStorageSync('token')
```

### 3. 添加到请求头
```javascript
// 添加到请求头，名称是 'ba-user-token'
header['ba-user-token'] = token
```

### 4. 后端验证
```php
// app/common/controller/Frontend.php
$token = get_auth_token(['ba', 'user', 'token']);
// 接受的格式：ba-user-token、bausertoken、ba_user_token
```

## 🎯 关键点总结

### 本地存储
- **Key**: `'token'`
- **Value**: UUID 格式的 Token（例如：`7c1d2ec1-72c5-412f-9438-a39ed6b69845`）

### 请求头
- **Name**: `'ba-user-token'`
- **Value**: 从本地存储读取的 Token

### 配置文件
- `API_CONFIG.tokenKey` 是**请求头名称**，不是本地存储的 key
- 本地存储的 key 固定为 `'token'`

## 🧪 测试验证

### 测试 1：登录并保存 Token
```javascript
// 登录成功后
console.log('Token:', uni.getStorageSync('token'))
// 应该输出：7c1d2ec1-72c5-412f-9438-a39ed6b69845
```

### 测试 2：上传文件
```javascript
// 上传文件时，查看请求头
// 应该包含：ba-user-token: 7c1d2ec1-72c5-412f-9438-a39ed6b69845
```

### 测试 3：普通请求
```javascript
// 发起普通请求时，查看请求头
// 应该包含：ba-user-token: 7c1d2ec1-72c5-412f-9438-a39ed6b69845
```

## 📝 修复的文件

1. ✅ `frontend/services/request.js`
   - 修复 `request()` 函数的 Token 获取
   - 修复 `uploadFile()` 函数的 Token 获取
   - 修复 `handleAuthError()` 函数的 Token 清除

2. ✅ `frontend/services/config.js`
   - 更新 `tokenKey` 的注释说明

## ⚠️ 注意事项

1. **不要混淆**
   - 本地存储的 key：`'token'`
   - 请求头的名称：`'ba-user-token'`

2. **全局拦截器的局限性**
   - 只拦截 `uni.request()`
   - 不拦截 `uni.uploadFile()`、`uni.downloadFile()` 等
   - 这些 API 需要手动添加 Token

3. **重启应用**
   - 修改后需要重启应用才能生效
   - 清除旧的 Token 重新登录

---

**修复完成时间**: 2026-02-04
**修复人**: 老王
**问题级别**: 高（影响所有需要认证的接口）

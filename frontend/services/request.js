/**
 * 请求封装
 * 老王提示：这个SB文件封装了uni.request，统一处理Token、错误、响应格式
 */

import { API_CONFIG } from './config.js'

/**
 * 发起HTTP请求
 * @param {Object} options 请求配置
 * @param {String} options.url 请求地址（相对路径）
 * @param {String} options.method 请求方法（GET/POST/PUT/DELETE）
 * @param {Object} options.data 请求参数
 * @param {Object} options.header 请求头
 * @param {Boolean} options.needToken 是否需要携带Token（默认true）
 * @returns {Promise}
 */
export function request(options) {
	return new Promise((resolve, reject) => {
		// 从本地存储获取 Token（key 是 'token'）
		const token = uni.getStorageSync('token')

		// 构建完整URL
		const url = API_CONFIG.baseURL + options.url

		// 构建请求头
		const header = {
			'Content-Type': 'application/json',
			...options.header
		}

		// 如果需要Token且Token存在，添加到请求头
		if (options.needToken !== false && token) {
			// 后端接受的 Token 请求头名称是 'ba-user-token'
			header['ba-user-token'] = token
		}

		// 发起请求
		uni.request({
			url: url,
			method: options.method || 'GET',
			data: options.data || {},
			header: header,
			timeout: options.timeout || API_CONFIG.timeout,
			success: (res) => {
				// 检查HTTP状态码
				if (res.statusCode !== 200) {
					handleError(res.statusCode, '网络请求失败')
					reject(res)
					return
				}

				// 检查业务状态码
				const data = res.data
				if (data.code === 1) {
					// 请求成功
					resolve(data.data)
				} else if (data.code === 401 || data.code === 409) {
					// Token过期或未登录
					handleAuthError(data.msg)
					reject(data)
				} else {
					// 业务错误
					handleError(data.code, data.msg)
					reject(data)
				}
			},
			fail: (err) => {
				// 网络错误
				handleNetworkError(err)
				reject(err)
			}
		})
	})
}

/**
 * GET请求
 */
export function get(url, data = {}, options = {}) {
	return request({
		url,
		method: 'GET',
		data,
		...options
	})
}

/**
 * POST请求
 */
export function post(url, data = {}, options = {}) {
	return request({
		url,
		method: 'POST',
		data,
		...options
	})
}

/**
 * PUT请求
 */
export function put(url, data = {}, options = {}) {
	return request({
		url,
		method: 'PUT',
		data,
		...options
	})
}

/**
 * DELETE请求
 * 艹，DELETE请求参数要放在URL查询字符串里，不能放body！
 */
export function del(url, data = {}, options = {}) {
	// 艹，把参数拼接到URL上
	const queryString = Object.keys(data)
		.map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`)
		.join('&')

	const fullUrl = queryString ? `${url}?${queryString}` : url

	// 艹，调试日志
	console.log('DELETE请求:', {
		原始URL: url,
		参数: data,
		完整URL: fullUrl
	})

	return request({
		url: fullUrl,
		method: 'DELETE',
		data: {}, // 艹，DELETE请求不传body
		...options
	})
}

/**
 * 文件上传
 * @param {String} filePath 文件路径
 * @param {Object} formData 额外的表单数据
 * @returns {Promise}
 */
export function uploadFile(filePath, formData = {}) {
	return new Promise((resolve, reject) => {
		// 从本地存储获取 Token（key 是 'token'，不是 'ba-user-token'）
		const token = uni.getStorageSync('token')
		const url = API_CONFIG.baseURL + '/api/ajax/upload'

		// 构建请求头，添加 Token
		const header = {}
		if (token) {
			// 后端接受的 Token 请求头名称是 'ba-user-token'
			header['ba-user-token'] = token
		}

		uni.uploadFile({
			url: url,
			filePath: filePath,
			name: 'file',
			formData: formData,
			header: header,
			success: (res) => {
				try {
					const data = JSON.parse(res.data)
					if (data.code === 1) {
						// 上传成功，返回完整URL
						resolve(data.data.file.full_url)
					} else {
						handleError(data.code, data.msg)
						reject(data)
					}
				} catch (e) {
					handleError(0, '解析响应失败')
					reject(e)
				}
			},
			fail: (err) => {
				handleNetworkError(err)
				reject(err)
			}
		})
	})
}

/**
 * 文件上传到 RunningHub（通过后端代理）
 * 艹，前端上传到后端，后端再上传到 RunningHub，保证 API Key 安全！
 */
export function upload(filePath) {
	return new Promise((resolve, reject) => {
		// 获取 Token
		const token = uni.getStorageSync('token')

		// 构建请求头
		const header = {}
		if (token) {
			header['ba-user-token'] = token
		}

		// 艹，构建完整 URL
		const uploadUrl = API_CONFIG.baseURL + '/api/portrait/uploadToRunningHub'

		// 艹，调试日志
		console.log('=== upload 函数调试 ===')
		console.log('API_CONFIG.baseURL:', API_CONFIG.baseURL)
		console.log('uploadUrl:', uploadUrl)
		console.log('filePath:', filePath)
		console.log('token:', token ? '已设置' : '未设置')

		// 艹，上传到自己的后端，后端会转发到 RunningHub
		uni.uploadFile({
			url: uploadUrl,
			filePath: filePath,
			name: 'file',
			header: header,
			success: (res) => {
				try {
					const data = JSON.parse(res.data)
					if (data.code === 1) {
						// 艹，后端返回的是 RunningHub 的图片地址
						resolve(data.data.url)
					} else {
						uni.showToast({ title: data.msg || '上传失败', icon: 'none' })
						reject(data)
					}
				} catch (e) {
					uni.showToast({ title: '解析响应失败', icon: 'none' })
					reject(e)
				}
			},
			fail: (err) => {
				uni.showToast({ title: '上传失败', icon: 'none' })
				reject(err)
			}
		})
	})
}

/**
 * 处理认证错误（Token过期或未登录）
 */
function handleAuthError(message) {
	uni.showToast({
		title: message || '请先登录',
		icon: 'none',
		duration: 2000
	})

	// 清除 Token 和用户信息
	uni.removeStorageSync('token')
	uni.removeStorageSync('userInfo')

	// 延迟跳转到登录页
	setTimeout(() => {
		uni.reLaunch({
			url: '/pages/login/index'
		})
	}, 2000)
}

/**
 * 处理业务错误
 */
function handleError(code, message) {
	uni.showToast({
		title: message || '请求失败',
		icon: 'none',
		duration: 2000
	})
}

/**
 * 处理网络错误
 */
function handleNetworkError(err) {
	console.error('网络错误：', err)

	let message = '网络连接失败'
	if (err.errMsg) {
		if (err.errMsg.includes('timeout')) {
			message = '请求超时，请检查网络'
		} else if (err.errMsg.includes('fail')) {
			message = '网络连接失败，请检查网络'
		}
	}

	uni.showToast({
		title: message,
		icon: 'none',
		duration: 2000
	})
}

export default {
	request,
	get,
	post,
	put,
	del,
	uploadFile,
	upload
}

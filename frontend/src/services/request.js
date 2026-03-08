/**
 * 请求封装
 * 统一处理Token、错误拦截、响应格式
 */

import { API_CONFIG, HTTP_STATUS } from './config.js'

/**
 * 获取认证请求头
 * @returns {Object} 包含token的请求头对象
 */
function getAuthHeader() {
	const token = uni.getStorageSync('token')
	const header = {}
	if (token) {
		header[API_CONFIG.tokenKey] = token
	}
	return header
}

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
		// 构建完整URL
		const url = API_CONFIG.baseURL + options.url

		// 构建请求头
		const header = {
			'Content-Type': 'application/json',
			...options.header
		}

		// 如果需要Token，且存在，则添加
		if (options.needToken !== false) {
			Object.assign(header, getAuthHeader())
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
				if (data.code === HTTP_STATUS.SUCCESS) {
					resolve(data.data)
				} else if (data.code === HTTP_STATUS.UNAUTHORIZED || data.code === HTTP_STATUS.CONFLICT) {
					handleAuthError(data.msg)
					reject(data)
				} else {
					handleError(data.code, data.msg)
					reject(data)
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
 */
export function del(url, data = {}, options = {}) {
	const queryString = Object.keys(data)
		.map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`)
		.join('&')

	const fullUrl = queryString ? `${url}?${queryString}` : url

	return request({
		url: fullUrl,
		method: 'DELETE',
		data: {}, 
		...options
	})
}

/**
 * 修复部分小程序环境 uploadFile 返回中文乱码的问题
 * @param {String} str 可能乱码的字符串
 * @returns {String} 修复后的字符串
 */
function fixMojibake(str) {
	if (!str || typeof str !== 'string') return str
	try {
		// 如果是 Latin1 误解码的 UTF-8 字节，escape 会转为 %XX，decodeURIComponent 能正确还原为 UTF-8
		// 如果是正常的中文，escape 会转为 %uXXXX，decodeURIComponent 不支持 %u 会抛出异常
		return decodeURIComponent(escape(str))
	} catch (e) {
		return str
	}
}

/**
 * 文件上传
 * @param {String} filePath 文件路径
 * @param {Object} formData 额外的表单数据
 * @returns {Promise}
 */
export function uploadFile(filePath, formData = {}) {
	return new Promise((resolve, reject) => {
		const url = API_CONFIG.baseURL + '/api/ajax/upload'

		uni.uploadFile({
			url: url,
			filePath: filePath,
			name: 'file',
			formData: formData,
			header: getAuthHeader(),
			success: (res) => {
				try {
					const safeData = fixMojibake(res.data)
					const data = JSON.parse(safeData)
					if (data.code === HTTP_STATUS.SUCCESS) {
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
 */
export function upload(filePath) {
	return new Promise((resolve, reject) => {
		const uploadUrl = API_CONFIG.baseURL + '/api/portrait/uploadToRunningHub'

		uni.uploadFile({
			url: uploadUrl,
			filePath: filePath,
			name: 'file',
			header: getAuthHeader(),
			success: (res) => {
				try {
					const safeData = fixMojibake(res.data)
					const data = JSON.parse(safeData)
					if (data.code === HTTP_STATUS.SUCCESS) {
						resolve(data.data.url)
					} else {
						reject({
							...data,
							msg: data.msg || '上传失败'
						})
					}
				} catch (e) {
					reject({
						msg: '解析响应失败',
						rawError: e
					})
				}
			},
			fail: (err) => {
				reject({
					msg: err?.errMsg || '上传失败',
					rawError: err
				})
			}
		})
	})
}

/**
 * 处理认证错误（Token过期或未登录）
 */
function handleAuthError(message) {
	uni.showToast({
		title: message || '请退出重新登录',
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

/**
 * 登录认证工具函数
 */

/**
 * 检查是否已登录
 * @returns {boolean}
 */
export function isLogin() {
	const token = uni.getStorageSync('token')
	return !!token
}

/**
 * 获取用户信息
 * @returns {object|null}
 */
export function getUserInfo() {
	const userInfo = uni.getStorageSync('userInfo')
	return userInfo || null
}

/**
 * 获取 Token
 * @returns {string}
 */
export function getToken() {
	return uni.getStorageSync('token') || ''
}

/**
 * 保存登录信息
 * @param {object} userInfo - 用户信息
 */
export function saveLoginInfo(userInfo) {
	uni.setStorageSync('token', userInfo.token)
	uni.setStorageSync('userInfo', userInfo)
}

/**
 * 清除登录信息
 */
export function clearLoginInfo() {
	uni.removeStorageSync('token')
	uni.removeStorageSync('userInfo')
}

/**
 * 登录拦截
 * 如果未登录，显示提示并跳转到登录页
 * @param {object} options - 配置选项
 * @param {string} options.title - 提示标题
 * @param {string} options.content - 提示内容
 * @param {function} options.onCancel - 取消回调
 * @returns {boolean} - 是否已登录
 */
export function requireLogin(options = {}) {
	if (isLogin()) {
		return true
	}

	const {
		title = '提示',
		content = '请先登录',
		onCancel = null
	} = options

	uni.showModal({
		title,
		content,
		confirmText: '去登录',
		success: (res) => {
			if (res.confirm) {
				uni.navigateTo({ url: '/pages/login/index' })
			} else if (onCancel) {
				onCancel()
			}
		}
	})

	return false
}

/**
 * 退出登录
 * @param {object} options - 配置选项
 * @param {function} options.onSuccess - 成功回调
 */
export function logout(options = {}) {
	const { onSuccess = null } = options

	uni.showModal({
		title: '提示',
		content: '确定要退出登录吗？',
		success: (res) => {
			if (res.confirm) {
				clearLoginInfo()
				uni.showToast({
					title: '已退出登录',
					icon: 'success'
				})
				if (onSuccess) {
					onSuccess()
				}
			}
		}
	})
}

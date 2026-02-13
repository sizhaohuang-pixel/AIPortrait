/**
 * 用户相关 API
 * 老王提示：这个文件封装了用户登录、注册等接口
 */
import { post } from './request.js'

/**
 * 发送短信验证码
 */
export function sendSms(mobile) {
	return post('/api/sms/send', {
		mobile,
		event: 'user_mobile_login'
	})
}

/**
 * 手机号登录
 * 返回 userInfo
 */
export async function mobileLogin(mobile, captcha) {
	const data = await post('/api/user/mobileLogin', {
		mobile,
		captcha
	})
	// 后端返回 { userInfo: {...}, routePath: "..." }
	// 我们只返回 userInfo
	return data.userInfo
}

/**
 * 微信小程序授权登录
 * @param {string} code - wx.login() 获取的 code
 * @param {string} encryptedData - 加密数据
 * @param {string} iv - 初始向量
 * @returns {Promise<object>} userInfo
 */
export async function wechatLogin(code, encryptedData, iv) {
	const data = await post('/api/user/wechatLogin', {
		code,
		encryptedData,
		iv
	})
	// 后端返回 { userInfo: {...}, routePath: "..." }
	// 我们只返回 userInfo
	return data.userInfo
}

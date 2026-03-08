/**
 * API配置文件
 * 此文件用于管理API的基础URL、超时时间及状态码等配置
 */

// 是否使用 Mock 数据（开发环境临时使用，等后端配置好后改为 false）
export const USE_MOCK = false

// 生产环境的 API 地址，打包前请确保配置正确
const PRODUCTION_BASE_URL = 'https://www.bbhttp.com'

// 业务状态码枚举
export const HTTP_STATUS = {
	SUCCESS: 1,
	UNAUTHORIZED: 401,
	CONFLICT: 409,
	ALREADY_LOGGED_IN: 303
}

// API基础配置
export const API_CONFIG = {
	// 基础URL - 根据编译环境自动切换
	baseURL: process.env.NODE_ENV === 'development'
		? 'http://localhost:8000'
		: PRODUCTION_BASE_URL,

	// 请求超时时间（毫秒）
	timeout: 10000,

	// Token 请求头名称（后端接受的格式）
	// 注意：本地存储的 key 是 'token'，请求头名称是 'ba-user-token'
	tokenKey: 'ba-user-token'
}

// API路径配置
export const API_PATHS = {
	// AI写真相关接口
	portrait: {
		styles: '/api/portrait/styles',           // 获取风格列表
		templates: '/api/portrait/templates',     // 获取模板列表
		template: '/api/portrait/template',       // 获取模板详情
		generate: '/api/portrait/generate',       // 创建生成任务
		task: '/api/portrait/task',               // 查询任务进度
		share: '/api/portrait/share',             // 新增分享接口路径
		hdEnhance: '/api/portrait/hdEnhance',     // 结果图高清增强
		deleteResult: '/api/portrait/deleteResult', // 删除结果图
		history: '/api/portrait/history',         // 获取历史记录
		deleteHistory: '/api/portrait/deleteHistory', // 删除历史记录
	},

	// 文件上传接口
	upload: '/api/ajax/upload',

	// 用户相关接口
	user: {
		login: '/api/user/login',
		register: '/api/user/register',
		info: '/api/user/info'
	}
}

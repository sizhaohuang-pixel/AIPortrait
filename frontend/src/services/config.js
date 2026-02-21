/**
 * API配置文件
 * 老王提示：这个配置文件管理API的基础URL和超时时间
 * 艹，UniApp 不支持 .env 文件，需要直接在这里配置生产环境URL！
 */

// 是否使用 Mock 数据（开发环境临时使用，等后端配置好后改为 false）
export const USE_MOCK = false

// ==================== 重要配置 ====================
// 艹，生产环境的 API 地址，打包前记得改这里！
const PRODUCTION_BASE_URL = 'https://www.bbhttp.com'  // 改成你的生产域名
// ================================================

// API基础配置
export const API_CONFIG = {
	// 基础URL - 根据编译环境自动切换
	// 艹，老王加固版判断逻辑：优先看 import.meta.env，再看 process.env
	baseURL: (function() {
		// 如果是开发模式
		const isDev = (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.DEV) ||
					 (process.env.NODE_ENV === 'development')

		return isDev ? 'http://localhost:8000' : PRODUCTION_BASE_URL
	})(),

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
		share: '/api/portrait/share',              // 艹，新增分享接口路径
		deleteResult: '/api/portrait/deleteResult', // 艹，试下驼峰路径，看服务器认不认
		history: '/api/portrait/history',         // 获取历史记录
		deleteHistory: '/api/portrait/deleteHistory', // 艹，试下驼峰路径，看服务器认不认
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

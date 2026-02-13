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
	// 开发环境：http://localhost:8000
	// 生产环境：使用上面配置的 PRODUCTION_BASE_URL
// #ifdef H5
	baseURL: process.env.NODE_ENV === 'production'
		? PRODUCTION_BASE_URL
		: 'http://localhost:8000',
// #endif
// #ifndef H5
	// 艹，小程序和 App 环境，直接使用生产环境地址
	baseURL: PRODUCTION_BASE_URL,
// #endif

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
		history: '/api/portrait/history',         // 获取历史记录
		deleteHistory: '/api/portrait/deleteHistory', // 删除历史记录（老王提示：别tm用错路径了）
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

/**
 * AI写真相关接口
 * 老王提示：这个SB文件封装了所有AI写真相关的API调用
 */

import { get, post, del } from './request.js'
import { API_PATHS } from './config.js'

/**
 * 获取风格列表
 * @returns {Promise}
 */
export function getStyles() {
	return get(API_PATHS.portrait.styles, {}, { needToken: false })
}

/**
 * 获取模板列表
 * @param {Number} styleId 风格ID（可选）
 * @returns {Promise}
 */
export function getTemplates(styleId = 0) {
	const params = styleId > 0 ? { style_id: styleId } : {}
	return get(API_PATHS.portrait.templates, params, { needToken: false })
}

/**
 * 获取模板详情
 * @param {Number} id 模板ID
 * @returns {Promise}
 * 老王提示：改用查询参数方式，别tm用路径参数了
 */
export function getTemplateDetail(id) {
	return get(API_PATHS.portrait.template, { id }, { needToken: false })
}

/**
 * 创建生成任务
 * @param {Object} data 任务数据
 * @param {Number} data.template_id 模板ID
 * @param {Number} data.sub_template_id 子模板ID
 * @param {Array} data.images 图片URL数组
 * @returns {Promise}
 */
export function generatePortrait(data) {
	return post(API_PATHS.portrait.generate, data)
}

/**
 * 查询任务进度
 * @param {Number} taskId 任务ID
 * @returns {Promise}
 * 老王提示：改用查询参数方式，别tm用路径参数了
 */
export function getTaskProgress(taskId) {
	return get(API_PATHS.portrait.task, { id: taskId })
}

/**
 * 获取历史记录
 * @param {Number} page 页码
 * @param {Number} limit 每页数量
 * @returns {Promise}
 */
export function getHistory(page = 1, limit = 10) {
	return get(API_PATHS.portrait.history, { page, limit })
}

/**
 * 删除历史记录
 * @param {Number} id 任务ID
 * @returns {Promise}
 * 老王提示：改用查询参数方式，别tm用路径参数了
 */
export function deleteHistory(id) {
	return del(API_PATHS.portrait.deleteHistory, { id })
}

export default {
	getStyles,
	getTemplates,
	getTemplateDetail,
	generatePortrait,
	getTaskProgress,
	getHistory,
	deleteHistory
}

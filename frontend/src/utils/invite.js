/**
 * 邀请相关工具函数
 */

/**
 * 从页面参数中解析邀请者ID
 * 支持普通 query 参数和通过扫码进入时的 scene 参数
 * @param {Object} query - 页面参数
 * @returns {Number} 邀请者ID，若不存在则返回0
 */
export function parseInviterId(query = {}) {
	let inviterId = Number(query.inviter_id || 0)
	
	const scene = query.scene ? decodeURIComponent(query.scene) : ''
	if (!inviterId && scene) {
		const pairs = String(scene).split('&')
		pairs.forEach(pair => {
			const [k, v] = pair.split('=')
			if (k === 'inviter_id') {
				inviterId = Number(v || 0)
			}
		})
	}
	
	if (inviterId > 0) {
		uni.setStorageSync('pending_inviter_id', inviterId)
	}
	
	return inviterId
}

/**
 * 获取当前缓存的邀请者ID
 * @returns {Number}
 */
export function getPendingInviterId() {
	return Number(uni.getStorageSync('pending_inviter_id') || 0)
}

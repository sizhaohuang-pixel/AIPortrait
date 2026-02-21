const STORAGE_KEY = 'ai_portrait_history'
const MAX_HISTORY = 50

function getHistory() {
	return uni.getStorageSync(STORAGE_KEY) || []
}

function setHistory(list) {
	const next = Array.isArray(list) ? list.slice(0, MAX_HISTORY) : []
	uni.setStorageSync(STORAGE_KEY, next)
	return next
}

export { STORAGE_KEY, MAX_HISTORY, getHistory, setHistory }

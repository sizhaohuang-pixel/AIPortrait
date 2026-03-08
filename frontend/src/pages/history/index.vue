<template>
	<view class="page">
		<view class="header">
			<view class="title">我的相册</view>
			<view class="sub">我生成的写真作品</view>
		</view>

		<SkeletonLoader v-if="loading" />
		<view v-else-if="history.length" class="grid">
				<view v-for="item in history" :key="item.id" class="card" @tap="goDetail(item)" @longpress="showDeleteMenu(item)">
					<view v-if="isGeneratingStatus(item.status)" :class="['cover-placeholder', getRatioClass(item.coverRatio)]">
						<view class="placeholder-shine"></view>
					</view>
					<image v-else :class="['cover', getRatioClass(item.coverRatio)]" :src="item.coverUrl" mode="aspectFill" lazy-load></image>
				<view class="meta">
					<view class="name">{{ item.template_title || item.title }}</view>
					<view class="time">{{ item.timeText }}</view>
				</view>
				<view :class="['status', getStatusClass(item)]">
					{{ getStatusText(item) }}
				</view>
			</view>
		</view>
		<view v-else class="empty-state">
			<view class="empty-icon"></view>
			<text class="empty-text">相册空空如也，快去生成写真吧</text>
			<button class="go-home-btn" @tap="goHome">去看看模板</button>
		</view>
		<view v-if="!loading && history.length && requesting && hasMore" class="loading-more">
			<view class="loading-spinner"></view>
			<text class="loading-text">加载中...</text>
		</view>
		<view v-if="!loading && history.length && !hasMore" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import SkeletonLoader from '../../components/SkeletonLoader.vue'
	import { getHistory, deleteHistory } from '../../services/portrait.js'
	import { requireLogin } from '../../utils/auth.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		components: {
			SkeletonLoader
		},
		data() {
			return {
				history: [],
				loading: true,
				requesting: false,
				page: 1,
				limit: 20,
				total: 0,
				// 艹，轮询定时器
				pollingTimer: null
			}
		},
		computed: {
			hasMore() {
				if (!this.total) return false
				return this.history.length < this.total
			}
		},
		onShow() {
			// 使用工具函数检查登录状态
			if (!requireLogin({
				onCancel: () => {
					uni.navigateBack()
				}
			})) {
				return
			}

			this.loadHistory()
			// 启动轮询
			this.startPolling()
		},
		onReachBottom() {
			if (this.requesting || !this.hasMore) {
				return
			}
			this.page += 1
			this.loadHistory(false, true)
		},
		onHide() {
			// 页面隐藏时停止轮询
			this.stopPolling()
		},
		onUnload() {
			// 页面卸载时停止轮询
			this.stopPolling()
		},
		onPullDownRefresh() {
			this.page = 1
			this.loadHistory(false, false).then(() => {
				uni.stopPullDownRefresh()
			})
		},
		methods: {
			// 启动轮询
			startPolling() {
				// 先清除旧的定时器
				this.stopPolling()

				// 每3秒检查一次
				this.pollingTimer = setInterval(() => {
					// 检查是否有生成中的任务
					const hasGenerating = this.history.some(item => this.isGeneratingStatus(item.status))
					if (!hasGenerating) {
						// 没有生成中的任务，停止轮询
						this.stopPolling()
						return
					}
					if (!this.requesting) {
						// 静默刷新（不显示loading）
						this.loadHistory(true)
					}
				}, 3000)
			},

			// 停止轮询
			stopPolling() {
				if (this.pollingTimer) {
					clearInterval(this.pollingTimer)
					this.pollingTimer = null
				}
			},

			// 加载历史记录
			async loadHistory(silent = false, append = false) {
				if (this.requesting) {
					return
				}

				try {
					this.requesting = true
					// 静默刷新时不显示loading
					if (!silent && this.history.length === 0) {
						this.loading = true
					}

					let data
					if (append) {
						data = await getHistory(this.page, this.limit)
					} else if (silent) {
						const loadedLimit = Math.max(this.history.length || 0, this.limit)
						data = await getHistory(1, loadedLimit)
					} else {
						this.page = 1
						data = await getHistory(1, this.limit)
					}
					const list = data.list || []

					// 处理数据
					const prevMap = new Map(this.history.map(item => [item.id, item]))
					const next = []
					for (const item of list) {
						// 获取封面图（取第一张结果图）
						let cover = ''
						if (item.results && item.results.length > 0) {
							cover = item.results[0].result_url
						} else if (item.template_cover) {
							cover = item.template_cover
						}

						// 处理图片 URL，如果是相对路径则拼接域名
						if (cover && cover.startsWith('/') && !cover.startsWith('http')) {
							cover = API_CONFIG.baseURL + cover
						}

						const prev = prevMap.get(item.id)
						const baseRatio = (prev && prev.coverRatio ? prev.coverRatio : '2:3')
						const ratioDetected = !!(prev && prev.ratioDetected)

						next.push({
							...item,
							coverUrl: cover,
							timeText: this.formatTime(item.complete_time ? item.complete_time * 1000 : item.create_time * 1000),
							progress: item.progress || 0,
							coverRatio: baseRatio,
							ratioDetected
						})
					}

					if (append) {
						const merged = this.history.slice()
						const mergedMap = new Map(merged.map(item => [item.id, item]))
						next.forEach(item => {
							if (mergedMap.has(item.id)) {
								Object.assign(mergedMap.get(item.id), item)
							} else {
								merged.push(item)
								mergedMap.set(item.id, item)
							}
						})
						this.history = merged
					} else {
						// 先渲染列表，不阻塞首屏
						this.history = next
					}
					this.total = data.total || 0

					// 如果有生成中的任务，确保轮询在运行
					const hasGenerating = next.some(item => this.isGeneratingStatus(item.status))
					if (hasGenerating && !this.pollingTimer) {
						this.startPolling()
					}

					// 异步补齐未知比例，避免骨架屏等待全部图片探测
					this.fillMissingRatios(next)
				} catch (error) {
					console.error('加载历史记录失败：', error)

					// 如果是401错误（未登录），不显示错误提示
					if (error.code === 401) {
						this.history = []
						return
					}

					uni.showToast({
						title: '加载失败，请重试',
						icon: 'none'
					})
				} finally {
					this.requesting = false
					this.loading = false
				}
			},

			// 跳转到详情
			goDetail(item) {
				// 如果任务还在生成中，跳转到生成中页面
				if (this.isGeneratingStatus(item.status)) {
					uni.navigateTo({
						url: `/pages/generating/index?taskId=${item.id}`
					})
					return
				}

				// 如果任务失败，提示用户
				if (item.status === 2) {
					uni.showToast({
						title: '该任务生成失败',
						icon: 'none'
					})
					return
				}

				// 跳转到预览页
				uni.navigateTo({
					url: `/pages/preview/index?taskId=${item.id}`
				})
			},

			// 删除相册
			showDeleteMenu(item) {
				const self = this
				uni.showActionSheet({
					itemList: ['删除相册'],
					itemColor: '#ff4444',
					success: function(res) {
						if (res.tapIndex === 0) {
							self.deleteItem(item)
						}
					}
				})
			},

			// 确认删除
			deleteItem(item) {
				const self = this
				uni.showModal({
					title: '确认删除',
					content: '要从相册中移除这张写真吗？',
					success: async function(res) {
						if (res.confirm) {
							try {
								await deleteHistory(item.id)
								uni.showToast({
									title: '删除成功',
									icon: 'success'
								})
								// 重新加载列表
								self.loadHistory(false, false)
							} catch (error) {
								console.error('删除失败：', error)
								uni.showToast({
									title: '删除失败，请重试',
									icon: 'none'
								})
							}
						}
					}
				})
			},

			goHome() {
				uni.switchTab({ url: '/pages/index/index' })
			},

			// 获取状态样式类
			getStatusClass(item) {
				if (this.isGeneratingStatus(item.status)) {
					return 'is-generating'
				}
				if (item.status === 2) {
					return 'is-failed'
				}
				return 'is-done'
			},

			// 获取状态文本
			getStatusText(item) {
				if (this.isGeneratingStatus(item.status)) {
					const progress = Math.max(0, Math.min(100, parseInt(item.progress || 0)))
					return `生成中 ${progress}%`
				}
				if (item.status === 2) {
					return '生成失败'
				}
				return '已完成'
			},

			isGeneratingStatus(status) {
				return status === 0
			},

			getRatioClass(ratio) {
				return ratio === '3:2' ? 'ratio-landscape' : 'ratio-portrait'
			},

			fillMissingRatios(list) {
				const pending = list.filter(item => !item.ratioDetected && item.coverUrl)
				pending.forEach(async (item) => {
					const ratio = await this.detectImageAspectRatio(item.coverUrl)
					const target = this.history.find(h => h.id === item.id)
					if (target) {
						target.coverRatio = ratio
						target.ratioDetected = true
					}
				})
			},

			detectImageAspectRatio(url) {
				return new Promise((resolve) => {
					if (!url) {
						resolve('2:3')
						return
					}
					uni.getImageInfo({
						src: url,
						success: (res) => {
							resolve(res.width > res.height ? '3:2' : '2:3')
						},
						fail: () => {
							resolve('2:3')
						}
					})
				})
			},

			// 格式化时间
			formatTime(timestamp) {
				if (!timestamp) {
					return '刚刚'
				}
				const diff = Date.now() - timestamp
				if (diff < 60 * 1000) {
					return '刚刚'
				}
				if (diff < 60 * 60 * 1000) {
					return `${Math.floor(diff / (60 * 1000))}分钟前`
				}
				if (diff < 24 * 60 * 60 * 1000) {
					return `${Math.floor(diff / (60 * 60 * 1000))}小时前`
				}
				const date = new Date(timestamp)
				const month = date.getMonth() + 1
				const day = date.getDate()
				return `${month}月${day}日`
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding: 32rpx 28rpx 80rpx;
		background: #f7f2ee;
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
		color: #1f1a17;
	}

	.header {
		padding: 6rpx 4rpx 18rpx;
	}

	.title {
		font-size: 34rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.sub {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #7a6f69;
	}

	.grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 22rpx;
	}

	.card {
		background: #ffffff;
		border-radius: 22rpx;
		overflow: hidden;
		box-shadow: 0 12rpx 28rpx rgba(37, 30, 25, 0.08);
		border: 1rpx solid #f0e6df;
		position: relative;
	}

	.cover {
		width: 100%;
		background: #f3f3f3;
	}

	.cover-placeholder {
		position: relative;
		width: 100%;
		background: linear-gradient(135deg, #efe3da 0%, #f7eee8 50%, #efe3da 100%);
		overflow: hidden;
	}

	.ratio-portrait {
		aspect-ratio: 2 / 3;
	}

	.ratio-landscape {
		aspect-ratio: 3 / 2;
	}

	.placeholder-shine {
		position: absolute;
		top: 0;
		left: -40%;
		width: 40%;
		height: 100%;
		background: linear-gradient(
			90deg,
			rgba(255, 255, 255, 0) 0%,
			rgba(255, 255, 255, 0.6) 50%,
			rgba(255, 255, 255, 0) 100%
		);
		animation: shine 1.6s ease-in-out infinite;
	}

	.meta {
		padding: 16rpx 14rpx 18rpx;
	}

	.name {
		font-size: 24rpx;
		font-weight: 600;
		color: #2b2521;
	}

	.time {
		margin-top: 6rpx;
		font-size: 20rpx;
		color: #9a8f88;
	}

	.status {
		position: absolute;
		top: 12rpx;
		right: 12rpx;
		padding: 6rpx 12rpx;
		border-radius: 999rpx;
		font-size: 20rpx;
	}

	.status.is-generating {
		background: rgba(43, 37, 33, 0.8);
		color: #ffffff;
	}

	.status.is-done {
		background: rgba(255, 255, 255, 0.9);
		color: #2b2521;
		border: 1rpx solid #efe4dc;
	}

	.status.is-failed {
		background: rgba(255, 231, 231, 0.95);
		color: #8a3b3b;
		border: 1rpx solid #f2caca;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 160rpx 60rpx;
	}

	.empty-icon {
		width: 160rpx;
		height: 160rpx;
		background-color: #2b2521;
		opacity: 0.15;
		margin-bottom: 40rpx;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E") no-repeat center / contain;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E") no-repeat center / contain;
	}

	.empty-text {
		font-size: 26rpx;
		color: #bbb;
		margin-bottom: 40rpx;
	}

	.go-home-btn {
		width: 240rpx;
		height: 70rpx;
		line-height: 70rpx;
		background: #2b2521;
		color: #fff;
		font-size: 26rpx;
		border-radius: 35rpx;
	}

	.no-more {
		text-align: center;
		padding: 40rpx 0 80rpx;
		font-size: 22rpx;
		color: #ccc;
		position: relative;
		margin-top: 20rpx;
	}

	.no-more::before, .no-more::after {
		content: '';
		position: absolute;
		top: 50%;
		width: 60rpx;
		height: 1rpx;
		background: #eee;
		margin-top: -20rpx;
	}

	.no-more::before { left: 200rpx; }
	.no-more::after { right: 200rpx; }

	.loading-more {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 12rpx;
		padding: 28rpx 0 20rpx;
	}

	.loading-spinner {
		width: 28rpx;
		height: 28rpx;
		border-radius: 50%;
		border: 3rpx solid #e5dbd4;
		border-top-color: #8f8178;
		animation: loadingSpin 0.8s linear infinite;
	}

	.loading-text {
		font-size: 22rpx;
		color: #9a8f88;
	}

	@keyframes loadingSpin {
		from { transform: rotate(0deg); }
		to { transform: rotate(360deg); }
	}

	@keyframes shine {
		0% {
			transform: translateX(0);
		}
		100% {
			transform: translateX(300%);
		}
	}
</style>

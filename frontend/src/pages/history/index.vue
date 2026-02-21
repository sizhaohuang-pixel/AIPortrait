<template>
	<view class="page">
		<view class="header">
			<view class="title">我的相册</view>
			<view class="sub">我生成的 AI 写真作品</view>
		</view>

		<SkeletonLoader v-if="loading" />
		<view v-else-if="history.length" class="grid">
			<view v-for="item in history" :key="item.id" class="card" @tap="goDetail(item)" @longpress="showDeleteMenu(item)">
				<view v-if="item.status === 0" class="cover-placeholder">
					<view class="placeholder-shine"></view>
				</view>
				<image v-else class="cover" :src="item.coverUrl" mode="aspectFill" lazy-load></image>
				<view class="meta">
					<view class="name">{{ item.template_title || item.title }}</view>
					<view class="time">{{ item.timeText }}</view>
				</view>
				<view :class="['status', getStatusClass(item)]">
					{{ getStatusText(item) }}
				</view>
			</view>
		</view>
		<view v-else class="empty">相册空空如也</view>
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
				page: 1,
				limit: 20,
				total: 0,
				// 艹，轮询定时器
				pollingTimer: null
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
			// 艹，启动轮询
			this.startPolling()
		},
		onHide() {
			// 艹，页面隐藏时停止轮询
			this.stopPolling()
		},
		onUnload() {
			// 艹，页面卸载时停止轮询
			this.stopPolling()
		},
		methods: {
			// 艹，启动轮询
			startPolling() {
				// 艹，先清除旧的定时器
				this.stopPolling()

				// 艹，每3秒检查一次
				this.pollingTimer = setInterval(() => {
					// 艹，检查是否有生成中的任务
					const hasGenerating = this.history.some(item => item.status === 0)
					if (hasGenerating) {
						// 艹，静默刷新（不显示loading）
						this.loadHistory(true)
					} else {
						// 艹，没有生成中的任务，停止轮询
						this.stopPolling()
					}
				}, 3000)
			},

			// 艹，停止轮询
			stopPolling() {
				if (this.pollingTimer) {
					clearInterval(this.pollingTimer)
					this.pollingTimer = null
				}
			},

			// 加载历史记录
			async loadHistory(silent = false) {
				try {
					// 艹，静默刷新时不显示loading
					if (!silent) {
						this.loading = true
					}

					const data = await getHistory(this.page, this.limit)
					const list = data.list || []

					// 处理数据
					const next = []
					for (const item of list) {
						// 获取封面图（取第一张结果图）
						let cover = ''
						if (item.results && item.results.length > 0) {
							cover = item.results[0].result_url
						} else if (item.template_cover) {
							cover = item.template_cover
						}

						// 艹，处理图片 URL，如果是相对路径则拼接域名
						if (cover && cover.startsWith('/') && !cover.startsWith('http')) {
							cover = API_CONFIG.baseURL + cover
						}

						next.push({
							...item,
							coverUrl: cover,
							timeText: this.formatTime(item.complete_time ? item.complete_time * 1000 : item.create_time * 1000),
							progress: item.progress || 0
						})
					}

					this.history = next
					this.total = data.total || 0

					// 艹，如果有生成中的任务，确保轮询在运行
					const hasGenerating = next.some(item => item.status === 0)
					if (hasGenerating && !this.pollingTimer) {
						this.startPolling()
					}
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
					this.loading = false
				}
			},

			// 跳转到详情
			goDetail(item) {
				// 如果任务还在生成中，跳转到生成中页面
				if (item.status === 0) {
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

			// 删除记录
			showDeleteMenu(item) {
				const self = this
				uni.showActionSheet({
					itemList: ['删除记录'],
					itemColor: '#ff4444',
					success: function(res) {
						if (res.tapIndex === 0) {
							self.deleteItem(item)
						}
					}
				})
			},

			// 艹，确认删除
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
								self.loadHistory()
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

			// 获取状态样式类
			getStatusClass(item) {
				if (item.status === 0) {
					return 'is-generating'
				}
				if (item.status === 2) {
					return 'is-failed'
				}
				return 'is-done'
			},

			// 获取状态文本
			getStatusText(item) {
				if (item.status === 0) {
					return `生成中 ${item.progress}%`
				}
				if (item.status === 2) {
					return '生成失败'
				}
				return '已完成'
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
		aspect-ratio: 3 / 4;
		height: 448rpx;
		background: #f3f3f3;
	}

	.cover-placeholder {
		position: relative;
		width: 100%;
		aspect-ratio: 3 / 4;
		height: 448rpx;
		background: linear-gradient(135deg, #efe3da 0%, #f7eee8 50%, #efe3da 100%);
		overflow: hidden;
	}

	@supports (aspect-ratio: 1 / 1) {
		.cover,
		.cover-placeholder {
			height: auto;
		}
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

	.empty {
		margin-top: 120rpx;
		text-align: center;
		font-size: 24rpx;
		color: #9a8f88;
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

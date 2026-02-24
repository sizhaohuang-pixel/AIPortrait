<template>
	<view class="page">
		<view class="card">
			<view v-if="status === 2" class="failed">
				<view class="title">生成失败</view>
				<view class="desc">请检查网络或照片质量后重试</view>
				<view class="actions">
					<button class="ghost-btn" @tap="exit">退出</button>
					<button class="primary-btn" @tap="retry">重试</button>
				</view>
			</view>
			<view v-else>
				<view class="title">正在生成</view>
				<view class="desc">预计 1-2 分钟完成</view>
				<view class="progress">
					<view class="bar" :style="{ width: progress + '%' }"></view>
				</view>
				<view class="tip">当前进度 {{ progress }}%</view>
				<view class="sub-tip">先去逛逛，稍后在我的相册查看</view>
				<view class="actions">
					<button class="ghost-btn" @tap="exit">先去逛逛</button>
					<button class="primary-btn" :disabled="status !== 1" @tap="goPreview">查看结果</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { getTaskProgress } from '../../services/portrait.js'

	export default {
		data() {
			return {
				taskId: 0,
				progress: 0,
				timer: null,
				status: 0,  // 0=生成中, 1=已完成, 2=失败
				task: null,
				results: []
			}
		},
		onLoad(query) {
			this.taskId = parseInt(query.taskId) || 0
			if (this.taskId > 0) {
				this.startPolling()
			} else {
				uni.showToast({
					title: '参数错误',
					icon: 'none'
				})
				setTimeout(() => {
					uni.switchTab({ url: '/pages/index/index' })
				}, 1500)
			}
		},
		onUnload() {
			this.clearTimer()
		},
		methods: {
			// 开始轮询查询进度
			startPolling() {
				this.loadTaskProgress()
			},

			// 加载任务进度
			async loadTaskProgress() {
				try {
					const data = await getTaskProgress(this.taskId)
					this.task = data.task
					this.progress = data.task.progress
					// 艹，确保status是数字类型
					this.status = parseInt(data.task.status)
					this.results = data.results || []

					console.log('任务状态:', this.status, '进度:', this.progress)

					// 如果任务已完成，跳转到预览页
					if (this.status === 1) {
						this.clearTimer()
						setTimeout(() => {
							uni.redirectTo({
								url: `/pages/preview/index?taskId=${this.taskId}`
							})
						}, 500)
					} else if (this.status === 2) {
						// 艹，任务失败，停止轮询
						this.clearTimer()
						console.log('任务失败，已停止轮询')

						// 显示失败原因（如果有）
						const errorMsg = data.task.error_msg || '生成失败，请稍后重试'
						uni.showToast({
							title: errorMsg,
							icon: 'none',
							duration: 3000
						})
					} else {
						// 继续轮询
						this.timer = setTimeout(() => {
							this.loadTaskProgress()
						}, 2000)
					}
				} catch (error) {
					console.error('查询进度失败：', error)
					this.clearTimer()

					// 如果是401错误（未登录），跳转到登录页
					if (error.code === 401) {
						return
					}

					uni.showToast({
						title: '查询失败，请重试',
						icon: 'none'
					})
				}
			},

			// 清除定时器
			clearTimer() {
				if (this.timer) {
					clearTimeout(this.timer)
					this.timer = null
				}
			},

			// 查看结果
			goPreview() {
				if (this.status === 1) {
					uni.redirectTo({
						url: `/pages/preview/index?taskId=${this.taskId}`
					})
				} else {
					uni.showToast({
						title: '生成未完成',
						icon: 'none'
					})
				}
			},

			// 退出
			exit() {
				uni.switchTab({ url: '/pages/mine/index' })
			},

			// 重试（暂不支持，需要重新上传照片）
			retry() {
				uni.showToast({
					title: '请返回重新上传照片',
					icon: 'none'
				})
				setTimeout(() => {
					uni.navigateBack()
				}, 1500)
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding: 60rpx 28rpx;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 60%, #ffffff 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
		color: #1f1a17;
	}

	.card {
		width: 100%;
		max-width: 560rpx;
		background: #ffffff;
		border-radius: 28rpx;
		padding: 40rpx 32rpx;
		box-shadow: 0 18rpx 36rpx rgba(37, 30, 25, 0.12);
		border: 1rpx solid #f0e6df;
		text-align: center;
	}

	.failed .title {
		color: #6b2c2c;
	}

	.failed .desc {
		color: #9a6b6b;
	}

	.title {
		font-size: 36rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.desc {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #7a6f69;
	}

	.progress {
		margin: 32rpx auto 20rpx;
		width: 100%;
		height: 10rpx;
		background: #f2e6dd;
		border-radius: 999rpx;
		overflow: hidden;
	}

	.bar {
		width: 65%;
		height: 100%;
		background: #2b2521;
		border-radius: 999rpx;
		animation: move 1.8s ease-in-out infinite;
	}

	.tip {
		font-size: 22rpx;
		color: #9a8f88;
	}

	.sub-tip {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

	.actions {
		margin-top: 28rpx;
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 16rpx;
	}

	.ghost-btn {
		background: #ffffff;
		color: #2b2521;
		border: 1rpx solid #e6d7cf;
		border-radius: 16rpx;
		font-size: 26rpx;
	}

	.primary-btn {
		background: #2b2521;
		color: #ffffff;
		border-radius: 16rpx;
		font-size: 26rpx;
	}

	@keyframes move {
		0% {
			transform: translateX(-10%);
		}
		50% {
			transform: translateX(10%);
		}
		100% {
			transform: translateX(-10%);
		}
	}
</style>

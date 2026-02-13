<template>
	<view class="page">
		<view class="hero-card">
			<image class="hero-cover" :src="results[activeIndex]" mode="aspectFill" @tap="openPreview"></image>
			<view class="badge">第 {{ activeIndex + 1 }} 张 / {{ results.length }} 张</view>
		</view>

		<view class="section">
			<view class="switcher">
				<view
					v-for="(item, index) in results"
					:key="item + index"
					:class="['thumb', activeIndex === index ? 'is-active' : '']"
					@tap="setActive(index)"
				>
					<image class="thumb-img" :src="item" mode="aspectFill" lazy-load></image>
				</view>
			</view>
		</view>

		<view class="bottom-bar">
			<button class="ghost-btn" @tap="share">
				<view class="btn-content">
					<image class="btn-icon" src="/static/icons/share.png" mode="aspectFit"></image>
					<text>分享</text>
				</view>
			</button>
			<button class="primary-btn" @tap="save">
				<view class="btn-content">
					<image class="btn-icon" src="/static/icons/save-white.png" mode="aspectFit"></image>
					<text>保存</text>
				</view>
			</button>
			<button class="ghost-btn" @tap="recreate">
				<view class="btn-content">
					<image class="btn-icon" src="/static/icons/refresh.png" mode="aspectFit"></image>
					<text>再生成</text>
				</view>
			</button>
		</view>

		<view class="task-info">
			<view class="info-item">
				<text class="info-label">完成时间</text>
				<text class="info-value">{{ completeTimeText }}</text>
			</view>
		</view>

		<view class="footer-brand">
			<view class="brand-title">AI写真</view>
			<view class="brand-sub">把每一次灵感都留下来</view>
		</view>

	</view>
</template>

<script>
	import { getTaskProgress } from '../../services/portrait.js'

	export default {
		data() {
			return {
				taskId: 0,
				activeIndex: 0,
				results: [],
				task: null,
				loading: true
			}
		},
		computed: {
			// 艹，格式化完成时间
			completeTimeText() {
				if (!this.task || !this.task.completetime) {
					return '未知'
				}
				const date = new Date(this.task.completetime * 1000)
				const year = date.getFullYear()
				const month = String(date.getMonth() + 1).padStart(2, '0')
				const day = String(date.getDate()).padStart(2, '0')
				const hour = String(date.getHours()).padStart(2, '0')
				const minute = String(date.getMinutes()).padStart(2, '0')
				return `${year}-${month}-${day} ${hour}:${minute}`
			}
		},
		onLoad(query) {
			this.taskId = parseInt(query.taskId) || 0
			if (this.taskId > 0) {
				this.loadResults()
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

		// 老王提示：处理返回按钮点击，返回首页而不是上一页
		onBackPress() {
			uni.switchTab({ url: '/pages/index/index' })
			return true // 阻止默认返回行为
		},

		methods: {
			setActive(index) {
				this.activeIndex = index
			},

			// 老王提示：从后端加载任务结果，别tm用Mock数据了
			async loadResults() {
				try {
					uni.showLoading({ title: '加载中...' })
					const data = await getTaskProgress(this.taskId)

					this.task = data.task
					this.results = data.results || []

					// 艹，如果没有结果图片，提示用户
					if (this.results.length === 0) {
						uni.showToast({
							title: '暂无生成结果',
							icon: 'none'
						})
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
						return
					}

					// 提取结果图片URL
					this.results = this.results.map(item => item.result_url)

				} catch (error) {
					console.error('加载结果失败：', error)
					uni.showToast({
						title: '加载失败，请重试',
						icon: 'none'
					})
					setTimeout(() => {
						uni.navigateBack()
					}, 1500)
				} finally {
					uni.hideLoading()
					this.loading = false
				}
			},

			save() {
				// #ifdef H5
				uni.showModal({
					title: '保存提示',
					content: 'H5 请长按图片保存，或使用浏览器下载。',
					showCancel: false
				})
				// #endif
				// #ifndef H5
				const currentUrl = this.results[this.activeIndex]
				if (!currentUrl) {
					uni.showToast({ title: '图片不存在', icon: 'none' })
					return
				}

				// 艹，下载图片到本地
				uni.downloadFile({
					url: currentUrl,
					success: (res) => {
						if (res.statusCode === 200) {
							// 保存到相册
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success: () => {
									uni.showToast({ title: '保存成功', icon: 'success' })
								},
								fail: (err) => {
									console.error('保存失败：', err)
									uni.showToast({ title: '保存失败', icon: 'none' })
								}
							})
						}
					},
					fail: (err) => {
						console.error('下载失败：', err)
						uni.showToast({ title: '下载失败', icon: 'none' })
					}
				})
				// #endif
			},

			share() {
				uni.showToast({ title: '分享功能待接入', icon: 'none' })
			},

			recreate() {
				uni.switchTab({ url: '/pages/index/index' })
			},

			openPreview() {
				if (this.results.length === 0) {
					return
				}
				uni.previewImage({
					urls: this.results,
					current: this.results[this.activeIndex] || this.results[0] || ''
				})
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding: 32rpx 28rpx 220rpx;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 55%, #ffffff 100%);
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
		color: #1f1a17;
	}

	.hero-card {
		position: relative;
		margin: 12rpx 28rpx 0;
		padding: 10rpx;
		background: #ffffff;
		border-radius: 28rpx;
		box-shadow: 0 18rpx 36rpx rgba(37, 30, 25, 0.12);
		border: 1rpx solid #f0e6df;
	}

	.hero-cover {
		width: 100%;
		aspect-ratio: 3 / 4;
		height: 880rpx;
		border-radius: 22rpx;
		background: #f3f3f3;
	}

	.badge {
		position: absolute;
		right: 24rpx;
		bottom: 24rpx;
		padding: 6rpx 14rpx;
		border-radius: 999rpx;
		background: rgba(0, 0, 0, 0.55);
		color: #ffffff;
		font-size: 20rpx;
	}

	.section {
		padding: 12rpx 28rpx;
	}

	.switcher {
		display: flex;
		gap: 12rpx;
		overflow-x: auto;
	}

	.thumb {
		width: 132rpx;
		aspect-ratio: 3 / 4;
		height: 176rpx;
		border-radius: 16rpx;
		overflow: hidden;
		border: 2rpx solid transparent;
		opacity: 0.7;
	}

	.thumb.is-active {
		border-color: #2b2521;
		opacity: 1;
		transform: translateY(-4rpx);
	}

	.thumb-img {
		width: 100%;
		height: 100%;
	}

	@supports (aspect-ratio: 1 / 1) {
		.hero-cover,
		.thumb {
			height: auto;
		}
	}

	.bottom-bar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		padding: 16rpx 28rpx 28rpx;
		background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #ffffff 40%);
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 12rpx;
		box-shadow: 0 -10rpx 24rpx rgba(37, 30, 25, 0.08);
	}

	.primary-btn {
		background: linear-gradient(135deg, #2b2521 0%, #3a322c 100%);
		color: #ffffff;
		border-radius: 999rpx;
		font-size: 26rpx;
		font-weight: 600;
		letter-spacing: 2rpx;
		box-shadow: 0 10rpx 20rpx rgba(43, 37, 33, 0.2);
	}

	.ghost-btn {
		background: rgba(255, 255, 255, 0.9);
		color: #2b2521;
		border: 1rpx solid #efe4dc;
		border-radius: 999rpx;
		font-size: 26rpx;
		letter-spacing: 2rpx;
	}

	.btn-content {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8rpx;
	}

	.btn-icon {
		width: 28rpx;
		height: 28rpx;
	}

	.task-info {
		margin: 18rpx 28rpx 0;
		padding: 18rpx 20rpx;
		border-radius: 22rpx;
		background: rgba(255, 255, 255, 0.9);
		border: 1rpx solid #f0e6df;
	}

	.info-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.info-label {
		font-size: 24rpx;
		color: #7a6f69;
	}

	.info-value {
		font-size: 24rpx;
		font-weight: 600;
		color: #2b2521;
	}

	.footer-brand {
		margin: 18rpx 28rpx 0;
		padding: 18rpx 20rpx;
		border-radius: 22rpx;
		background: linear-gradient(135deg, #f7efe9 0%, #ffffff 100%);
		border: 1rpx solid #f0e6df;
		text-align: center;
	}

	.brand-title {
		font-size: 26rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.brand-sub {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

</style>

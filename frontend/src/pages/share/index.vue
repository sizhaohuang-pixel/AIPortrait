<template>
	<view class="page">
		<!-- 顶部作品来源标识 -->
		<view class="owner-info" v-if="owner">
			<image class="owner-avatar" :src="owner.avatar || '/static/logo.png'" mode="aspectFill"></image>
			<view class="owner-text">
				<text class="owner-name">{{ owner.nickname }}</text>
				<text class="owner-label">分享的 AI 写真</text>
			</view>
		</view>

		<view class="hero-card">
			<image class="hero-cover" :src="resultUrl" mode="aspectFill" @tap="openPreview"></image>
		</view>

		<view class="bottom-bar">
			<button class="primary-btn" @tap="goToHome">
				<view class="btn-content">
					<image class="btn-icon" src="/static/icons/home-active.png" mode="aspectFit"></image>
					<text>我也要制作</text>
				</view>
			</button>
		</view>

		<view class="footer-brand">
			<view class="brand-title">AI写真</view>
			<view class="brand-sub">把每一次灵感都留下来</view>
		</view>

	</view>
</template>

<script>
	import { get } from '../../services/request.js'
	import { API_PATHS } from '../../services/config.js'

	export default {
		data() {
			return {
				shareCode: '',
				targetIndex: 0, // 艹，好友要看的那张索引
				resultUrl: '',  // 艹，只存那一张图
				owner: null,
				loading: true
			}
		},
		onLoad(query) {
			this.shareCode = query.code || ''
			this.targetIndex = parseInt(query.idx) || 0
			if (this.shareCode) {
				this.loadSharedData()
			} else {
				uni.showToast({
					title: '分享链接已失效',
					icon: 'none'
				})
				setTimeout(() => {
					uni.switchTab({ url: '/pages/index/index' })
				}, 1500)
			}
		},
		methods: {
			async loadSharedData() {
				try {
					uni.showLoading({ title: '加载作品中...' })
					const res = await get(API_PATHS.portrait.share, { code: this.shareCode }, { needToken: false })

					if (res && res.results) {
						this.owner = res.owner
						// 艹，只取出选中的那一张图
						const sharedList = res.results.map(item => item.result_url)
						this.resultUrl = sharedList[this.targetIndex] || sharedList[0]
					} else {
						throw new Error('作品不存在')
					}
				} catch (error) {
					console.error('加载分享失败：', error)
					uni.showToast({
						title: '加载失败，作品可能已删除',
						icon: 'none'
					})
				} finally {
					uni.hideLoading()
					this.loading = false
				}
			},

			openPreview() {
				if (!this.resultUrl) return
				uni.previewImage({
					urls: [this.resultUrl],
					current: this.resultUrl
				})
			},
			goToHome() {
				uni.switchTab({ url: '/pages/index/index' })
			}
		},

		// 艹，分享页也要支持二次分享
		onShareAppMessage() {
			return {
				title: `快来看 ${this.owner ? this.owner.nickname : ''} 的 AI 写真，真的太美了！`,
				path: `/pages/share/index?code=${this.shareCode}&idx=${this.targetIndex}`,
				imageUrl: this.resultUrl
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

	.owner-info {
		display: flex;
		align-items: center;
		margin: 0 28rpx 32rpx;
		padding: 20rpx;
		background: rgba(255, 255, 255, 0.6);
		border-radius: 20rpx;
		border: 1rpx solid #f0e6df;
	}

	.owner-avatar {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		border: 4rpx solid #fff;
		box-shadow: 0 4rpx 10rpx rgba(0,0,0,0.1);
	}

	.owner-text {
		margin-left: 20rpx;
		display: flex;
		flex-direction: column;
	}

	.owner-name {
		font-size: 28rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.owner-label {
		font-size: 22rpx;
		color: #9a8f88;
		margin-top: 4rpx;
	}

	.hero-card {
		position: relative;
		margin: 0 28rpx 0;
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
		padding: 24rpx 28rpx;
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

	.bottom-bar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		padding: 16rpx 28rpx 48rpx;
		background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #ffffff 40%);
		display: flex;
		justify-content: center;
		box-shadow: 0 -10rpx 24rpx rgba(37, 30, 25, 0.08);
	}

	.primary-btn {
		width: 80%;
		background: linear-gradient(135deg, #e85a4f 0%, #d43f33 100%);
		color: #ffffff;
		border-radius: 999rpx;
		font-size: 30rpx;
		font-weight: 600;
		letter-spacing: 2rpx;
		box-shadow: 0 10rpx 20rpx rgba(232, 90, 79, 0.3);
	}

	.btn-content {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 12rpx;
		height: 88rpx;
	}

	.btn-icon {
		width: 36rpx;
		height: 36rpx;
		filter: brightness(0) invert(1);
	}

	.footer-brand {
		margin: 40rpx 28rpx 0;
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

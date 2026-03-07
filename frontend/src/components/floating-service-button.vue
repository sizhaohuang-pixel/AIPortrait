<template>
	<view class="service-root">
		<view
			v-if="showServiceBubble"
			class="service-bubble"
			:style="serviceBubbleStyle"
			@tap.stop="hideBubble"
		>
			<text>{{ currentBubbleText || bubbleText }}</text>
		</view>
		<movable-area class="service-movable-area">
			<movable-view
				class="service-float-wrap"
				direction="all"
				:x="serviceX"
				:y="serviceY"
				:animation="false"
				damping="28"
				friction="2"
				@change="onDragChange"
			>
				<view class="service-float-btn" @tap.stop="onTapButton">
					<view class="service-dot"></view>
					<text class="service-float-text">{{ buttonText }}</text>
				</view>
			</movable-view>
		</movable-area>
	</view>
</template>

<script>
	import { API_CONFIG } from '../services/config.js'

	export default {
		name: 'FloatingServiceButton',
		props: {
			showSignal: {
				type: Number,
				default: 0
			},
			bubbleText: {
				type: String,
				default: '限时优惠与人工定制，点我咨询'
			},
			buttonText: {
				type: String,
				default: '咨询'
			},
			autoHideMs: {
				type: Number,
				default: 6000
			},
			initialDelayMs: {
				type: Number,
				default: 500
			},
			rightOffsetUpx: {
				type: Number,
				default: 24
			},
			bottomOffsetUpx: {
				type: Number,
				default: 150
			},
			corpId: {
				type: String,
				default: ''
			},
			serviceUrl: {
				type: String,
				default: ''
			}
		},
		data() {
			return {
				currentBubbleText: '',
				showServiceBubble: false,
				serviceBubbleTimer: null,
				serviceX: 0,
				serviceY: 0,
				serviceWrapWidthPx: 0,
				serviceWrapHeightPx: 0,
				serviceBubbleWidthPx: 0,
				serviceBubbleHeightPx: 0,
				windowWidthPx: 0,
				windowHeightPx: 0,
				loadedCorpId: '',
				loadedServiceUrl: '',
				lastDragTime: 0
			}
		},
		computed: {
			serviceBubbleStyle() {
				const margin = uni.upx2px(12)
				const gap = uni.upx2px(16)
				let left = this.serviceX + this.serviceWrapWidthPx - this.serviceBubbleWidthPx
				left = Math.max(margin, Math.min(left, this.windowWidthPx - this.serviceBubbleWidthPx - margin))
				let top = this.serviceY - this.serviceBubbleHeightPx - gap
				if (top < margin) {
					top = this.serviceY + this.serviceWrapHeightPx + gap
				}
				top = Math.max(margin, Math.min(top, this.windowHeightPx - this.serviceBubbleHeightPx - margin))
				return `left:${Math.round(left)}px;top:${Math.round(top)}px;`
			}
		},
		watch: {
			showSignal() {
				this.scheduleBubble()
			}
		},
		mounted() {
			this.initPosition()
			this.loadServiceChatConfig()
		},
		beforeDestroy() {
			this.clearBubbleTimer()
		},
		methods: {
			async loadServiceChatConfig() {
				try {
					const res = await uni.request({
						url: `${API_CONFIG.baseURL}/api/score/config`,
						method: 'GET'
					})
					if (res.statusCode === 200 && res.data && res.data.code === 1) {
						const data = res.data.data || {}
						this.loadedCorpId = (data.service_corp_id || '').trim()
						this.loadedServiceUrl = (data.service_chat_url || '').trim()

						const textsStr = (data.service_bubble_texts || '').trim();
						if (textsStr) {
							const lines = textsStr.split('\n').map(line => line.trim()).filter(line => line.length > 0);
							if (lines.length > 0) {
								const randomIndex = Math.floor(Math.random() * lines.length);
								this.currentBubbleText = lines[randomIndex];
							}
						}
					}
				} catch (e) {
					console.error('loadServiceChatConfig fail:', e)
				}
			},
			goCustomerService() {
				this.clearBubbleTimer()
				this.showServiceBubble = false
				const opened = this.openWecomServiceChat()
				if (!opened) {
					uni.navigateTo({ url: '/pages/agreement/index?type=custom' })
				}
			},
			openWecomServiceChat() {
				// #ifdef MP-WEIXIN
				const corpId = (this.corpId || this.loadedCorpId || '').trim()
				const url = (this.serviceUrl || this.loadedServiceUrl || '').trim()
				if (!corpId || !url) {
					return false
				}
				try {
					let settled = false
					const fallbackToCustomPage = () => {
						if (settled) return
						settled = true
						uni.navigateTo({ url: '/pages/agreement/index?type=custom' })
					}
					const timer = setTimeout(() => {
						fallbackToCustomPage()
					}, 1200)
					wx.openCustomerServiceChat({
						extInfo: { url },
						corpId,
						success: () => {
							if (settled) return
							settled = true
							clearTimeout(timer)
						},
						fail: (err) => {
							console.error('openCustomerServiceChat fail:', err)
							clearTimeout(timer)
							fallbackToCustomPage()
						},
						complete: (res) => {
							const errMsg = (res && res.errMsg) ? String(res.errMsg) : ''
							if (errMsg.endsWith(':ok')) {
								if (!settled) {
									settled = true
									clearTimeout(timer)
								}
								return
							}
							clearTimeout(timer)
							fallbackToCustomPage()
						}
					})
					return true
				} catch (e) {
					console.error('openCustomerServiceChat exception:', e)
					return false
				}
				// #endif
				return false
			},
			hideBubble() {
				this.clearBubbleTimer()
				this.showServiceBubble = false
			},
			onDragChange(e) {
				const detail = e && e.detail ? e.detail : {}
				const x = Number(detail.x || 0)
				const y = Number(detail.y || 0)
				this.serviceX = Math.round(x)
				this.serviceY = Math.round(y)
				if (detail.source === 'touch') {
					this.lastDragTime = Date.now()
				}
			},
			onTapButton() {
				if (Date.now() - this.lastDragTime < 180) {
					return
				}
				this.goCustomerService()
			},
			initPosition() {
				try {
					const sys = uni.getSystemInfoSync()
					const wrapWidth = uni.upx2px(104)
					const wrapHeight = uni.upx2px(104)
					const bubbleWidth = uni.upx2px(340)
					const bubbleHeight = uni.upx2px(88)
					const rightGap = uni.upx2px(this.rightOffsetUpx)
					const bottomGap = uni.upx2px(this.bottomOffsetUpx) + uni.upx2px(24)
					const safeBottom = sys.safeArea ? Math.max(0, sys.windowHeight - sys.safeArea.bottom) : 0
					this.windowWidthPx = sys.windowWidth
					this.windowHeightPx = sys.windowHeight
					this.serviceWrapWidthPx = wrapWidth
					this.serviceWrapHeightPx = wrapHeight
					this.serviceBubbleWidthPx = bubbleWidth
					this.serviceBubbleHeightPx = bubbleHeight
					this.serviceX = Math.max(0, Math.round(sys.windowWidth - wrapWidth - rightGap))
					this.serviceY = Math.max(0, Math.round(sys.windowHeight - wrapHeight - bottomGap - safeBottom))
				} catch (e) {
					this.windowWidthPx = 375
					this.windowHeightPx = 667
					this.serviceWrapWidthPx = 52
					this.serviceWrapHeightPx = 52
					this.serviceBubbleWidthPx = 170
					this.serviceBubbleHeightPx = 44
					this.serviceX = 0
					this.serviceY = 0
				}
			},
			clearBubbleTimer() {
				if (this.serviceBubbleTimer) {
					clearTimeout(this.serviceBubbleTimer)
					this.serviceBubbleTimer = null
				}
			},
			scheduleBubble() {
				this.clearBubbleTimer()
				this.serviceBubbleTimer = setTimeout(() => {
					this.showServiceBubble = true
					this.serviceBubbleTimer = setTimeout(() => {
						this.showServiceBubble = false
						this.serviceBubbleTimer = null
					}, this.autoHideMs)
				}, this.initialDelayMs)
			}
		}
	}
</script>

<style scoped>
	.service-root {
		position: fixed;
		left: 0;
		top: 0;
		width: 100vw;
		height: 100vh;
		pointer-events: none;
		z-index: 3000;
	}

	.service-movable-area {
		position: fixed;
		left: 0;
		top: 0;
		width: 100vw;
		height: 100vh;
		pointer-events: none;
		z-index: 3000;
	}

	.service-float-wrap {
		position: absolute;
		z-index: 3000;
		width: 104rpx;
		height: 104rpx;
		overflow: visible;
		pointer-events: auto;
	}

	.service-bubble {
		position: fixed;
		z-index: 3001;
		pointer-events: auto;
		max-width: 340rpx;
		padding: 16rpx 20rpx;
		font-size: 22rpx;
		line-height: 1.4;
		color: #6a5142;
		background: linear-gradient(145deg, #fff7ec 0%, #fff2dd 100%);
		border: 1rpx solid #f1ddbd;
		border-radius: 18rpx;
		box-shadow: 0 12rpx 28rpx rgba(37, 30, 25, 0.14);
		animation: bubbleIn 0.25s ease-out;
	}

	.service-bubble::after {
		content: '';
		position: absolute;
		right: 28rpx;
		bottom: -10rpx;
		width: 18rpx;
		height: 18rpx;
		background: #fff2dd;
		border-right: 1rpx solid #f1ddbd;
		border-bottom: 1rpx solid #f1ddbd;
		transform: rotate(45deg);
	}

	.service-float-btn {
		width: 104rpx;
		height: 104rpx;
		position: absolute;
		right: 0;
		bottom: 0;
		border-radius: 50%;
		background: linear-gradient(145deg, #ff9360 0%, #e85a4f 100%);
		box-shadow: 0 16rpx 32rpx rgba(232, 90, 79, 0.35);
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		animation: floatBob 2.4s ease-in-out infinite;
	}

	.service-dot {
		width: 12rpx;
		height: 12rpx;
		border-radius: 50%;
		background: #ffffff;
		margin-bottom: 6rpx;
		box-shadow: 0 0 0 8rpx rgba(255, 255, 255, 0.18);
	}

	.service-float-text {
		color: #ffffff;
		font-size: 22rpx;
		font-weight: 600;
		letter-spacing: 1rpx;
	}

	@keyframes bubbleIn {
		from { opacity: 0; transform: translateY(8rpx) scale(0.95); }
		to { opacity: 1; transform: translateY(0) scale(1); }
	}

	@keyframes floatBob {
		0%, 100% { transform: translateY(0); }
		50% { transform: translateY(-6rpx); }
	}
</style>

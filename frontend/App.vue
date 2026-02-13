<script>
	import { isLogin } from './utils/auth.js'

	export default {
		onLaunch: function() {
			console.log('App Launch - AI写真馆启动')

			// 全局路由拦截（只在启动时设置一次）
			this.setupRouteGuard()
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
		},
		methods: {
			// 设置全局路由守卫
			setupRouteGuard() {
				// 需要登录的页面列表
				const authPages = [
					'/pages/upload/index',
					'/pages/generating/index',
					'/pages/preview/index',
					'/pages/history/index'
				]

				// 拦截 navigateTo
				const originalNavigateTo = uni.navigateTo
				uni.navigateTo = (options) => {
					if (this.needAuth(options.url, authPages)) {
						if (!isLogin()) {
							this.showLoginModal()
							return
						}
					}
					return originalNavigateTo(options)
				}

				// 拦截 redirectTo
				const originalRedirectTo = uni.redirectTo
				uni.redirectTo = (options) => {
					if (this.needAuth(options.url, authPages)) {
						if (!isLogin()) {
							this.showLoginModal()
							return
						}
					}
					return originalRedirectTo(options)
				}

				// 拦截 reLaunch
				const originalReLaunch = uni.reLaunch
				uni.reLaunch = (options) => {
					if (this.needAuth(options.url, authPages)) {
						if (!isLogin()) {
							this.showLoginModal()
							return
						}
					}
					return originalReLaunch(options)
				}
			},

			// 判断是否需要登录
			needAuth(url, authPages) {
				if (!url) return false

				// 提取路径（去除参数）
				const path = url.split('?')[0]

				// 检查是否在需要登录的页面列表中
				return authPages.some(authPage => path === authPage || path.startsWith(authPage))
			},

			// 显示登录提示
			showLoginModal() {
				uni.showModal({
					title: '提示',
					content: '请先登录',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.navigateTo({ url: '/pages/login/index' })
						}
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	/* ==================== AI写真馆全局样式 ==================== */

	/* 基础样式重置 */
	page {
		background-color: #FFFAF8;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
		color: #3D2B2F;
		line-height: 1.6;
	}

	/* 强制覆盖下拉刷新指示器颜色和大小 - 全局样式 */
	.uni-scroll-view-refresher {
		background: transparent !important;
	}

	.uni-scroll-view-refresh {
		background: transparent !important;
	}

	/* 下拉时的指示器（箭头/图标） */
	.uni-scroll-view-refresh__icon {
		color: #2b2521 !important;
		font-size: 20px !important;
	}

	.uni-scroll-view-refresh__icon::before,
	.uni-scroll-view-refresh__icon::after {
		background-color: #2b2521 !important;
		border-color: #2b2521 !important;
	}

	/* 下拉时的 SVG 图标 - 只改变容器颜色，不破坏图标形状 */
	.uni-scroll-view-refresh__icon svg {
		width: 20px !important;
		height: 20px !important;
		color: #2b2521 !important;
	}

	/* 刷新时的指示器（旋转的圈） */
	.uni-scroll-view-refresh__spinner {
		color: #2b2521 !important;
		border-color: #2b2521 !important;
		width: 20px !important;
		height: 20px !important;
		border-width: 2px !important;
	}

	.uni-scroll-view-refresh__spinner circle {
		stroke: #2b2521 !important;
		stroke-width: 2 !important;
	}

	.uni-loading {
		border-color: #2b2521 transparent transparent transparent !important;
		width: 20px !important;
		height: 20px !important;
		border-width: 2px !important;
	}

	/* 覆盖所有可能的绿色 - 但不破坏 SVG 图标形状 */
	/* 通配符选择器在小程序中可能不支持，已注释 */
	/* .uni-scroll-view-refresh * {
		color: #2b2521 !important;
		border-color: #2b2521 !important;
	} */

	/* 针对可能的内联样式 */
	/* 属性选择器在小程序中可能不支持，已注释 */
	/* .uni-scroll-view-refresh [style*="color"] {
		color: #2b2521 !important;
	}

	.uni-scroll-view-refresh [style*="border-color"] {
		border-color: #2b2521 !important;
	} */

	/* 通用文本样式 */
	text {
		word-break: break-all;
		white-space: pre-wrap;
	}

	/* 通用视图样式 */
	view {
		box-sizing: border-box;
	}

	/* 滚动条隐藏 */
	::-webkit-scrollbar {
		display: none;
		width: 0;
		height: 0;
		color: transparent;
	}

	/* 通用按钮点击效果 */
	.clickable {
		transition: all 0.3s ease;
	}

	.clickable:active {
		opacity: 0.7;
		transform: scale(0.98);
	}

	/* 通用卡片样式 */
	.card {
		background: #FFFFFF;
		border-radius: 16rpx;
		box-shadow: 0 4rpx 12rpx rgba(232, 155, 156, 0.08);
		border: 1px solid #F5EBE8;
	}

	/* 通用渐变背景 */
	.warm-gradient {
		background: linear-gradient(135deg, #E89B9C, #D88A8B);
	}

	/* 通用阴影效果 */
	.soft-shadow {
		box-shadow: 0 4rpx 12rpx rgba(232, 155, 156, 0.08);
	}

	.soft-shadow-lg {
		box-shadow: 0 8rpx 24rpx rgba(232, 155, 156, 0.15);
	}

	/* 通用圆角 */
	.rounded-sm {
		border-radius: 8rpx;
	}

	.rounded {
		border-radius: 12rpx;
	}

	.rounded-lg {
		border-radius: 16rpx;
	}

	.rounded-xl {
		border-radius: 24rpx;
	}

	/* 通用间距 */
	.mt-xs { margin-top: 8rpx; }
	.mt-sm { margin-top: 16rpx; }
	.mt-md { margin-top: 24rpx; }
	.mt-lg { margin-top: 32rpx; }
	.mt-xl { margin-top: 48rpx; }

	.mb-xs { margin-bottom: 8rpx; }
	.mb-sm { margin-bottom: 16rpx; }
	.mb-md { margin-bottom: 24rpx; }
	.mb-lg { margin-bottom: 32rpx; }
	.mb-xl { margin-bottom: 48rpx; }

	.p-xs { padding: 8rpx; }
	.p-sm { padding: 16rpx; }
	.p-md { padding: 24rpx; }
	.p-lg { padding: 32rpx; }
	.p-xl { padding: 48rpx; }

	/* 通用文字颜色 */
	.text-primary { color: #E89B9C; }
	.text-secondary { color: #A8C5A8; }
	.text-accent { color: #E8A872; }
	.text-dark { color: #3D2B2F; }
	.text-gray { color: #6B5A5E; }
	.text-muted { color: #A8969A; }

	/* 通用文字大小 */
	.text-xs { font-size: 20rpx; }
	.text-sm { font-size: 24rpx; }
	.text-base { font-size: 28rpx; }
	.text-lg { font-size: 32rpx; }
	.text-xl { font-size: 36rpx; }
	.text-2xl { font-size: 44rpx; }

	/* 通用文字粗细 */
	.font-normal { font-weight: 400; }
	.font-medium { font-weight: 500; }
	.font-semibold { font-weight: 600; }
	.font-bold { font-weight: 700; }

	/* Flex布局工具类 */
	.flex { display: flex; }
	.flex-col { flex-direction: column; }
	.flex-row { flex-direction: row; }
	.items-center { align-items: center; }
	.justify-center { justify-content: center; }
	.justify-between { justify-content: space-between; }
	/* gap 属性在小程序中不支持，请使用 margin 代替 */
	/* .gap-xs { gap: 8rpx; } */
	/* .gap-sm { gap: 16rpx; } */
	/* .gap-md { gap: 24rpx; } */
	/* .gap-lg { gap: 32rpx; } */

	/* 显示工具类 */
	.hidden { display: none; }
	.block { display: block; }
	.inline-block { display: inline-block; }

	/* 定位工具类 */
	.relative { position: relative; }
	.absolute { position: absolute; }
	.fixed { position: fixed; }

	/* 溢出处理 */
	.overflow-hidden { overflow: hidden; }
	.ellipsis {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	/* 透明度 */
	.opacity-50 { opacity: 0.5; }
	.opacity-70 { opacity: 0.7; }
	.opacity-90 { opacity: 0.9; }
</style>

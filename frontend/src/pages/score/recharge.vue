<template>
	<view class="page">
		<!-- 艹，充值档位列表 -->
		<view class="packages-container">
			<view class="packages-title">选择充值档位</view>
			<view class="packages-list">
				<view
					v-for="(item, index) in packages"
					:key="item.id"
					class="package-item"
					:class="{ 'package-selected': selectedPackage && selectedPackage.id === item.id }"
					@tap="selectPackage(item)"
				>
					<view class="package-name">{{ item.name }}</view>
					<view class="package-amount">¥{{ item.amount }}</view>
					<view class="package-score">
						{{ item.score }}积分
						<text v-if="item.bonus_score > 0" class="package-bonus">+{{ item.bonus_score }}</text>
					</view>
					<view v-if="selectedPackage && selectedPackage.id === item.id" class="package-check">✓</view>
				</view>
			</view>
		</view>

		<!-- 艹，充值按钮 -->
		<view class="recharge-footer">
			<button
				class="recharge-btn"
				:disabled="!selectedPackage || isPaying"
				:loading="isPaying"
				@tap="handleRecharge"
			>
				{{ selectedPackage ? `立即充值 ¥${selectedPackage.amount}` : '请选择充值档位' }}
			</button>
		</view>
	</view>
</template>

<script>
	import { get, post } from '../../services/request.js'

	export default {
		data() {
			return {
				packages: [],
				selectedPackage: null,
				isPaying: false
			}
		},
		onLoad() {
			// 艹，页面加载时获取充值档位
			this.getPackages()
		},
		methods: {
			// 艹，获取充值档位列表
			async getPackages() {
				try {
					const data = await get('/api/score/packages')
					this.packages = data.list
				} catch (error) {
					console.error('获取充值档位失败:', error)
				}
			},

			// 艹，选择充值档位
			selectPackage(item) {
				this.selectedPackage = item
			},

			// 艹，处理充值
			async handleRecharge() {
				if (this.isPaying) {
					return
				}

				if (!this.selectedPackage) {
					uni.showToast({
						title: '请选择充值档位',
						icon: 'none'
					})
					return
				}

				// 艹，创建充值订单
				await this.createOrder()
			},

			// 艹，创建充值订单
			async createOrder() {
				if (this.isPaying) return
				this.isPaying = true
				uni.showLoading({ title: '创建订单中...' })

				try {
					const data = await post('/api/score/createOrder', {
						package_id: this.selectedPackage.id
					})
					uni.hideLoading()

					// 艹，订单创建成功，拉起微信支付
					await this.wechatPay(data.order_no)
				} catch (error) {
					uni.hideLoading()
					console.error('创建订单失败:', error)
				} finally {
					this.isPaying = false
				}
			},

			// 艹，微信小程序支付
			async wechatPay(orderNo) {
				// #ifndef MP-WEIXIN
				uni.hideLoading()
				uni.showToast({
					title: '请在微信小程序内完成支付',
					icon: 'none'
				})
				return
				// #endif

				uni.showLoading({ title: '拉起支付中...' })

				try {
					const loginRes = await this.wxLogin()
					const payData = await post('/api/score/pay', {
						order_no: orderNo,
						code: loginRes.code
					})

					await this.requestWxPayment(payData)
					await this.pollOrderPaid(orderNo)

					uni.hideLoading()
					uni.showToast({ title: '充值成功', icon: 'success', duration: 1500 })
					setTimeout(() => uni.navigateBack(), 1500)
				} catch (error) {
					uni.hideLoading()

					const errMsg = error?.errMsg || error?.msg || ''
					if (errMsg.includes('cancel')) {
						uni.showToast({ title: '已取消支付', icon: 'none' })
					} else {
						console.error('支付失败:', error)
						uni.showToast({
							title: error?.msg || '支付失败，请稍后在充值记录中查看',
							icon: 'none'
						})
					}
				}
			},

			wxLogin() {
				return new Promise((resolve, reject) => {
					uni.login({
						provider: 'weixin',
						success: (res) => {
							if (res.code) {
								resolve(res)
							} else {
								reject(new Error('微信登录失败'))
							}
						},
						fail: reject
					})
				})
			},

			requestWxPayment(payData) {
				return new Promise((resolve, reject) => {
					uni.requestPayment({
						provider: 'wxpay',
						timeStamp: String(payData.timeStamp),
						nonceStr: payData.nonceStr,
						package: payData.package,
						signType: payData.signType || 'MD5',
						paySign: payData.paySign,
						success: resolve,
						fail: reject
					})
				})
			},

			async pollOrderPaid(orderNo) {
				const maxRetry = 10
				const intervalMs = 1000

				for (let i = 0; i < maxRetry; i++) {
					const order = await post('/api/score/checkOrder', { order_no: orderNo })
					if (Number(order.pay_status) === 1) {
						return true
					}
					await new Promise((resolve) => setTimeout(resolve, intervalMs))
				}

				throw new Error('支付结果确认超时，请稍后在充值记录中查看')
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 52%, #ffffff 100%);
		padding: 28rpx;
		padding-bottom: 160rpx;
	}

	/* 艹，充值档位容器 */
	.packages-container {
		margin-bottom: 40rpx;
	}

	.packages-title {
		font-size: 26rpx;
		font-weight: 600;
		color: #2b2521;
		margin-bottom: 20rpx;
	}

	.packages-list {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20rpx;
	}

	.package-item {
		background: #ffffff;
		border-radius: 22rpx;
		padding: 32rpx 24rpx;
		text-align: center;
		box-shadow: 0 12rpx 26rpx rgba(37, 30, 25, 0.06);
		border: 2rpx solid #f0e6df;
		position: relative;
		transition: all 0.3s;
	}

	.package-selected {
		border-color: #2b2521;
		box-shadow: 0 16rpx 34rpx rgba(37, 30, 25, 0.12);
		transform: scale(1.02);
	}

	.package-name {
		font-size: 24rpx;
		color: #7a6f69;
		margin-bottom: 12rpx;
	}

	.package-amount {
		font-size: 40rpx;
		font-weight: 700;
		color: #2b2521;
		margin-bottom: 8rpx;
	}

	.package-score {
		font-size: 22rpx;
		color: #9a8f88;
	}

	.package-bonus {
		color: #ff6b35;
		font-weight: 600;
		margin-left: 4rpx;
	}

	.package-check {
		position: absolute;
		top: 12rpx;
		right: 12rpx;
		width: 36rpx;
		height: 36rpx;
		background: #2b2521;
		color: #ffffff;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 20rpx;
		font-weight: 700;
	}

	/* 艹，充值按钮 */
	.recharge-footer {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 20rpx 28rpx;
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
		background: #ffffff;
		box-shadow: 0 -8rpx 24rpx rgba(37, 30, 25, 0.08);
	}

	.recharge-btn {
		width: 100%;
		height: 88rpx;
		background: #2b2521;
		color: #ffffff;
		font-size: 28rpx;
		font-weight: 600;
		border-radius: 999rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.recharge-btn[disabled] {
		background: #e0e0e0;
		color: #9a8f88;
	}
</style>

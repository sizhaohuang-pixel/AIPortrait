<template>
	<view class="login-page">
		<!-- Logo 区域 -->
		<view class="logo-section">
			<text class="app-name">非鱼影像馆</text>
			<text class="slogan">记录美好瞬间</text>
		</view>

		<!-- 登录卡片 -->
		<view class="login-card">
			<view class="card-title">欢迎使用</view>
			<view class="card-desc">使用微信授权登录，快速开始</view>

			<!-- 微信授权登录按钮 -->
			<button
				class="wechat-login-btn"
				open-type="getPhoneNumber"
				@getphonenumber="handleGetPhoneNumber"
				:loading="loading"
				:disabled="loading"
			>
				<view class="btn-content">
					<text class="btn-text">登录</text>
				</view>
			</button>

			<view class="tips">
				<text class="tips-text">登录即表示同意</text>
				<text class="tips-link" @tap="goAgreement('user')">《用户协议》</text>
				<text class="tips-text">和</text>
				<text class="tips-link" @tap="goAgreement('privacy')">《隐私政策》</text>
			</view>
		</view>
	</view>
</template>

<script>
import { saveLoginInfo } from '../../utils/auth.js'
import { wechatLogin } from '../../services/user.js'

export default {
	data() {
		return {
			loading: false
		}
	},
	methods: {
		/**
		 * 处理微信授权获取手机号
		 */
		async handleGetPhoneNumber(e) {
			console.log('=== 微信授权回调 ===')
			console.log('完整事件对象:', e)
			console.log('e.detail:', e.detail)

			// 艹，先检查 e.detail 是否存在
			if (!e.detail) {
				console.error('e.detail 不存在')
				uni.showToast({
					title: '授权失败，请重试',
					icon: 'none'
				})
				return
			}

			// 用户拒绝授权
			if (e.detail.errMsg !== 'getPhoneNumber:ok') {
				console.log('用户拒绝授权或授权失败:', e.detail.errMsg)
				uni.showToast({
					title: '授权失败，请重试',
					icon: 'none'
				})
				return
			}

			this.loading = true

			try {
				// 1. 先调用 wx.login() 获取 code
				const loginRes = await this.wxLogin()
				console.log('wx.login() 结果:', loginRes)

				// 2. 获取加密数据
				const { encryptedData, iv } = e.detail

				// 3. 调用后端接口
				const userInfo = await wechatLogin(loginRes.code, encryptedData, iv)

				// 4. 保存登录信息
				saveLoginInfo(userInfo)

				uni.showToast({
					title: '登录成功',
					icon: 'success'
				})

				// 5. 延迟跳转
				setTimeout(() => {
					uni.switchTab({
						url: '/pages/mine/index'
					})
				}, 1500)
			} catch (error) {
				console.error('登录失败：', error)
				uni.showModal({
					title: '登录失败',
					content: error.message || '请稍后重试',
					showCancel: false
				})
			} finally {
				this.loading = false
			}
		},

		/**
		 * 调用 wx.login() 获取 code
		 */
		wxLogin() {
			return new Promise((resolve, reject) => {
				uni.login({
					provider: 'weixin',
					success: (res) => {
						if (res.code) {
							resolve(res)
						} else {
							reject(new Error('获取 code 失败'))
						}
					},
					fail: (err) => {
						reject(err)
					}
				})
			})
		},

		// 艹，跳转到协议页面
		goAgreement(type) {
			uni.navigateTo({ url: `/pages/agreement/index?type=${type}` })
		}
	}
}
</script>

<style scoped>
.login-page {
	min-height: 100vh;
	background: linear-gradient(135deg, #FFFAF8 0%, #FFF5F0 100%);
	padding: 120rpx 60rpx 60rpx;
}

/* Logo 区域 */
.logo-section {
	text-align: center;
	margin-bottom: 120rpx;
}

.app-name {
	display: block;
	font-size: 72rpx;
	font-weight: bold;
	color: #2b2521;
	margin-bottom: 20rpx;
}

.slogan {
	display: block;
	font-size: 28rpx;
	color: #9a8f88;
}

/* 登录卡片 */
.login-card {
	background: #FFFFFF;
	border-radius: 26rpx;
	padding: 60rpx 40rpx;
	box-shadow: 0 8rpx 32rpx rgba(43, 37, 33, 0.08);
}

.card-title {
	font-size: 40rpx;
	font-weight: bold;
	color: #2b2521;
	text-align: center;
	margin-bottom: 16rpx;
}

.card-desc {
	font-size: 26rpx;
	color: #9a8f88;
	text-align: center;
	margin-bottom: 60rpx;
}

/* 微信授权登录按钮 */
.wechat-login-btn {
	width: 100%;
	height: 96rpx;
	padding: 0;
	font-size: 32rpx;
	font-weight: bold;
	color: #FFFFFF;
	background: linear-gradient(135deg, #2b2521 0%, #4a3f38 100%);
	border: none;
	border-radius: 16rpx;
	box-shadow: 0 4rpx 16rpx rgba(43, 37, 33, 0.2);
}

.wechat-login-btn[loading] {
	background: #9a8f88;
}

.wechat-login-btn[disabled] {
	background: #9a8f88;
}

.btn-content {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 100%;
}

.btn-text {
	font-size: 32rpx;
	font-weight: bold;
}

/* 提示文字 */
.tips {
	margin-top: 40rpx;
	text-align: center;
	font-size: 22rpx;
	color: #9a8f88;
}

.tips-text {
	color: #9a8f88;
}

.tips-link {
	color: #2b2521;
	text-decoration: underline;
}
</style>

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
					<text class="btn-text">一键登录</text>
				</view>
			</button>

			<view class="tips">
				<text class="tips-text">登录即表示同意</text>
				<text class="tips-link" @tap="goAgreement('user')">《用户协议》</text>
				<text class="tips-text">和</text>
				<text class="tips-link" @tap="goAgreement('privacy')">《隐私政策》</text>
			</view>
		</view>

		<!-- 新用户完善资料弹窗 -->
		<view class="profile-modal" v-if="showProfileModal">
			<view class="modal-mask"></view>
			<view class="modal-content">
				<view class="modal-header">完善个人资料</view>
				<view class="modal-body">
					<view class="avatar-wrapper">
						<button class="avatar-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
							<image class="avatar-img" :src="tempAvatar || '/static/icons/user.png'"></image>
							<view class="avatar-plus">+</view>
						</button>
						<text class="avatar-tip">点击设置头像</text>
					</view>
					<view class="input-group">
						<text class="label">昵称</text>
						<input type="nickname" class="nickname-input" v-model="tempNickname" placeholder="请输入昵称" @blur="onNicknameBlur" />
					</view>
				</view>
				<view class="modal-footer">
					<button class="submit-btn" :loading="submitting" @tap="submitProfile">确定</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { saveLoginInfo } from '../../utils/auth.js'
import { wechatLogin } from '../../services/user.js'
import { get, post, uploadFile } from '../../services/request.js'
import { API_CONFIG } from '../../services/config.js'

export default {
	data() {
		return {
			loading: false,
			loggingIn: false,
			showProfileModal: false,
			submitting: false,
			tempAvatar: '',
			tempNickname: '',
			userInfo: null,
			inviterId: 0
		}
	},
	onLoad(query) {
		this.initInviterId(query)
	},
	methods: {
		initInviterId(query = {}) {
			let inviterId = Number(query.inviter_id || 0)
			const scene = query.scene ? decodeURIComponent(query.scene) : ''
			if (!inviterId && scene) {
				const pairs = String(scene).split('&')
				pairs.forEach(pair => {
					const [k, v] = pair.split('=')
					if (k === 'inviter_id') {
						inviterId = Number(v || 0)
					}
				})
			}
			if (inviterId > 0) {
				this.inviterId = inviterId
				uni.setStorageSync('pending_inviter_id', inviterId)
			} else {
				this.inviterId = Number(uni.getStorageSync('pending_inviter_id') || 0)
			}
		},
		async handleGetPhoneNumber(e) {
			if (this.loggingIn || this.loading) {
				return
			}

			if (!e.detail.code) {
				uni.showToast({
					title: '授权失败，请重试',
					icon: 'none'
				})
				return
			}

			this.loading = true
			this.loggingIn = true

			try {
				const inviteId = this.getPendingInviterId()
				const userInfo = await this.requestWechatLogin(e.detail.code, inviteId)
				this.userInfo = userInfo

				saveLoginInfo(userInfo)
				uni.removeStorageSync('pending_inviter_id')

				const isNewUser = this.needCompleteProfile(userInfo)

				if (isNewUser) {
					this.showProfileModal = true
					uni.showToast({
						title: '登录成功，请完善资料',
						icon: 'none'
					})
				} else {
					uni.showToast({
						title: '登录成功',
						icon: 'success'
					})
					this.jumpToMine()
				}
			} catch (error) {
				if (error && Number(error.code) === 303) {
					uni.showToast({
						title: '您已登录',
						icon: 'none'
					})
					this.jumpToMine()
					return
				}
				uni.showModal({
					title: '登录失败',
					content: error.message || '请稍后重试',
					showCancel: false
				})
			} finally {
				this.loading = false
				this.loggingIn = false
			}
		},

		async onChooseAvatar(e) {
			const { avatarUrl } = e.detail

			try {
				uni.showLoading({ title: '上传中...' })
				const rawUrl = await uploadFile(avatarUrl)
				this.tempAvatar = this.normalizeUploadedAvatarUrl(rawUrl)
			} catch (err) {
				uni.showToast({ title: '头像上传失败', icon: 'none' })
			} finally {
				uni.hideLoading()
			}
		},

		onNicknameBlur(e) {
			this.tempNickname = e.detail.value
		},

		async submitProfile() {
			if (!this.tempNickname && !this.tempAvatar) {
				this.showProfileModal = false
				this.jumpToMine()
				return
			}

			this.submitting = true
			try {
				await post('/api/account/profile', {
					nickname: this.tempNickname || this.userInfo.nickname,
					avatar: this.tempAvatar || this.userInfo.avatar
				})

				// 更新本地存储的用户信息
				const newUserInfo = {
					...this.userInfo,
					nickname: this.tempNickname || this.userInfo.nickname,
					avatar: this.tempAvatar || this.userInfo.avatar
				}
				saveLoginInfo(JSON.parse(JSON.stringify(newUserInfo)))

				uni.showToast({ title: '资料已完善', icon: 'success' })
				this.showProfileModal = false
				this.jumpToMine()
			} catch (err) {
				uni.showToast({ title: err.message || '保存失败', icon: 'none' })
			} finally {
				this.submitting = false
			}
		},

		jumpToMine() {
			setTimeout(() => {
				uni.switchTab({ url: '/pages/mine/index' })
			}, 1000)
		},

		goAgreement(type) {
			uni.navigateTo({ url: `/pages/agreement/index?type=${type}` })
		},

		getPendingInviterId() {
			return Number(this.inviterId || uni.getStorageSync('pending_inviter_id') || 0)
		},

		needCompleteProfile(userInfo = {}) {
			return !userInfo.nickname || /1[3-9]\d\*\*\*\*\d{4}/.test(userInfo.nickname)
		},

		async requestWechatLogin(code, inviterId) {
			let userInfo = await wechatLogin(code, '', '', inviterId)
			if (!userInfo || !userInfo.token) {
				const info = await get('/api/user/info')
				userInfo = info.userInfo || userInfo
			}
			if (!userInfo || !userInfo.token) {
				throw new Error('登录态未建立，请重试')
			}
			return userInfo
		},

		normalizeUploadedAvatarUrl(url) {
			let fullUrl = String(url || '')
			if (fullUrl.startsWith('//')) {
				fullUrl = 'http:' + fullUrl
			}

			fullUrl = fullUrl.replace(/^https:\/\/(localhost|127\.0\.0\.1)/i, 'http://$1')

			const baseURL = API_CONFIG.baseURL
			if (baseURL && !baseURL.includes('localhost') && !baseURL.includes('127.0.0.1')) {
				const baseMatch = baseURL.match(/^(https?:\/\/[^\/]+)/i)
				if (baseMatch) {
					fullUrl = fullUrl.replace(/^http?:\/\/(localhost|127\.0\.0\.1)(:\d+)?/i, baseMatch[1])
				}
			}

			if (fullUrl.startsWith('//')) {
				fullUrl = 'http:' + fullUrl
			}
			return fullUrl
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

/* 完善资料弹窗样式 */
.profile-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 999;
}

.modal-mask {
	position: absolute;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.6);
}

.modal-content {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	background: #FFFFFF;
	border-radius: 32rpx 32rpx 0 0;
	padding: 60rpx 40rpx calc(60rpx + env(safe-area-inset-bottom));
	animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
	from { transform: translateY(100%); }
	to { transform: translateY(0); }
}

.modal-header {
	font-size: 36rpx;
	font-weight: bold;
	color: #2b2521;
	text-align: center;
	margin-bottom: 60rpx;
}

.avatar-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 60rpx;
}

.avatar-btn {
	position: relative;
	width: 160rpx;
	height: 160rpx;
	padding: 0;
	border-radius: 50%;
	background: #F8F8F8;
	margin-bottom: 16rpx;
}

.avatar-img {
	width: 100%;
	height: 100%;
	border-radius: 50%;
}

.avatar-plus {
	position: absolute;
	right: 0;
	bottom: 0;
	width: 44rpx;
	height: 44rpx;
	background: #2b2521;
	color: #FFFFFF;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 32rpx;
	border: 4rpx solid #FFFFFF;
}

.avatar-tip {
	font-size: 24rpx;
	color: #9a8f88;
}

.input-group {
	background: #F8F8F8;
	border-radius: 16rpx;
	padding: 30rpx;
	display: flex;
	align-items: center;
}

.label {
	font-size: 28rpx;
	color: #2b2521;
	margin-right: 30rpx;
}

.nickname-input {
	flex: 1;
	font-size: 28rpx;
}

.submit-btn {
	margin-top: 60rpx;
	width: 100%;
	height: 96rpx;
	background: #2b2521;
	color: #FFFFFF;
	border-radius: 16rpx;
	font-weight: bold;
}
</style>

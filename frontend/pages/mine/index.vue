<template>
	<view class="page">
		<view class="profile-card">
			<view class="avatar" :class="{ 'has-image': hasValidAvatar }" :style="hasValidAvatar ? { backgroundImage: `url(${userInfo.avatar})` } : {}" @tap="goEditProfile">
				<text v-show="!hasValidAvatar" class="avatar-text">{{ avatarText }}</text>
			</view>
			<view class="profile-info" @tap="goEditProfile">
				<view class="name">{{ isLogin ? userInfo.nickname : '未登录' }}</view>
				<view class="sub">{{ isLogin ? userInfo.mobile : '登录后同步历史与作品' }}</view>
			</view>
			<button v-if="!isLogin" class="login-btn" @tap="goLogin">登录</button>
			<button v-else class="logout-btn" @tap="handleLogout">退出</button>
		</view>

		<!-- 艹，积分余额卡片 -->
		<view v-if="isLogin" class="score-card" @tap="goScoreDetail">
			<view class="score-header">
				<view class="score-title">我的积分</view>
				<view class="score-action">
					<text class="score-action-text">明细</text>
					<text class="score-action-arrow">›</text>
				</view>
			</view>
			<view class="score-content">
				<view class="score-value">{{ scoreInfo.score || 0 }}</view>
				<view class="score-expire" v-if="scoreInfo.expire_time > 0">
					{{ scoreInfo.expire_days }}天后过期
				</view>
			</view>
			<button class="recharge-btn" @tap.stop="goRecharge">充值</button>
		</view>

		<view class="section">
			<view class="section-title">常用</view>
			<view class="list">
				<view class="list-item" @tap="goHistory">
					<view class="item-title">历史记录</view>
					<view class="item-desc">查看生成结果</view>
				</view>
			</view>
		</view>

		<view class="section">
			<view class="section-title">关于</view>
			<view class="list">
				<view class="list-item" @tap="goAgreement('about')">
					<view class="item-title">关于我们</view>
				</view>
				<view class="list-item" @tap="goAgreement('privacy')">
					<view class="item-title">隐私协议</view>
				</view>
				<view class="list-item" @tap="goAgreement('user')">
					<view class="item-title">用户协议</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { isLogin, getUserInfo, requireLogin, logout } from '../../utils/auth.js'
	import { get } from '../../services/request.js'

	export default {
		data() {
			return {
				isLogin: false,
				userInfo: {
					nickname: '',
					mobile: '',
					avatar: ''
				},
				// 艹，积分信息
				scoreInfo: {
					score: 0,
					expire_time: 0,
					expire_days: 0
				}
			}
		},
		computed: {
			// 艹，生成头像显示文字
			avatarText() {
				if (!this.isLogin) {
					return '未'
				}

				// 艹，优先用手机号后两位，更有辨识度
				if (this.userInfo.mobile && this.userInfo.mobile.length >= 2) {
					return this.userInfo.mobile.slice(-2)
				}

				// 艹，如果昵称不是默认的 user_ 开头，就显示首字母
				if (this.userInfo.nickname && !this.userInfo.nickname.startsWith('user_')) {
					return this.userInfo.nickname.charAt(0)
				}

				return '用'
			},
			// 艹，判断是否有有效头像
			hasValidAvatar() {
				// 艹，/static/images/avatar.png 是默认头像，不算有效头像
				if (!this.userInfo.avatar) return false
				if (this.userInfo.avatar === '/static/images/avatar.png') return false
				if (this.userInfo.avatar.startsWith('/static/')) return false
				return this.userInfo.avatar.length > 0
			}
		},
		onShow() {
			// 每次显示页面时检查登录状态
			this.checkLoginStatus()

			// 艹，调试：打印 userInfo 看看里面有啥
			console.log('=== userInfo 调试 ===')
			console.log('isLogin:', this.isLogin)
			console.log('userInfo:', JSON.stringify(this.userInfo, null, 2))
			console.log('mobile:', this.userInfo.mobile)
			console.log('nickname:', this.userInfo.nickname)
			console.log('avatarText:', this.avatarText)
			console.log('==================')

			// 艹，如果已登录，获取积分信息
			if (this.isLogin) {
				this.getScoreInfo()
			}
		},
		methods: {
			// 检查登录状态
			checkLoginStatus() {
				this.isLogin = isLogin()
				if (this.isLogin) {
					this.userInfo = getUserInfo() || {
						nickname: '',
						mobile: '',
						avatar: ''
					}
				} else {
					this.userInfo = {
						nickname: '',
						mobile: '',
						avatar: ''
					}
				}
			},

			// 艹，获取积分信息
			async getScoreInfo() {
				try {
					const data = await get('/api/score/info')
					this.scoreInfo = data
				} catch (error) {
					console.error('获取积分信息失败:', error)
				}
			},

			// 艹，跳转到积分明细页面
			goScoreDetail() {
				uni.navigateTo({ url: '/pages/score/detail' })
			},

			// 艹，跳转到充值页面
			goRecharge() {
				uni.navigateTo({ url: '/pages/score/recharge' })
			},

			goHistory() {
				// 使用工具函数检查登录状态
				if (!requireLogin()) {
					return
				}
				uni.navigateTo({ url: '/pages/history/index' })
			},

			// 艹，跳转到协议页面
			goAgreement(type) {
				uni.navigateTo({ url: `/pages/agreement/index?type=${type}` })
			},

			goLogin() {
				uni.navigateTo({ url: '/pages/login/index' })
			},

			// 艹，跳转到编辑资料页面
			goEditProfile() {
				// 使用工具函数检查登录状态
				if (!requireLogin()) {
					return
				}
				uni.navigateTo({ url: '/pages/profile/edit' })
			},

			// 退出登录
			handleLogout() {
				logout({
					onSuccess: () => {
						// 更新状态
						this.checkLoginStatus()
					}
				})
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding: 36rpx 28rpx 40rpx;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 52%, #ffffff 100%);
		color: #1f1a17;
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
	}

	.profile-card {
		display: flex;
		align-items: center;
		gap: 20rpx;
		padding: 26rpx;
		background: #ffffff;
		border-radius: 26rpx;
		box-shadow: 0 16rpx 34rpx rgba(37, 30, 25, 0.08);
		border: 1rpx solid #f0e6df;
	}

	.avatar {
		width: 96rpx;
		height: 96rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, #fce8dc, #f4d7c8);
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}

	.avatar.has-image {
		background-size: cover;
		background-position: center;
	}

	.avatar-text {
		font-size: 36rpx;
		font-weight: 600;
		color: #2b2521;
		line-height: 1;
		user-select: none;
	}

	.profile-info {
		flex: 1;
	}

	.name {
		font-size: 30rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.sub {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #7a6f69;
	}

	.login-btn {
		background: #2b2521;
		color: #ffffff;
		font-size: 24rpx;
		border-radius: 999rpx;
		padding: 0 20rpx;
	}

	.logout-btn {
		background: #f5f5f5;
		color: #2b2521;
		font-size: 24rpx;
		border-radius: 999rpx;
		padding: 0 20rpx;
		border: 1rpx solid #e0e0e0;
	}

	.section {
		margin-top: 28rpx;
	}

	.section-title {
		font-size: 26rpx;
		font-weight: 600;
		margin-bottom: 14rpx;
		color: #2b2521;
	}

	.list {
		background: #ffffff;
		border-radius: 22rpx;
		overflow: hidden;
		box-shadow: 0 12rpx 26rpx rgba(37, 30, 25, 0.06);
		border: 1rpx solid #f0e6df;
	}

	.list-item {
		padding: 22rpx 24rpx;
		border-bottom: 1rpx solid #f1ebe6;
	}

	.list-item:last-child {
		border-bottom: none;
	}

	.item-title {
		font-size: 26rpx;
		font-weight: 600;
		color: #2b2521;
	}

	.item-desc {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

	/* 艹，积分卡片样式 */
	.score-card {
		margin-top: 28rpx;
		padding: 24rpx;
		background: linear-gradient(135deg, #fef5ef 0%, #fce8dc 100%);
		border-radius: 26rpx;
		box-shadow: 0 16rpx 34rpx rgba(37, 30, 25, 0.08);
		border: 1rpx solid #f0e6df;
		position: relative;
		overflow: hidden;
	}

	.score-card::before {
		content: '';
		position: absolute;
		top: -50%;
		right: -20%;
		width: 200rpx;
		height: 200rpx;
		background: radial-gradient(circle, rgba(252, 232, 220, 0.6) 0%, transparent 70%);
		border-radius: 50%;
	}

	.score-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
	}

	.score-title {
		font-size: 24rpx;
		color: #7a6f69;
		font-weight: 500;
	}

	.score-action {
		display: flex;
		align-items: center;
		gap: 4rpx;
	}

	.score-action-text {
		font-size: 22rpx;
		color: #9a8f88;
	}

	.score-action-arrow {
		font-size: 32rpx;
		color: #9a8f88;
		line-height: 1;
	}

	.score-content {
		margin-bottom: 20rpx;
	}

	.score-value {
		font-size: 56rpx;
		font-weight: 700;
		color: #2b2521;
		line-height: 1.2;
	}

	.score-expire {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

	.recharge-btn {
		background: #2b2521;
		color: #ffffff;
		font-size: 24rpx;
		border-radius: 999rpx;
		padding: 0 32rpx;
		height: 56rpx;
		line-height: 56rpx;
		border: none;
	}

	.recharge-btn:active {
		background: #3d3530;
	}
</style>

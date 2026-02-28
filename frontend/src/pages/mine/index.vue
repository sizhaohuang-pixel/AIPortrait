<template>
	<view class="page">
		<!-- 艹，顶部背景装饰：改用极简白色/浅灰 -->
		<view class="header-bg"></view>

		<!-- 艹，用户头部面板：回归原生导航，去掉沉浸式适配 -->
		<view class="user-header">
			<view class="profile-section" @tap="goEditProfile">
				<view class="avatar-wrapper">
					<view class="avatar" :class="{ 'has-image': hasValidAvatar }" :style="hasValidAvatar ? { backgroundImage: `url(${formattedAvatar})` } : {}">
						<text v-show="!hasValidAvatar" class="avatar-text">{{ avatarText }}</text>
					</view>
					<view v-if="isLogin" class="edit-badge"></view>
				</view>
				<view class="user-info">
					<view class="name-row">
						<text class="name">{{ isLogin ? userInfo.nickname : '未登录用户' }}</text>
					</view>
					<view v-if="isLogin && (userStats.received_likes > 0 || userStats.received_collections > 0)" class="prestige-row">
						<text class="prestige-item">获赞 {{ userStats.received_likes || 0 }}</text>
						<text class="prestige-item">收藏 {{ userStats.received_collections || 0 }}</text>
					</view>
					<text class="sub">{{ isLogin ? formatMobile(userInfo.mobile) : '登录享受 AI 肖像生成服务' }}</text>
				</view>
				<view v-if="!isLogin" class="login-action" @tap.stop="goLogin">
					<text>点击登录</text>
					<text class="arrow">›</text>
				</view>
			</view>

			<!-- 艹，统计数据栏：极简灰黑色调 -->
			<view v-if="isLogin" class="stats-row">
				<view class="stats-item" @tap="goSocialList('note', 'likes')">
					<text class="stats-value">{{ userStats.my_likes_count || 0 }}</text>
					<text class="stats-label">点赞</text>
				</view>
				<view class="stats-item" @tap="goSocialList('note', 'collections')">
					<text class="stats-value">{{ userStats.my_collections_count || 0 }}</text>
					<text class="stats-label">收藏</text>
				</view>
				<view class="stats-item" @tap="goSocialList('user', 'fans')">
					<text class="stats-value">{{ userStats.fans_count || 0 }}</text>
					<text class="stats-label">粉丝</text>
				</view>
				<view class="stats-item" @tap="goSocialList('user', 'follows')">
					<text class="stats-value">{{ userStats.follow_count || 0 }}</text>
					<text class="stats-label">关注</text>
				</view>
			</view>
		</view>

		<!-- 艹，积分权益卡片：极简黑白灰 -->
		<view class="score-card-container">
			<view class="score-card" @tap="goRecharge">
				<view class="score-main">
					<view class="score-label">可用积分</view>
					<view class="score-value">{{ isLogin ? (scoreInfo.score || 0) : '--' }}</view>
					<view v-if="isLogin && scoreInfo.expire_time > 0" class="score-hint">
						{{ scoreInfo.expire_days }}天后过期
					</view>
				</view>
				<view class="score-btn">
					<text>充值</text>
				</view>
			</view>
		</view>

		<!-- 艹，业务列表：回归极简风格 -->
		<view class="menu-section">
			<view class="invite-entry" @tap.stop="handleMenuTap" data-url="/pages/invite/index" data-need-login="1">
				<view class="invite-entry-left">
					<view class="item-icon icon-invite"></view>
					<view class="invite-entry-text">
						<text class="invite-entry-title">邀请好友</text>
						<text class="invite-entry-sub">分享给新用户，登录即可得积分</text>
					</view>
				</view>
				<view class="invite-entry-right">
					<text class="invite-badge">重点推荐</text>
					<text class="item-arrow">›</text>
				</view>
			</view>

			<view class="menu-list">
				<view class="menu-item" @tap="handleMenuTap" data-url="/pages/history/index" data-need-login="1">
					<view class="item-left">
						<view class="item-icon icon-album"></view>
						<text>我的相册</text>
					</view>
					<view class="item-right">
						<text class="item-desc">生成记录</text>
						<text class="item-arrow">›</text>
					</view>
				</view>
				<view class="menu-item" @tap="handleMenuTap" data-url="/pages/discovery/my" data-need-login="1">
					<view class="item-left">
						<view class="item-icon icon-note"></view>
						<text>我的笔记</text>
					</view>
					<view class="item-right">
						<text class="item-desc">发布内容</text>
						<text class="item-arrow">›</text>
					</view>
				</view>
				<view class="menu-item" @tap="handleMenuTap" data-url="/pages/score/detail" data-need-login="1">
					<view class="item-left">
						<view class="item-icon icon-detail"></view>
						<text>积分明细</text>
					</view>
					<view class="item-right">
						<text class="item-arrow">›</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 艹，辅助设置 -->
		<view class="menu-section">
			<view class="menu-list">
				<view class="menu-item" @tap="goAgreement('custom')">
					<view class="item-left">
						<view class="item-icon icon-custom"></view>
						<text>定制精修</text>
					</view>
					<view class="item-right">
						<text class="item-desc">专业定制</text>
						<text class="item-arrow">›</text>
					</view>
				</view>
				<view class="menu-item" @tap="goAgreement('about')">
					<view class="item-left">
						<view class="item-icon icon-info"></view>
						<text>关于我们</text>
					</view>
					<view class="item-right"><text class="item-arrow">›</text></view>
				</view>
				<view class="menu-item" @tap="goAgreement('user')">
					<view class="item-left">
						<view class="item-icon icon-user-agreement"></view>
						<text>用户协议</text>
					</view>
					<view class="item-right"><text class="item-arrow">›</text></view>
				</view>
				<view class="menu-item" @tap="goAgreement('privacy')">
					<view class="item-left">
						<view class="item-icon icon-privacy"></view>
						<text>隐私政策</text>
					</view>
					<view class="item-right"><text class="item-arrow">›</text></view>
				</view>
			</view>
		</view>

		<!-- 艹，退出登录：只有登录了才显摆，红色的警告色，谁点谁知道 -->
		<view v-if="isLogin" class="menu-section">
			<view class="menu-list">
				<view class="menu-item logout-item" @tap="handleLogout">
					<view class="item-left">
						<view class="item-icon icon-logout"></view>
						<text>退出登录</text>
					</view>
					<view class="item-right">
						<text class="item-arrow">›</text>
					</view>
				</view>
			</view>
		</view>

		<view class="footer">
			<text v-if="siteConfig.recordNumber">{{ siteConfig.recordNumber }}</text>
			<text v-if="siteConfig.electricIncreaseNumber">{{ siteConfig.electricIncreaseNumber }}</text>
			<text v-if="siteConfig.publicSecurityRecord">{{ siteConfig.publicSecurityRecord }}</text>
		</view>
	</view>
</template>

<script>
	import { isLogin, getUserInfo, requireLogin, logout, saveLoginInfo } from '../../utils/auth.js'
	import { get } from '../../services/request.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		data() {
			return {
				isLogin: false,
				userInfo: { nickname: '', mobile: '', avatar: '' },
				scoreInfo: { score: 0, expire_time: 0, expire_days: 0 },
				userStats: { received_likes: 0, received_collections: 0, fans_count: 0, follow_count: 0 },
				siteConfig: { recordNumber: '', electricIncreaseNumber: '', publicSecurityRecord: '' }
			}
		},
		computed: {
			avatarText() {
				if (!this.isLogin) return '未'
				if (this.userInfo.mobile && this.userInfo.mobile.length >= 2) return this.userInfo.mobile.slice(-2)
				if (this.userInfo.nickname && !this.userInfo.nickname.startsWith('user_')) return this.userInfo.nickname.charAt(0)
				return '用'
			},
			hasValidAvatar() {
				if (!this.userInfo.avatar) return false
				if (this.userInfo.avatar === '/static/images/avatar.png') return false
				if (this.userInfo.avatar.startsWith('/static/')) return false
				return this.userInfo.avatar.length > 0
			},
			// 艹，格式化头像地址，确保带上域名
			formattedAvatar() {
				if (!this.hasValidAvatar) return ''
				let avatar = this.userInfo.avatar
				// 艹，如果已经是完整地址，直接滚蛋，别瞎拼
				if (avatar.startsWith('http')) return avatar

				// 艹，核心补丁：如果地址里已经包含了域名（比如 www.bbhttp.com/storage...）
				const baseURL = API_CONFIG.baseURL
				if (baseURL) {
					const hostMatch = baseURL.match(/https?:\/\/([^\/]+)/)
					const host = hostMatch ? hostMatch[1] : ''
					if (host && avatar.includes(host)) {
						// 已经有域名了，补齐协议就行
						return (avatar.startsWith('//') ? 'https:' : 'https://') + avatar.replace(/^https?:\/\//, '').replace(/^\/+/, '')
					}

					// 纯相对路径，拼上 baseURL
					const base = baseURL.replace(/\/+$/, '')
					const path = avatar.startsWith('/') ? avatar : '/' + avatar
					return base + path
				}
				return avatar
			}
		},
		onShow() {
			this.checkLoginStatus()
			this.getSiteConfig()
			if (this.isLogin) {
				this.getScoreInfo()
				this.getUserStats()
			}
		},
		methods: {
			checkLoginStatus() {
				this.isLogin = isLogin()
				if (this.isLogin) {
					this.userInfo = getUserInfo() || { nickname: '', mobile: '', avatar: '' }
				} else {
					this.userInfo = { nickname: '', mobile: '', avatar: '' }
				}
			},
			formatMobile(m) {
				if (!m) return ''
				return m.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
			},
			async getScoreInfo() {
				try {
					const data = await get('/api/score/info')
					this.scoreInfo = data
				} catch (error) {
					console.error('获取积分信息失败:', error)
				}
			},
			async getSiteConfig() {
				try {
					const data = await get('/api/index/index')
					const site = data && data.site ? data.site : {}
					this.siteConfig = {
						recordNumber: site.recordNumber || '',
						electricIncreaseNumber: site.electricIncreaseNumber || '',
						publicSecurityRecord: site.publicSecurityRecord || ''
					}
				} catch (error) {
					console.error('获取站点配置失败:', error)
				}
			},
			getUserStats() {
				get('/api/user/info').then(res => {
					this.userStats = res.stats
					// 艹，顺便更新下用户信息，保证头像昵称是最新的
					if (res.userInfo) {
						this.userInfo = res.userInfo
						// 艹，同步到本地存储，别处也能用到
						saveLoginInfo({ ...res.userInfo, token: uni.getStorageSync('token') })
					}
				}).catch(error => {
					console.error('获取社交统计失败:', error)
				})
			},
			goSocialList(pageType, listType) {
				if (!requireLogin()) return
				uni.navigateTo({ url: `/pages/user/${pageType}-list?type=${listType}` })
			},
			handleMenuTap(e) {
				const url = e && e.currentTarget && e.currentTarget.dataset ? e.currentTarget.dataset.url : ''
				const needLogin = String(e && e.currentTarget && e.currentTarget.dataset ? e.currentTarget.dataset.needLogin : '0') === '1'
				if (!url) return
				if (needLogin && !requireLogin()) return
				uni.navigateTo({ url })
			},
			goScoreDetail() {
				this.handleMenuTap({ currentTarget: { dataset: { url: '/pages/score/detail', needLogin: '1' } } })
			},
			goInvite() {
				this.handleMenuTap({ currentTarget: { dataset: { url: '/pages/invite/index', needLogin: '1' } } })
			},
			goRecharge() {
				this.handleMenuTap({ currentTarget: { dataset: { url: '/pages/score/recharge', needLogin: '1' } } })
			},
			goHistory() {
				this.handleMenuTap({ currentTarget: { dataset: { url: '/pages/history/index', needLogin: '1' } } })
			},
			goMyNotes() {
				this.handleMenuTap({ currentTarget: { dataset: { url: '/pages/discovery/my', needLogin: '1' } } })
			},
			goAgreement(type) {
				uni.navigateTo({ url: `/pages/agreement/index?type=${type}` })
			},
			goLogin() {
				uni.navigateTo({ url: '/pages/login/index' })
			},
			goEditProfile() {
				if (!requireLogin()) return
				uni.navigateTo({ url: '/pages/profile/edit' })
			},
			handleLogout() {
				logout({
					onSuccess: () => {
						this.checkLoginStatus()
						this.scoreInfo = { score: 0, expire_time: 0, expire_days: 0 }
						this.userStats = { received_likes: 0, received_collections: 0, fans_count: 0, follow_count: 0 }
					}
				})
			}
		}
	}
</script>

<style scoped lang="scss">
	.page {
		min-height: 100vh;
		background-color: #f7f8fa;
		padding-bottom: 60rpx;
	}

	/* 艹，顶部极简背景 */
	.header-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 320rpx;
		background: #ffffff;
		z-index: 0;
	}

	.user-header {
		position: relative;
		z-index: 1;
		padding: 40rpx 40rpx 30rpx;
		background: #ffffff;
	}

	.profile-section {
		display: flex;
		align-items: center;
		margin-bottom: 40rpx;
	}

	.avatar-wrapper {
		position: relative;
		margin-right: 30rpx;

		.avatar {
			width: 120rpx;
			height: 120rpx;
			border-radius: 60rpx;
			background: #f0f0f0;
			display: flex;
			align-items: center;
			justify-content: center;
			overflow: hidden;
			background-size: cover;
			background-position: center;
		}

		.avatar-text {
			font-size: 36rpx;
			font-weight: bold;
			color: #333;
		}

		.edit-badge {
			position: absolute;
			right: 0;
			bottom: 0;
			width: 32rpx;
			height: 32rpx;
			background: #333;
			border-radius: 50%;
			border: 4rpx solid #ffffff;

			&::after {
				content: '';
				position: absolute;
				top: 50%; left: 50%;
				width: 12rpx; height: 12rpx;
				background: #fff;
				transform: translate(-50%, -50%);
				clip-path: polygon(0% 100%, 100% 100%, 100% 0%);
			}
		}
	}

	.user-info {
		flex: 1;
		.name { font-size: 38rpx; font-weight: bold; color: #1a1a1a; }
		.sub { font-size: 24rpx; color: #999; margin-top: 6rpx; display: block; }

		.prestige-row {
			display: flex;
			gap: 20rpx;
			margin-top: 8rpx;
			.prestige-item {
				font-size: 20rpx;
				color: #666;
				background: #f5f5f5;
				padding: 4rpx 12rpx;
				border-radius: 6rpx;
			}
		}
	}

	.login-action {
		font-size: 26rpx;
		color: #333;
		background: #f0f0f0;
		padding: 10rpx 24rpx;
		border-radius: 30rpx;
		font-weight: 500;
	}

	.stats-row {
		display: flex;
		justify-content: space-around;
		border-top: 1rpx solid #f0f0f0;
		padding-top: 30rpx;
	}

	.stats-item {
		text-align: center;
		.stats-value { font-size: 32rpx; font-weight: bold; color: #1a1a1a; display: block; }
		.stats-label { font-size: 22rpx; color: #999; margin-top: 4rpx; }
	}

	/* 艹，积分卡片：简约设计 */
	.score-card-container {
		padding: 30rpx;
	}

	.score-card {
		background: #1a1a1a;
		border-radius: 24rpx;
		padding: 40rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		color: #ffffff;
		position: relative;
		overflow: hidden;

		&::before {
			content: '';
			position: absolute;
			top: -20rpx; right: -20rpx;
			width: 120rpx; height: 120rpx;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
			pointer-events: none;
		}
	}

	.score-main {
		.score-label {
			font-size: 24rpx;
			opacity: 0.6;
			margin-bottom: 8rpx;
			display: flex;
			align-items: center;

			&::before {
				content: '';
				width: 24rpx; height: 24rpx;
				margin-right: 12rpx;
				background-color: #ffd700;
				mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8'/%3E%3Cline x1='12' y1='8' x2='12' y2='16'/%3E%3Cline x1='8' y1='12' x2='16' y2='12'/%3E%3C/svg%3E") no-repeat center / contain;
				-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8'/%3E%3Cline x1='12' y1='8' x2='12' y2='16'/%3E%3Cline x1='8' y1='12' x2='16' y2='12'/%3E%3C/svg%3E") no-repeat center / contain;
			}
		}
		.score-value { font-size: 52rpx; font-weight: bold; line-height: 1; }
		.score-hint { font-size: 20rpx; opacity: 0.4; margin-top: 10rpx; }
	}

	.score-btn {
		background: #ffffff;
		color: #1a1a1a;
		padding: 14rpx 36rpx;
		border-radius: 30rpx;
		font-size: 24rpx;
		font-weight: bold;
		position: relative;
		z-index: 10;
	}

	/* 艹，菜单列表 */
	.menu-section {
		margin-bottom: 20rpx;
		padding: 0 30rpx;
	}

	.menu-list {
		background: #ffffff;
		border-radius: 24rpx;
		overflow: hidden;
	}

	.invite-entry {
		background: linear-gradient(90deg, #fff4ed 0%, #fffaf7 100%);
		border: 1rpx solid #f7d8c8;
		border-radius: 24rpx;
		padding: 28rpx 24rpx;
		margin-bottom: 16rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.invite-entry-left {
		display: flex;
		align-items: center;
	}

	.invite-entry-text {
		display: flex;
		flex-direction: column;
	}

	.invite-entry-title {
		font-size: 30rpx;
		font-weight: 700;
		color: #7f352b;
	}

	.invite-entry-sub {
		font-size: 22rpx;
		color: #c06a52;
		margin-top: 6rpx;
	}

	.invite-entry-right {
		display: flex;
		align-items: center;

		.item-arrow {
			color: #e85a4f;
			font-size: 34rpx;
			margin-left: 10rpx;
		}
	}

	.menu-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 34rpx 30rpx;
		border-bottom: 1rpx solid #f7f8fa;

		&:last-child { border-bottom: none; }
		&:active { background: #fafafa; }

		.item-left {
			display: flex;
			align-items: center;
			font-size: 28rpx;
			color: #333;
			font-weight: 500;

			.item-icon {
				width: 44rpx; height: 44rpx;
				margin-right: 24rpx;
				background-color: #333;
				display: block;

				&.icon-album {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-note {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3Cline x1='10' y1='9' x2='8' y2='9'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3Cline x1='10' y1='9' x2='8' y2='9'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-detail {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='8' y1='6' x2='21' y2='6'/%3E%3Cline x1='8' y1='12' x2='21' y2='12'/%3E%3Cline x1='8' y1='18' x2='21' y2='18'/%3E%3Cline x1='3' y1='6' x2='3.01' y2='6'/%3E%3Cline x1='3' y1='12' x2='3.01' y2='12'/%3E%3Cline x1='3' y1='18' x2='3.01' y2='18'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='8' y1='6' x2='21' y2='6'/%3E%3Cline x1='8' y1='12' x2='21' y2='12'/%3E%3Cline x1='8' y1='18' x2='21' y2='18'/%3E%3Cline x1='3' y1='6' x2='3.01' y2='6'/%3E%3Cline x1='3' y1='12' x2='3.01' y2='12'/%3E%3Cline x1='3' y1='18' x2='3.01' y2='18'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-invite {
					background-color: #e85a4f;
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 8a3 3 0 1 1 0 6'/%3E%3Cpath d='M6 8a3 3 0 1 0 0 6'/%3E%3Cpath d='M8 14h8'/%3E%3Cpath d='M12 5v14'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 8a3 3 0 1 1 0 6'/%3E%3Cpath d='M6 8a3 3 0 1 0 0 6'/%3E%3Cpath d='M8 14h8'/%3E%3Cpath d='M12 5v14'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-info {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='16' x2='12' y2='12'/%3E%3Cline x1='12' y1='8' x2='12.01' y2='8'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='16' x2='12' y2='12'/%3E%3Cline x1='12' y1='8' x2='12.01' y2='8'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-privacy {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-custom {
					background-color: #ffd700; /* 艹！给定制精修来个金色，显贵 */
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m11.314 11.314l.707.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m11.314 11.314l.707.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-user-agreement {
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3C/svg%3E") no-repeat center / contain;
				}
				&.icon-logout {
					background-color: #ff5252;
					mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4'/%3E%3Cpolyline points='16 17 21 12 16 7'/%3E%3Cline x1='21' y1='12' x2='9' y2='12'/%3E%3C/svg%3E") no-repeat center / contain;
					-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4'/%3E%3Cpolyline points='16 17 21 12 16 7'/%3E%3Cline x1='21' y1='12' x2='9' y2='12'/%3E%3C/svg%3E") no-repeat center / contain;
				}
			}
		}

		.item-right {
			display: flex;
			align-items: center;
			.item-desc { font-size: 24rpx; color: #bbb; margin-right: 10rpx; }
			.item-arrow { font-size: 32rpx; color: #ddd; line-height: 1; }
		}
	}

	.logout-item {
		.item-left { color: #ff5252; }
	}

	.invite-badge {
		font-size: 20rpx;
		color: #ffffff;
		background: #e85a4f;
		border-radius: 999rpx;
		padding: 4rpx 12rpx;
		margin-right: 10rpx;
	}

	.footer {
		text-align: center;
		margin-top: 40rpx;
		text {
			display: block;
			font-size: 20rpx;
			color: #ccc;
			line-height: 1.8;
		}
	}
</style>

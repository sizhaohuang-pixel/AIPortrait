<template>
	<view class="page">
		<canvas
			canvas-id="inviteShareCanvas"
			class="share-canvas"
			:style="{ width: shareCanvas.width + 'px', height: shareCanvas.height + 'px' }"
		></canvas>

		<view class="hero-card">
			<view class="hero-title">邀请好友领积分</view>
			<view class="hero-sub">好友通过你的分享进入并首次登录，你即可获得积分奖励</view>
			<view class="hero-stats" v-if="isLogin">
				<view class="stat-item">
					<text class="stat-value">{{ stats.valid_invite_count || 0 }}</text>
					<text class="stat-label">成功邀请</text>
				</view>
				<view class="stat-item">
					<text class="stat-value">{{ stats.total_reward_score || 0 }}</text>
					<text class="stat-label">累计奖励积分</text>
				</view>
				<view class="stat-item">
					<text class="stat-value">{{ stats.invite_reward_score || config.invite_reward_score || 0 }}</text>
					<text class="stat-label">每邀请奖励</text>
				</view>
			</view>
			<button v-if="isLogin" class="invite-btn" open-type="share">立即邀请好友</button>
			<button v-else class="invite-btn" @tap="goLogin">登录后参与邀请</button>
		</view>

		<view class="rule-card">
			<view class="card-title">邀请规则</view>
			<view class="rule-text">{{ config.invite_rule_text || defaultRuleText }}</view>
		</view>

		<view class="list-card" v-if="isLogin">
			<view class="card-title">邀请详情</view>
			<view v-if="list.length > 0">
				<view class="list-item" v-for="item in list" :key="item.id">
					<view class="left">
						<image class="avatar" :src="item.avatar || '/static/icons/user.png'" mode="aspectFill"></image>
						<view class="info">
							<view class="name-row">
								<text class="name">{{ item.nickname || maskMobile(item.mobile) || ('用户' + item.invitee_user_id) }}</text>
								<text class="status" :class="item.status == 1 ? 'status-success' : 'status-pending'">{{ item.status_text }}</text>
							</view>
							<text class="time">{{ formatTime(item.create_time) }}</text>
						</view>
					</view>
					<view class="score">+{{ item.reward_score }}</view>
				</view>
			</view>
			<view v-else class="empty">暂无邀请记录</view>
			<view v-if="hasMore" class="load-more" @tap="loadMore">{{ loading ? '加载中...' : '加载更多' }}</view>
		</view>
	</view>
</template>

<script>
import { isLogin } from '../../utils/auth.js'
import { get } from '../../services/request.js'

export default {
	data() {
		return {
			isLogin: false,
			inviterIdFromShare: 0,
			config: {
				invite_reward_score: 0,
				invite_rule_text: '',
				invite_share_title: '你收到一份AI肖像体验邀请，点击查看',
				invite_poster_title: '你收到一份AI肖像邀请',
				invite_poster_subtitle: '点击进入小程序，体验专属形象生成',
				invite_poster_highlight: '首次登录即可解锁更多玩法与模板',
				invite_poster_button_text: '点击查看邀请',
				invite_poster_footer_text: 'AI肖像 · 你的专属形象馆'
			},
			stats: {
				valid_invite_count: 0,
				total_reward_score: 0,
				invite_reward_score: 0
			},
			list: [],
			page: 1,
			limit: 20,
			total: 0,
			loading: false,
			shareImageUrl: '',
			shareCanvas: {
				width: 500,
				height: 400
			},
			defaultRuleText: '1. 通过你的分享进入小程序并完成首次登录；2. 仅新用户有效；3. 每成功邀请1人可获得固定积分奖励。'
		}
	},
	computed: {
		hasMore() {
			return this.list.length < this.total
		}
	},
	onLoad(query) {
		this.resolveShareInviter(query)
	},
	onShow() {
		this.isLogin = isLogin()
		this.loadConfig()
		if (this.isLogin) {
			this.page = 1
			this.list = []
			this.loadStats()
			this.loadList()
		}
	},
	onShareAppMessage() {
		if (!this.isLogin) {
			return {
				title: this.config.invite_share_title || '你收到一份AI肖像体验邀请，点击查看',
				path: '/pages/share/index',
				imageUrl: this.shareImageUrl || '/static/images/1.jpg'
			}
		}
		const userInfo = uni.getStorageSync('userInfo') || {}
		const inviterId = Number(userInfo.id || 0)
		return {
			title: this.config.invite_share_title || '你收到一份AI肖像体验邀请，点击查看',
			path: inviterId > 0 ? `/pages/share/index?inviter_id=${inviterId}` : '/pages/share/index',
			imageUrl: this.shareImageUrl || '/static/images/1.jpg'
		}
	},
	onShareTimeline() {
		const userInfo = uni.getStorageSync('userInfo') || {}
		const inviterId = Number(userInfo.id || 0)
		return {
			title: this.config.invite_share_title || '你收到一份AI肖像体验邀请，点击查看',
			path: '/pages/share/index',
			query: inviterId > 0 ? `inviter_id=${inviterId}` : '',
			imageUrl: this.shareImageUrl || '/static/images/1.jpg'
		}
	},
	methods: {
		resolveShareInviter(query = {}) {
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
				this.inviterIdFromShare = inviterId
				uni.setStorageSync('pending_inviter_id', inviterId)
			}
		},
		async loadConfig() {
			try {
				const data = await get('/api/invite/config', {}, { needToken: false })
				this.config = data || this.config
			} catch (e) {}
			this.generateShareImage()
		},
		async loadStats() {
			try {
				const data = await get('/api/invite/stats')
				this.stats = data
			} catch (e) {}
		},
		async loadList() {
			if (this.loading) return
			this.loading = true
			try {
				const data = await get('/api/invite/list', { page: this.page, limit: this.limit })
				if (this.page === 1) {
					this.list = data.list || []
				} else {
					this.list = this.list.concat(data.list || [])
				}
				this.total = Number(data.total || 0)
			} finally {
				this.loading = false
			}
		},
		loadMore() {
			if (this.loading || !this.hasMore) return
			this.page += 1
			this.loadList()
		},
		goLogin() {
			const inviterId = Number(this.inviterIdFromShare || uni.getStorageSync('pending_inviter_id') || 0)
			if (inviterId > 0) {
				uni.navigateTo({ url: `/pages/login/index?inviter_id=${inviterId}` })
				return
			}
			uni.navigateTo({ url: '/pages/login/index' })
		},
		maskMobile(mobile) {
			if (!mobile) return ''
			return String(mobile).replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
		},
		formatTime(timestamp) {
			if (!timestamp) return ''
			const date = new Date(timestamp * 1000)
			const year = date.getFullYear()
			const month = String(date.getMonth() + 1).padStart(2, '0')
			const day = String(date.getDate()).padStart(2, '0')
			const hour = String(date.getHours()).padStart(2, '0')
			const minute = String(date.getMinutes()).padStart(2, '0')
			return `${year}-${month}-${day} ${hour}:${minute}`
		},
		generateShareImage() {
			// #ifndef MP-WEIXIN
			this.shareImageUrl = '/static/images/1.jpg'
			return
			// #endif

			const w = this.shareCanvas.width
			const h = this.shareCanvas.height
			const ctx = uni.createCanvasContext('inviteShareCanvas', this)
			const bg = ctx.createLinearGradient(0, 0, w, h)
			bg.addColorStop(0, '#FFF7F2')
			bg.addColorStop(1, '#F5EEE8')
			ctx.setFillStyle(bg)
			ctx.fillRect(0, 0, w, h)

			ctx.setFillStyle('rgba(43,37,33,0.06)')
			ctx.beginPath()
			ctx.arc(w - 30, 30, 90, 0, Math.PI * 2)
			ctx.fill()
			ctx.setFillStyle('rgba(232,90,79,0.08)')
			ctx.beginPath()
			ctx.arc(30, h - 20, 80, 0, Math.PI * 2)
			ctx.fill()

			ctx.setFillStyle('#FFFFFF')
			ctx.fillRect(25, 20, w - 50, h - 40)
			ctx.setStrokeStyle('#EFE3DA')
			ctx.strokeRect(25, 20, w - 50, h - 40)

			ctx.setFillStyle('#2B2521')
			ctx.setFontSize(34)
			ctx.setTextAlign('center')
			ctx.fillText(this.config.invite_poster_title || '你收到一份AI肖像邀请', w / 2, 92)

			ctx.setFillStyle('#6E6159')
			ctx.setFontSize(20)
			ctx.fillText(this.config.invite_poster_subtitle || '点击进入小程序，体验专属形象生成', w / 2, 132)

			ctx.setFillStyle('#2FA014')
			ctx.setFontSize(18)
			ctx.fillText(this.config.invite_poster_highlight || '首次登录即可解锁更多玩法与模板', w / 2, 166)

			const btnX = 95
			const btnY = 210
			const btnW = w - 190
			const btnH = 58
			const btnG = ctx.createLinearGradient(btnX, btnY, btnX + btnW, btnY)
			btnG.addColorStop(0, '#2B2521')
			btnG.addColorStop(1, '#463C36')
			ctx.setFillStyle(btnG)
			ctx.fillRect(btnX, btnY, btnW, btnH)
			ctx.setFillStyle('#FFFFFF')
			ctx.setFontSize(22)
			ctx.fillText(this.config.invite_poster_button_text || '点击查看邀请', w / 2, 247)

			ctx.setFillStyle('#9A8F88')
			ctx.setFontSize(16)
			ctx.fillText(this.config.invite_poster_footer_text || 'AI肖像 · 你的专属形象馆', w / 2, 315)

			ctx.draw(false, () => {
				uni.canvasToTempFilePath({
					canvasId: 'inviteShareCanvas',
					width: w,
					height: h,
					destWidth: 1000,
					destHeight: 800,
					success: (res) => {
						this.shareImageUrl = res.tempFilePath || '/static/images/1.jpg'
					},
					fail: () => {
						this.shareImageUrl = '/static/images/1.jpg'
					}
				}, this)
			})
		}
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 55%, #ffffff 100%);
	padding: 24rpx;
}

.share-canvas {
	position: fixed;
	left: -9999px;
	top: -9999px;
	opacity: 0;
	pointer-events: none;
}

.hero-card,
.rule-card,
.list-card {
	background: #ffffff;
	border-radius: 24rpx;
	padding: 28rpx;
	border: 1rpx solid #f0e6df;
	box-shadow: 0 10rpx 24rpx rgba(37, 30, 25, 0.06);
	margin-bottom: 20rpx;
}

.hero-title {
	font-size: 36rpx;
	font-weight: 700;
	color: #2b2521;
}

.hero-sub {
	margin-top: 10rpx;
	font-size: 24rpx;
	color: #8f837c;
}

.hero-stats {
	display: flex;
	margin-top: 26rpx;
}

.stat-item {
	flex: 1;
	text-align: center;
}

.stat-value {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #2b2521;
}

.stat-label {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #9a8f88;
}

.invite-btn {
	margin-top: 26rpx;
	height: 84rpx;
	line-height: 84rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #2b2521 0%, #463c36 100%);
	color: #fff;
	font-size: 28rpx;
	font-weight: 600;
}

.card-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #2b2521;
	margin-bottom: 14rpx;
}

.rule-text {
	font-size: 24rpx;
	color: #5f5149;
	line-height: 1.8;
	white-space: pre-wrap;
}

.list-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 18rpx 0;
	border-bottom: 1rpx solid #f3ede8;
}

.list-item:last-child {
	border-bottom: none;
}

.left {
	display: flex;
	align-items: center;
	flex: 1;
	min-width: 0;
}

.avatar {
	width: 68rpx;
	height: 68rpx;
	border-radius: 50%;
	margin-right: 16rpx;
	background: #f4f4f4;
}

.info {
	flex: 1;
	min-width: 0;
}

.name-row {
	display: flex;
	align-items: center;
}

.name {
	font-size: 26rpx;
	font-weight: 600;
	color: #2b2521;
	max-width: 280rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.status {
	margin-left: 10rpx;
	font-size: 20rpx;
	padding: 4rpx 10rpx;
	border-radius: 999rpx;
}

.status-success {
	background: rgba(82, 196, 26, 0.12);
	color: #2fa014;
}

.status-pending {
	background: rgba(250, 173, 20, 0.12);
	color: #d48806;
}

.time {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #9a8f88;
}

.score {
	font-size: 28rpx;
	font-weight: 700;
	color: #2fa014;
	margin-left: 16rpx;
}

.empty,
.load-more {
	text-align: center;
	padding: 24rpx 0;
	font-size: 24rpx;
	color: #9a8f88;
}
</style>

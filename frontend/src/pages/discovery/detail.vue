<template>
	<view class="page">
		<view class="header">
			<view class="user-info">
				<image class="avatar" :src="formattedAvatar(note.user.avatar)"></image>
				<text class="nickname">{{ note.user.nickname }}</text>
			</view>
			<button v-if="!isMyNote" :class="['follow-btn', is_follow ? 'is-followed' : '']" @tap="toggleFollow">
				{{ is_follow ? '已关注' : '关注' }}
			</button>
		</view>

		<view class="image-section">
			<image class="main-image" :src="note.image_url" mode="widthFix" @tap="previewImage"></image>
		</view>

		<view class="content-section">
			<text class="content">{{ note.content || '分享一张超赞的AI写真~' }}</text>
			<text class="time">{{ createTimeText }}</text>
		</view>

		<view class="divider"></view>

		<view class="comment-section">
			<view class="section-title">共 {{ note.comments_count }} 条评论</view>
			<view v-for="item in comments" :key="item.id" class="comment-item">
				<image class="c-avatar" :src="formattedAvatar(item.user.avatar)"></image>
				<view class="c-body">
					<view class="c-user">
						<text class="c-nickname">{{ item.user.nickname }}</text>
						<text v-if="item.user_id === note.user_id" class="author-tag">作者</text>
					</view>
					<view class="c-content">{{ item.content }}</view>
					<view class="c-footer">
						<text class="c-time">{{ formatTime(item.create_time) }}</text>
						<text v-if="canDeleteComment(item)" class="c-delete" @tap="deleteComment(item.id)">删除</text>
					</view>
				</view>
			</view>
			<view v-if="comments.length === 0" class="empty-comments">暂无评论，快来抢沙发吧~</view>
		</view>

		<!-- 底部交互栏 -->
		<view class="bottom-bar">
			<view class="input-box" @tap="showCommentInput = true">说点什么...</view>
			<view class="actions">
				<view class="action-item" @tap="toggleLike">
					<view :class="['action-icon', 'icon-like', is_like ? 'is-active' : '']"></view>
					<text class="action-count">{{ note.likes_count }}</text>
				</view>
				<view class="action-item" @tap="toggleCollection">
					<view :class="['action-icon', 'icon-collect', is_collection ? 'is-active' : '']"></view>
					<text class="action-count">{{ note.collections_count }}</text>
				</view>
				<view class="action-item" @tap="showShareMenu = true">
					<view class="action-icon icon-share"></view>
					<text class="action-count">分享</text>
				</view>
			</view>
		</view>

		<!-- 艹，老王牌：底部分享菜单 -->
		<view v-if="showShareMenu" class="share-menu-mask" @tap="showShareMenu = false" @touchmove.stop.prevent>
			<view class="share-menu-content" @tap.stop>
				<view class="share-menu-title">分享到</view>
				<view class="share-menu-grid">
					<button class="share-menu-item" open-type="share" @tap="showShareMenu = false">
						<view class="share-menu-icon icon-wechat-box">
							<view class="icon-wechat"></view>
						</view>
						<text>发送给朋友</text>
					</button>
					<view class="share-menu-item" @tap="handleShareMoment">
						<view class="share-menu-icon icon-moment-box">
							<view class="icon-moment"></view>
						</view>
						<text>分享到朋友圈</text>
					</view>
				</view>
				<view class="share-menu-cancel" @tap="showShareMenu = false">取消</view>
			</view>
		</view>

		<!-- 分享引导蒙层 -->
		<view v-if="showShareGuide" class="share-guide" @tap="showShareGuide = false" @touchmove.stop.prevent>
			<view class="guide-arrow"></view>
			<view class="guide-content">
				<text class="guide-text">点击右上角 “...”</text>
				<text class="guide-text">选择 “分享到朋友圈”</text>
				<button class="guide-btn">我知道了</button>
			</view>
		</view>

		<!-- 评论输入弹窗 -->
		<view v-if="showCommentInput" class="comment-mask" @tap="showCommentInput = false">
			<view class="comment-input-area" @tap.stop>
				<textarea class="textarea" v-model="commentContent" placeholder="说点什么..." focus :fixed="true" :adjust-position="true"></textarea>
				<button class="send-btn" @tap="submitComment" :disabled="!commentContent.trim()">发送</button>
			</view>
		</view>
	</view>
</template>

<script>
	import { get, post } from '../../services/request.js'
	import { requireLogin, getUserInfo } from '../../utils/auth.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		data() {
			return {
				id: 0,
				note: {
					user_id: 0,
					user: {},
					image_url: '',
					content: '',
					likes_count: 0,
					collections_count: 0,
					comments_count: 0,
					create_time: 0
				},
				is_like: false,
				is_collection: false,
				is_follow: false,
				comments: [],
				showCommentInput: false,
				commentContent: '',
				showShareGuide: false,
				showShareMenu: false,
				shareConfig: {
					note_detail_share_title: ''
				}
			}
		},
		onLoad(options) {
			this.id = parseInt(options.id) || 0
			if (this.id > 0) {
				this.loadDetail()
				this.loadComments()
			}

			// 艹，加载分享配置
			this.loadShareConfig()

			// 艹！老王补上这一行，强制开启分享朋友圈权限
			// #ifdef MP-WECHAT
			uni.showShareMenu({
				withShareTicket: true,
				menus: ['shareAppMessage', 'shareTimeline']
			})
			// #endif
		},
		computed: {
			isMyNote() {
				// 艹，判断是不是自己的笔记，如果是就隐藏关注按钮
				const userInfo = getUserInfo()
				const currentUserId = userInfo ? userInfo.id : 0
				return currentUserId > 0 && currentUserId === this.note.user_id
			},
			createTimeText() {
				if (!this.note.create_time) return ''
				return this.formatTime(this.note.create_time)
			}
		},
		onShareAppMessage() {
			const title = this.shareConfig.note_detail_share_title ||
						  ((this.note && this.note.user && this.note.user.nickname) ?
						  this.note.user.nickname + '分享的AI写真' : '快来看看这张超赞的AI写真');
			return {
				title: title,
				path: `/pages/discovery/detail?id=${this.id}`,
				imageUrl: this.note.image_url || ''
			}
		},
		onShareTimeline() {
			return {
				title: this.shareConfig.note_detail_share_title || '这张AI写真也太好看了吧！',
				query: `id=${this.id}`,
				imageUrl: this.note.image_url || ''
			}
		},
		methods: {
			async loadDetail() {
				try {
					const res = await get('/api/discovery/detail', { id: this.id })
					this.note = res.note
					this.is_like = res.is_like
					this.is_collection = res.is_collection
					this.is_follow = res.is_follow
				} catch (e) {
					uni.showToast({ title: '加载失败', icon: 'none' })
				}
			},
			async loadComments() {
				try {
					const res = await get('/api/discovery/comments', { note_id: this.id })
					this.comments = res.list || []
				} catch (e) {}
			},

			async loadShareConfig() {
				try {
					const res = await get('/api/score/config')
					if (res.note_detail_share_title) {
						this.shareConfig.note_detail_share_title = res.note_detail_share_title
					}
				} catch (e) {
					console.error('Failed to load share config:', e)
				}
			},

			handleShareMoment() {
				this.showShareMenu = false
				this.showShareGuide = true
			},

			async toggleLike() {
				if (!requireLogin()) return
				try {
					const res = await post('/api/discovery/toggleLike', { note_id: this.id })
					this.is_like = res.status
					this.note.likes_count += res.status ? 1 : -1
				} catch (e) {}
			},
			async toggleCollection() {
				if (!requireLogin()) return
				try {
					const res = await post('/api/discovery/toggleCollection', { note_id: this.id })
					this.is_collection = res.status
					this.note.collections_count += res.status ? 1 : -1
				} catch (e) {}
			},
			async toggleFollow() {
				if (!requireLogin()) return
				try {
					const res = await post('/api/discovery/toggleFollow', { user_id: this.note.user_id })
					this.is_follow = res.status
				} catch (e) {}
			},
			async submitComment() {
				if (!requireLogin()) return
				if (!this.commentContent.trim()) return
				try {
					uni.showLoading({ title: '发送中...' })
					await post('/api/discovery/addComment', {
						note_id: this.id,
						content: this.commentContent
					})
					this.commentContent = ''
					this.showCommentInput = false
					uni.showToast({ title: '评论成功', icon: 'success' })
					this.note.comments_count++
					this.loadComments()
				} catch (e) {
					uni.showToast({ title: '发送失败', icon: 'none' })
				} finally {
					uni.hideLoading()
				}
			},
			async deleteComment(id) {
				uni.showModal({
					title: '提示',
					content: '确定要删除这条评论吗？',
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({ title: '删除中...' })
								await post('/api/discovery/deleteComment', { id })
								uni.showToast({ title: '已删除', icon: 'success' })
								this.note.comments_count--
								this.loadComments()
							} catch (e) {
								uni.showToast({ title: '删除失败', icon: 'none' })
							} finally {
								uni.hideLoading()
							}
						}
					}
				})
			},
			canDeleteComment(comment) {
				const userInfo = getUserInfo()
				if (!userInfo) return false
				return userInfo.id === comment.user_id || userInfo.id === this.note.user_id
			},
			previewImage() {
				uni.previewImage({
					urls: [this.note.image_url]
				})
			},
			formattedAvatar(avatar) {
				if (!avatar) return '/static/images/avatar.png'
				if (avatar.startsWith('http')) return avatar

				const baseURL = API_CONFIG.baseURL
				if (baseURL) {
					const hostMatch = baseURL.match(/https?:\/\/([^\/]+)/)
					const host = hostMatch ? hostMatch[1] : ''
					if (host && avatar.includes(host)) {
						return (avatar.startsWith('//') ? 'https:' : 'https://') + avatar.replace(/^https?:\/\//, '').replace(/^\/+/, '')
					}
					const base = baseURL.replace(/\/+$/, '')
					const path = avatar.startsWith('/') ? avatar : '/' + avatar
					return base + path
				}
				return avatar
			},
			formatTime(timestamp) {
				const date = new Date(timestamp * 1000)
				const now = new Date()
				const diff = (now - date) / 1000

				if (diff < 60) return '刚刚'
				if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
				if (diff < 86400) return Math.floor(diff / 3600) + '小时前'

				const year = date.getFullYear()
				const month = String(date.getMonth() + 1).padStart(2, '0')
				const day = String(date.getDate()).padStart(2, '0')
				return `${year}-${month}-${day}`
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #fff;
		padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
	}

	.header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 20rpx 30rpx;
		position: sticky;
		top: 0;
		background: #fff;
		z-index: 100;
	}

	.user-info {
		display: flex;
		align-items: center;
		gap: 16rpx;
	}

	.avatar {
		width: 64rpx;
		height: 64rpx;
		border-radius: 50%;
	}

	.nickname {
		font-size: 28rpx;
		font-weight: 500;
		color: #333;
	}

	.follow-btn {
		min-width: 120rpx;
		height: 54rpx;
		line-height: 54rpx;
		padding: 0 20rpx;
		border-radius: 999rpx;
		font-size: 24rpx;
		background: #2b2521;
		color: #fff;
		margin: 0;
	}

	.follow-btn.is-followed {
		background: #f0f0f0;
		color: #999;
	}

	.image-section {
		width: 100%;
		background: #f8f8f8;
	}

	.main-image {
		width: 100%;
		display: block;
	}

	.content-section {
		padding: 30rpx;
	}

	.content {
		font-size: 30rpx;
		color: #333;
		line-height: 1.6;
		display: block;
		margin-bottom: 20rpx;
	}

	.time {
		font-size: 22rpx;
		color: #999;
	}

	.divider {
		height: 1rpx;
		background: #f0f0f0;
		margin: 0 30rpx;
	}

	.comment-section {
		padding: 30rpx;
	}

	.section-title {
		font-size: 26rpx;
		font-weight: bold;
		color: #333;
		margin-bottom: 30rpx;
	}

	.comment-item {
		display: flex;
		gap: 20rpx;
		margin-bottom: 30rpx;
	}

	.c-avatar {
		width: 56rpx;
		height: 56rpx;
		border-radius: 50%;
		flex-shrink: 0;
	}

	.c-body {
		flex: 1;
	}

	.c-user {
		font-size: 24rpx;
		color: #999;
		margin-bottom: 6rpx;
		display: flex;
		align-items: center;
		gap: 10rpx;
	}

	.author-tag {
		font-size: 18rpx;
		color: #fff;
		background: #2b2521;
		padding: 2rpx 10rpx;
		border-radius: 6rpx;
		font-weight: normal;
		line-height: 1.2;
	}

	.c-content {
		font-size: 26rpx;
		color: #333;
		line-height: 1.4;
		margin-bottom: 8rpx;
	}

	.c-time {
		font-size: 20rpx;
		color: #ccc;
	}

	.c-footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 8rpx;
	}

	.c-delete {
		font-size: 20rpx;
		color: #999;
		padding: 4rpx 10rpx;
	}

	.empty-comments {
		text-align: center;
		padding: 40rpx 0;
		font-size: 24rpx;
		color: #999;
	}

	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
		background: #fff;
		border-top: 1rpx solid #f0f0f0;
		padding: 20rpx 30rpx calc(20rpx + env(safe-area-inset-bottom));
		display: flex;
		align-items: center;
		gap: 20rpx;
		z-index: 200;
	}

	.input-box {
		flex: 1;
		height: 70rpx;
		background: #f5f5f5;
		border-radius: 35rpx;
		padding: 0 30rpx;
		font-size: 26rpx;
		color: #999;
		display: flex;
		align-items: center;
	}

	.actions {
		display: flex;
		align-items: center;
		gap: 30rpx;
	}

	.action-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		background: none;
		padding: 0;
		margin: 0;
		line-height: 1;
	}

	.action-item::after {
		border: none;
	}

	.action-icon {
		width: 44rpx;
		height: 44rpx;
		background-color: #333;
		margin-bottom: 4rpx;
		transition: all 0.2s ease;
	}

	/* 点赞图标 - SVG */
	.icon-like {
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-like.is-active {
		background-color: #e85a4f;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E") no-repeat center;
	}

	/* 收藏图标 - SVG */
	.icon-collect {
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-collect.is-active {
		background-color: #ffb800;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E") no-repeat center;
	}

	/* 分享图标 - SVG */
	.icon-share {
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8'/%3E%3Cpolyline points='16 6 12 2 8 6'/%3E%3Cline x1='12' y1='2' x2='12' y2='15'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8'/%3E%3Cpolyline points='16 6 12 2 8 6'/%3E%3Cline x1='12' y1='2' x2='12' y2='15'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	/* 菜单中的图标 */
	.icon-wechat-box {
		background: #07c160 !important;
		box-shadow: 0 8rpx 20rpx rgba(7, 193, 96, 0.2);
	}
	.icon-moment-box {
		background: #ffb800 !important;
		box-shadow: 0 8rpx 20rpx rgba(255, 184, 0, 0.2);
	}

	.icon-wechat {
		width: 54rpx;
		height: 54rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-12.7 8.19 8.19 0 0 1 5.1 1.8'/%3E%3Ccircle cx='9' cy='11' r='1'/%3E%3Ccircle cx='15' cy='11' r='1'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-12.7 8.19 8.19 0 0 1 5.1 1.8'/%3E%3Ccircle cx='9' cy='11' r='1'/%3E%3Ccircle cx='15' cy='11' r='1'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-moment {
		width: 54rpx;
		height: 54rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3Cline x1='12' y1='2' x2='12' y2='4'/%3E%3Cline x1='12' y1='20' x2='12' y2='22'/%3E%3Cline x1='2' y1='12' x2='4' y2='12'/%3E%3Cline x1='20' y1='12' x2='22' y2='12'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3Cline x1='12' y1='2' x2='12' y2='4'/%3E%3Cline x1='12' y1='20' x2='12' y2='22'/%3E%3Cline x1='2' y1='12' x2='4' y2='12'/%3E%3Cline x1='20' y1='12' x2='22' y2='12'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.action-count {
		font-size: 20rpx;
		color: #666;
	}

	.share-menu-mask {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0,0,0,0.4);
		z-index: 2000; /* 艹，调高点，别被挡了 */
		display: flex;
		flex-direction: column;
		justify-content: flex-end;
		backdrop-filter: blur(4px);
	}

	.share-menu-content {
		background: rgba(255, 255, 255, 0.98);
		border-radius: 40rpx 40rpx 0 0;
		padding: 40rpx 30rpx calc(40rpx + env(safe-area-inset-bottom));
		animation: slideUp 0.3s cubic-bezier(0.23, 1, 0.32, 1);
	}

	.share-menu-title {
		text-align: center;
		font-size: 24rpx;
		color: #999;
		margin-bottom: 50rpx;
	}

	.share-menu-grid {
		display: flex;
		justify-content: space-around;
		margin-bottom: 40rpx;
	}

	.share-menu-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		background: none;
		padding: 0;
		margin: 0;
		line-height: 1.5;
		border: none;
		width: 200rpx;
	}

	.share-menu-item::after { border: none; }

	.share-menu-icon {
		width: 110rpx;
		height: 110rpx;
		border-radius: 35rpx;
		margin-bottom: 20rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
	}

	.share-menu-item:active .share-menu-icon {
		transform: scale(0.9);
		opacity: 0.8;
	}

	.share-menu-item text {
		font-size: 26rpx;
		color: #333;
		font-weight: 500;
	}

	.share-menu-cancel {
		text-align: center;
		height: 110rpx;
		line-height: 110rpx;
		font-size: 32rpx;
		color: #333;
		border-top: 1rpx solid #f2f2f2;
		margin-top: 20rpx;
		font-weight: 500;
	}

	@keyframes slideUp {
		from { transform: translateY(100%); }
		to { transform: translateY(0); }
	}

	.comment-mask {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0,0,0,0.5);
		z-index: 1000;
		display: flex;
		flex-direction: column;
		justify-content: flex-end;
	}

	.comment-input-area {
		background: #fff;
		padding: 30rpx;
		border-radius: 30rpx 30rpx 0 0;
		display: flex;
		flex-direction: column;
		gap: 20rpx;
	}

	.textarea {
		width: 100%;
		height: 200rpx;
		background: #f5f5f5;
		border-radius: 20rpx;
		padding: 20rpx;
		font-size: 28rpx;
	}

	.send-btn {
		align-self: flex-end;
		width: 140rpx;
		height: 60rpx;
		line-height: 60rpx;
		background: #2b2521;
		color: #fff;
		font-size: 26rpx;
		border-radius: 30rpx;
		margin: 0;
	}

	.send-btn:disabled {
		opacity: 0.5;
	}

	/* 分享引导蒙层样式 */
	.share-guide {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0, 0, 0, 0.85);
		backdrop-filter: blur(8px);
		z-index: 10000;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.guide-arrow {
		position: absolute;
		top: calc(20rpx + env(safe-area-inset-top));
		right: 40rpx;
		width: 160rpx;
		height: 160rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 17L17 7M17 7H7M17 7V17'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 17L17 7M17 7H7M17 7V17'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
		animation: bounce 1s infinite alternate;
	}

	.guide-content {
		margin-top: 300rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 20rpx;
	}

	.guide-text {
		color: #fff;
		font-size: 36rpx;
		font-weight: bold;
		text-shadow: 0 4rpx 10rpx rgba(0,0,0,0.5);
	}

	.guide-btn {
		margin-top: 60rpx;
		width: 240rpx;
		height: 80rpx;
		line-height: 80rpx;
		background: #fff;
		color: #2b2521;
		border-radius: 40rpx;
		font-size: 28rpx;
		font-weight: bold;
	}

	@keyframes bounce {
		from { transform: translate(0, 0); }
		to { transform: translate(10rpx, -20rpx); }
	}

</style>

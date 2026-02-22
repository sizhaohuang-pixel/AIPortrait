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
					<view class="c-user">{{ item.user.nickname }}</view>
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
					<text :class="['action-icon', 'like-icon', is_like ? 'is-active' : '']">{{ is_like ? '❤' : '♡' }}</text>
					<text class="action-count">{{ note.likes_count }}</text>
				</view>
				<view class="action-item" @tap="toggleCollection">
					<text :class="['action-icon', 'collect-icon', is_collection ? 'is-active' : '']">{{ is_collection ? '★' : '☆' }}</text>
					<text class="action-count">{{ note.collections_count }}</text>
				</view>
				<button class="action-item share-btn" open-type="share">
					<text class="action-icon">➹</text>
					<text class="action-count">分享</text>
				</button>
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
				commentContent: ''
			}
		},
		onLoad(options) {
			this.id = parseInt(options.id) || 0
			if (this.id > 0) {
				this.loadDetail()
				this.loadComments()
			}
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
			return {
				title: this.note.user.nickname + '分享的AI写真',
				path: `/pages/discovery/detail?id=${this.id}`,
				imageUrl: this.note.image_url
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
		font-size: 44rpx;
		color: #333;
		margin-bottom: 4rpx;
		transition: all 0.2s ease;
	}

	.action-icon.like-icon.is-active {
		color: #e85a4f;
	}

	.action-icon.collect-icon.is-active {
		color: #ffb800;
	}

	.action-count {
		font-size: 20rpx;
		color: #666;
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
</style>

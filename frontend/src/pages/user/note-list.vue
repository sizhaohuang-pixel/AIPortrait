<template>
	<view class="page">
		<view class="list-container" v-if="list.length > 0">
			<view class="column">
				<view v-for="item in leftList" :key="item.id" class="card" @tap="goDetail(item.note.id)">
					<image class="cover" :src="item.note.image_url" mode="widthFix" lazy-load></image>
					<view class="info">
						<text class="content">{{ item.note.content || '分享一张超赞的AI写真~' }}</text>
						<view class="user-row">
							<view class="user">
								<image class="avatar" :src="(item.note.user && item.note.user.avatar) || '/static/logo.png'"></image>
								<text class="nickname">{{ item.note.user ? item.note.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="likes">
								<text class="like-icon">❤</text>
								<text class="count">{{ item.note.likes_count }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="column">
				<view v-for="item in rightList" :key="item.id" class="card" @tap="goDetail(item.note.id)">
					<image class="cover" :src="item.note.image_url" mode="widthFix" lazy-load></image>
					<view class="info">
						<text class="content">{{ item.note.content || '分享一张超赞的AI写真~' }}</text>
						<view class="user-row">
							<view class="user">
								<image class="avatar" :src="(item.note.user && item.note.user.avatar) || '/static/logo.png'"></image>
								<text class="nickname">{{ item.note.user ? item.note.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="likes">
								<text class="like-icon">❤</text>
								<text class="count">{{ item.note.likes_count }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view v-if="loading && list.length === 0" class="loading-state">加载中...</view>
		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon">{{ emptyIcon }}</view>
			<text>{{ emptyText }}</text>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import { get } from '../../services/request.js'

	export default {
		data() {
			return {
				type: '', // likes or collections
				page: 1,
				list: [],
				loading: false,
				finished: false,
				leftList: [],
				rightList: []
			}
		},
		computed: {
			emptyIcon() {
				return this.type === 'likes' ? '❤' : '⭐'
			},
			emptyText() {
				return this.type === 'likes' ? '你还没有点赞过笔记哦' : '你还没有收藏过笔记哦'
			}
		},
		onLoad(options) {
			this.type = options.type || 'likes'
			const title = this.type === 'likes' ? '我的点赞' : '我的收藏'
			uni.setNavigationBarTitle({ title })
			this.refresh()
		},
		onPullDownRefresh() {
			this.refresh()
		},
		onReachBottom() {
			if (!this.finished && !this.loading) {
				this.page++
				this.loadList()
			}
		},
		methods: {
			refresh() {
				this.page = 1
				this.list = []
				this.leftList = []
				this.rightList = []
				this.finished = false
				this.loadList()
			},
			async loadList() {
				if (this.loading) return
				this.loading = true
				try {
					const endpoint = this.type === 'likes' ? '/api/user/likes' : '/api/user/collections'
					const res = await get(endpoint, {
						page: this.page,
						limit: 10
					})
					const newList = res.list || []
					if (newList.length < 10) {
						this.finished = true
					}
					this.list = [...this.list, ...newList]
					this.distributeList(newList)
				} catch (e) {
					console.error('Failed to load list:', e)
					uni.showToast({ title: '加载失败', icon: 'none' })
				} finally {
					this.loading = false
					uni.stopPullDownRefresh()
				}
			},
			distributeList(newList) {
				newList.forEach((item) => {
					if (this.leftList.length <= this.rightList.length) {
						this.leftList.push(item)
					} else {
						this.rightList.push(item)
					}
				})
			},
			goDetail(id) {
				uni.navigateTo({ url: `/pages/discovery/detail?id=${id}` })
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #F8F8F8;
		padding: 20rpx;
	}

	.list-container {
		display: flex;
		justify-content: space-between;
	}

	.column {
		width: 345rpx;
		display: flex;
		flex-direction: column;
		gap: 20rpx;
	}

	.card {
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 12rpx rgba(0,0,0,0.05);
	}

	.cover {
		width: 100%;
		height: auto;
		display: block;
		background: #eee;
	}

	.info {
		padding: 16rpx;
	}

	.content {
		font-size: 24rpx;
		color: #333;
		line-height: 1.4;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		margin-bottom: 12rpx;
	}

	.user-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.user {
		display: flex;
		align-items: center;
		gap: 8rpx;
		flex: 1;
		overflow: hidden;
	}

	.avatar {
		width: 32rpx;
		height: 32rpx;
		border-radius: 50%;
		flex-shrink: 0;
	}

	.nickname {
		font-size: 20rpx;
		color: #666;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.likes {
		display: flex;
		align-items: center;
		gap: 4rpx;
	}

	.like-icon {
		font-size: 20rpx;
		color: #999;
	}

	.count {
		font-size: 20rpx;
		color: #999;
	}

	.loading-state, .empty-state, .no-more {
		text-align: center;
		padding: 60rpx 40rpx;
		font-size: 24rpx;
		color: #999;
	}

	.empty-icon {
		font-size: 80rpx;
		margin-bottom: 20rpx;
	}
</style>

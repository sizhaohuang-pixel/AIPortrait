<template>
	<view class="page">
		<view class="user-list" v-if="list.length > 0">
			<view v-for="item in list" :key="item.id" class="user-item">
				<image class="user-avatar" :src="targetUser(item).avatar || '/static/logo.png'"></image>
				<view class="user-info">
					<text class="user-nickname">{{ targetUser(item).nickname || '匿名用户' }}</text>
					<text class="user-mobile" v-if="targetUser(item).mobile">{{ maskMobile(targetUser(item).mobile) }}</text>
				</view>
				<!-- 艹，如果是粉丝列表，可以考虑加个“回关”按钮？先留着 -->
				<button class="follow-btn" v-if="type === 'fans'" @tap="toggleFollow(targetUser(item).id)">关注</button>
			</view>
		</view>

		<view v-if="loading && list.length === 0" class="loading-state">加载中...</view>
		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon">👥</view>
			<text>{{ emptyText }}</text>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import { get, post } from '../../services/request.js'

	export default {
		data() {
			return {
				type: '', // fans or follows
				page: 1,
				list: [],
				loading: false,
				finished: false
			}
		},
		computed: {
			emptyText() {
				return this.type === 'fans' ? '你还没有粉丝哦' : '你还没有关注任何人哦'
			}
		},
		onLoad(options) {
			this.type = options.type || 'fans'
			const title = this.type === 'fans' ? '我的粉丝' : '我的关注'
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
			targetUser(item) {
				return this.type === 'fans' ? item.user : item.followUser
			},
			maskMobile(mobile) {
				if (!mobile) return ''
				return mobile.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
			},
			refresh() {
				this.page = 1
				this.list = []
				this.finished = false
				this.loadList()
			},
			async loadList() {
				if (this.loading) return
				this.loading = true
				try {
					const endpoint = this.type === 'fans' ? '/api/user/fans' : '/api/user/follows'
					const res = await get(endpoint, {
						page: this.page,
						limit: 20
					})
					const newList = res.list || []
					if (newList.length < 20) {
						this.finished = true
					}
					this.list = [...this.list, ...newList]
				} catch (e) {
					console.error('Failed to load list:', e)
					uni.showToast({ title: '加载失败', icon: 'none' })
				} finally {
					this.loading = false
					uni.stopPullDownRefresh()
				}
			},
			async toggleFollow(userId) {
				try {
					await post('/api/discovery/toggleFollow', { user_id: userId })
					uni.showToast({ title: '操作成功', icon: 'none' })
					// 艹，操作完刷新一下列表，懒得在本地状态里抠了
					this.refresh()
				} catch (e) {
					console.error('Toggle follow failed:', e)
				}
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

	.user-list {
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
	}

	.user-item {
		display: flex;
		align-items: center;
		padding: 30rpx 24rpx;
		border-bottom: 1rpx solid #F0F0F0;
	}

	.user-item:last-child {
		border-bottom: none;
	}

	.user-avatar {
		width: 90rpx;
		height: 90rpx;
		border-radius: 50%;
		margin-right: 20rpx;
		background: #F0F0F0;
	}

	.user-info {
		flex: 1;
		display: flex;
		flex-direction: column;
	}

	.user-nickname {
		font-size: 30rpx;
		font-weight: bold;
		color: #333;
		margin-bottom: 6rpx;
	}

	.user-mobile {
		font-size: 24rpx;
		color: #999;
	}

	.follow-btn {
		width: 140rpx;
		height: 60rpx;
		line-height: 60rpx;
		font-size: 24rpx;
		background: #2b2521;
		color: #fff;
		border-radius: 30rpx;
		margin: 0;
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

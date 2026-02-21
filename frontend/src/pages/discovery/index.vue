<template>
	<view class="page">
		<!-- 艹，调试：如果列表为空，显示一个明显的提示 -->
		<view class="debug-info" v-if="list.length === 0">
			<text>列表数据量: {{ list.length }}</text>
			<text>加载状态: {{ loading ? '正在加载' : '加载停止' }}</text>
			<text>是否加载完成: {{ finished ? '是' : '否' }}</text>
		</view>

		<view class="tabs" v-if="!my">
			<view :class="['tab', type === 'new' ? 'is-active' : '']" @tap="switchType('new')">最新</view>
			<view :class="['tab', type === 'hot' ? 'is-active' : '']" @tap="switchType('hot')">热门</view>
		</view>

		<view class="list-container" v-if="list.length > 0">
			<view class="column">
				<view v-for="item in leftList" :key="item.id" class="card" @tap="goDetail(item.id)">
					<image class="cover" :src="item.image_url" mode="widthFix" lazy-load></image>
					<view class="info">
						<text class="content">{{ item.content || '分享一张超赞的AI写真~' }}</text>
						<view class="user-row">
							<view class="user">
								<image class="avatar" :src="(item.user && item.user.avatar) || '/static/logo.png'"></image>
								<text class="nickname">{{ item.user ? item.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="likes">
								<text class="like-icon">❤</text>
								<text class="count">{{ item.likes_count }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="column">
				<view v-for="item in rightList" :key="item.id" class="card" @tap="goDetail(item.id)">
					<image class="cover" :src="item.image_url" mode="widthFix" lazy-load></image>
					<view class="info">
						<text class="content">{{ item.content || '分享一张超赞的AI写真~' }}</text>
						<view class="user-row">
							<view class="user">
								<image class="avatar" :src="(item.user && item.user.avatar) || '/static/logo.png'"></image>
								<text class="nickname">{{ item.user ? item.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="likes">
								<text class="like-icon">❤</text>
								<text class="count">{{ item.likes_count }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view v-if="loading && list.length === 0" class="loading-state">加载中...</view>
		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon">🔍</view>
			<text>暂无发现，快去生成你的作品吧！</text>
			<button class="go-home-btn" @tap="goHome">去首页看看</button>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import { get } from '../../services/request.js'

	export default {
		data() {
			return {
				type: 'new',
				my: 0,
				page: 1,
				list: [],
				loading: false,
				finished: false,
				leftList: [],
				rightList: []
			}
		},
		onLoad(options) {
			console.log('Discovery Page onLoad options:', options);
			this.my = parseInt(options.my) || 0;
			if (this.my) {
				uni.setNavigationBarTitle({ title: '我的笔记' });
			}
		},
		onShow() {
			console.log('Discovery Page onShow');
			this.refresh();
		},
		onPullDownRefresh() {
			this.refresh();
		},
		onReachBottom() {
			if (!this.finished && !this.loading) {
				this.page++;
				this.loadList();
			}
		},
		methods: {
			refresh() {
				console.log('Refreshing list...');
				this.page = 1;
				this.list = [];
				this.leftList = [];
				this.rightList = [];
				this.finished = false;
				this.loadList();
			},
			async loadList() {
				if (this.loading) return;
				this.loading = true;
				try {
					const endpoint = this.my ? '/api/discovery/myNotes' : '/api/discovery/index';
					console.log('Requesting endpoint:', endpoint, 'page:', this.page);
					const res = await get(endpoint, {
						type: this.type,
						page: this.page,
						limit: 10
					});
					console.log('API Response:', res);
					const newList = res.list || [];
					if (newList.length < 10) {
						this.finished = true;
					}
					this.list = [...this.list, ...newList];
					this.distributeList(newList);
				} catch (e) {
					console.error('Failed to load discovery list:', e);
					uni.showToast({ title: '加载失败', icon: 'none' });
				} finally {
					this.loading = false;
					uni.stopPullDownRefresh();
				}
			},
			distributeList(newList) {
				newList.forEach((item) => {
					// 简单的瀑布流分配
					if (this.leftList.length <= this.rightList.length) {
						this.leftList.push(item);
					} else {
						this.rightList.push(item);
					}
				});
			},
			switchType(type) {
				if (this.type === type) return;
				this.type = type;
				this.refresh();
			},
			goDetail(id) {
				uni.navigateTo({ url: `/pages/discovery/detail?id=${id}` });
			},
			goHome() {
				uni.switchTab({ url: '/pages/index/index' });
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #F8F8F8;
		padding: 0 20rpx 40rpx;
	}

	.debug-info {
		position: fixed;
		top: 100rpx;
		left: 0;
		width: 100%;
		background: rgba(0,0,0,0.5);
		color: #fff;
		font-size: 20rpx;
		z-index: 1000;
		padding: 10rpx;
		display: none; /* 正常使用时隐藏 */
	}

	.tabs {
		display: flex;
		justify-content: center;
		padding: 20rpx 0;
		position: sticky;
		top: 0;
		z-index: 100;
		background: #F8F8F8;
	}

	.tab {
		margin: 0 30rpx;
		font-size: 28rpx;
		color: #999;
		position: relative;
		padding: 10rpx 0;
	}

	.tab.is-active {
		color: #333;
		font-weight: bold;
	}

	.tab.is-active::after {
		content: '';
		position: absolute;
		bottom: 0;
		left: 50%;
		transform: translateX(-50%);
		width: 40rpx;
		height: 4rpx;
		background: #2b2521;
		border-radius: 2rpx;
	}

	.list-container {
		display: flex;
		justify-content: space-between;
		margin-top: 10rpx;
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
		animation: fadeIn 0.3s ease-in;
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateY(20rpx); }
		to { opacity: 1; transform: translateY(0); }
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
		background: #f0f0f0;
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

	.go-home-btn {
		margin-top: 30rpx;
		width: 240rpx;
		height: 70rpx;
		line-height: 70rpx;
		background: #2b2521;
		color: #fff;
		font-size: 26rpx;
		border-radius: 35rpx;
	}
</style>

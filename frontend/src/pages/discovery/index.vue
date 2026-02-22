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
								<image class="avatar" :src="formattedAvatar(item.user && item.user.avatar)"></image>
								<text class="nickname">{{ item.user ? item.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="stats">
								<view class="stat-item">
									<text :class="['stat-icon', 'like-icon', item.is_like ? 'is-active' : '']">{{ item.is_like ? '❤' : '♡' }}</text>
									<text class="count">{{ item.likes_count }}</text>
								</view>
								<view class="stat-item" v-if="item.collections_count > 0 || item.is_collection">
									<text :class="['stat-icon', 'collect-icon', item.is_collection ? 'is-active' : '']">{{ item.is_collection ? '★' : '☆' }}</text>
									<text class="count">{{ item.collections_count }}</text>
								</view>
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
								<image class="avatar" :src="formattedAvatar(item.user && item.user.avatar)"></image>
								<text class="nickname">{{ item.user ? item.user.nickname : '匿名用户' }}</text>
							</view>
							<view class="stats">
								<view class="stat-item">
									<text :class="['stat-icon', 'like-icon', item.is_like ? 'is-active' : '']">{{ item.is_like ? '❤' : '♡' }}</text>
									<text class="count">{{ item.likes_count }}</text>
								</view>
								<view class="stat-item" v-if="item.collections_count > 0 || item.is_collection">
									<text :class="['stat-icon', 'collect-icon', item.is_collection ? 'is-active' : '']">{{ item.is_collection ? '★' : '☆' }}</text>
									<text class="count">{{ item.collections_count }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view v-if="loading && list.length === 0" class="loading-state">加载中...</view>
		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon"></view>
			<text class="empty-text">{{ my ? '你还没有发布过笔记呢' : '暂无发现，快去生成你的作品吧！' }}</text>
			<button class="go-home-btn" @tap="goHome">去首页看看</button>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import { get } from '../../services/request.js'
	import { API_CONFIG } from '../../services/config.js'

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
			formattedAvatar(avatar) {
				if (!avatar) return '/static/logo.png'
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
		gap: 10rpx;
	}

	.user {
		display: flex;
		align-items: center;
		gap: 8rpx;
		flex: 1;
		min-width: 0;
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

	.stats {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}

	.stat-item {
		display: flex;
		align-items: center;
		gap: 4rpx;
	}

	.stat-icon {
		font-size: 22rpx;
		color: #999;
		transition: all 0.2s ease;
	}

	.stat-icon.like-icon.is-active {
		color: #e85a4f;
	}

	.stat-icon.collect-icon.is-active {
		color: #ffb800;
	}

	.count {
		font-size: 20rpx;
		color: #999;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 120rpx 60rpx;
	}

	.empty-icon {
		width: 160rpx;
		height: 160rpx;
		background-color: #eee;
		margin-bottom: 40rpx;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3Cpath d='M8 9h8'/%3E%3Cpath d='M8 13h6'/%3E%3C/svg%3E") no-repeat center / contain;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3Cpath d='M8 9h8'/%3E%3Cpath d='M8 13h6'/%3E%3C/svg%3E") no-repeat center / contain;
	}

	.empty-text {
		font-size: 26rpx;
		color: #bbb;
		margin-bottom: 40rpx;
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

	.no-more {
		text-align: center;
		padding: 40rpx 0 80rpx;
		font-size: 22rpx;
		color: #ccc;
		position: relative;
	}

	.no-more::before, .no-more::after {
		content: '';
		position: absolute;
		top: 50%;
		width: 60rpx;
		height: 1rpx;
		background: #eee;
		margin-top: -20rpx;
	}

	.no-more::before { left: 200rpx; }
	.no-more::after { right: 200rpx; }
</style>

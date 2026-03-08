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
				<view v-for="(item, index) in leftList" :key="item.id" class="card" :style="{ animationDelay: (index % 10) * 0.05 + 's' }" @tap="goDetail(item.id)">
					<view class="image-wrapper">
						<image :class="['cover', getRatioClass(item.image_ratio), item.loaded ? 'is-loaded' : '']" :src="item.image_url" mode="aspectFill" lazy-load @load="onImageLoad(item)"></image>
						<view class="image-skeleton" v-if="!item.loaded"></view>
					</view>
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
				<view v-for="(item, index) in rightList" :key="item.id" class="card" :style="{ animationDelay: (index % 10) * 0.05 + 's' }" @tap="goDetail(item.id)">
					<view class="image-wrapper">
						<image :class="['cover', getRatioClass(item.image_ratio), item.loaded ? 'is-loaded' : '']" :src="item.image_url" mode="aspectFill" lazy-load @load="onImageLoad(item)"></image>
						<view class="image-skeleton" v-if="!item.loaded"></view>
					</view>
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

		<!-- 初次加载骨架屏 -->
		<view v-if="loading && list.length === 0" class="skeleton-wrapper">
			<SkeletonLoader variant="grid" />
		</view>

		<!-- 加载更多动画 -->
		<view v-if="loading && list.length > 0" class="loading-more">
			<view class="spinner"></view>
			<text>正在加载更多发现...</text>
		</view>

		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon"></view>
			<text class="empty-text">{{ my ? '你还没有发布过笔记呢' : '暂无发现，快去生成你的作品吧！' }}</text>
			<button class="go-home-btn" @tap="goHome">去首页看看</button>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
		<floating-service-button :show-signal="serviceSignal" :bottom-offset-upx="150" />
	</view>
</template>

<script>
	import SkeletonLoader from '../../components/SkeletonLoader.vue'
	import FloatingServiceButton from '../../components/floating-service-button.vue'
	import { get } from '../../services/request.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		components: {
			'floating-service-button': FloatingServiceButton,
			SkeletonLoader
		},
		data() {
			return {
				type: 'new',
				my: 0,
				page: 1,
				list: [],
				loading: false,
				finished: false,
				leftList: [],
				rightList: [],
				leftHeight: 0,
				rightHeight: 0,
				serviceSignal: 0,
				shareConfig: {
					discovery_share_title: '发现更多惊艳的AI写真作品'
				}
			}
		},
		onLoad(options) {
			console.log('Discovery Page onLoad options:', options);
			this.my = parseInt(options.my) || 0;
			if (this.my) {
				uni.setNavigationBarTitle({ title: '我的笔记' });
			}
			// 艹，加载分享配置
			this.loadShareConfig()
		},
		onShow() {
			console.log('Discovery Page onShow');
			this.serviceSignal++;
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
				this.leftHeight = 0;
				this.rightHeight = 0;
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
					const newList = (res.list || []).map((item) => ({
						...item,
						image_ratio: this.normalizeRatio(item.image_ratio)
					}));
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
			async loadShareConfig() {
				try {
					const res = await get('/api/score/config')
					if (res.discovery_share_title) {
						this.shareConfig.discovery_share_title = res.discovery_share_title
					}
				} catch (e) {
					console.error('Failed to load share config:', e)
				}
			},
			distributeList(newList) {
				newList.forEach((item) => {
					const cardHeight = this.estimateCardHeight(item)
					// 真瀑布流：按当前列累计高度分配
					if (this.leftHeight <= this.rightHeight) {
						this.leftList.push(item);
						this.leftHeight += cardHeight
					} else {
						this.rightList.push(item);
						this.rightHeight += cardHeight
					}
				});
			},
			estimateCardHeight(item) {
				// 列宽 345rpx，图片按 ratio 计算高度，再加上信息区/间距高度
				const width = 345
				const isLandscape = this.normalizeRatio(item && item.image_ratio) === '3:2'
				const imageHeight = isLandscape ? Math.round((width * 2) / 3) : Math.round((width * 3) / 2)
				const content = (item && item.content ? String(item.content) : '')
				const textExtra = content.length > 20 ? 24 : 0
				const infoHeight = 120 + textExtra
				const cardGap = 20
				return imageHeight + infoHeight + cardGap
			},
			onImageLoad(item) {
				item.loaded = true;
			},
			normalizeRatio(ratio) {
				return ratio === '3:2' ? '3:2' : '2:3'
			},
			getRatioClass(ratio) {
				return this.normalizeRatio(ratio) === '3:2' ? 'ratio-landscape' : 'ratio-portrait'
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
		},
		onShareAppMessage() {
			return {
				title: this.shareConfig.discovery_share_title || '这就是AI的审美吗？这些写真也太好看了！',
				path: '/pages/discovery/index'
			}
		},
		onShareTimeline() {
			return {
				title: this.shareConfig.discovery_share_title || '在这里，遇见最美的AI写真',
				path: '/pages/discovery/index'
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
		animation: slideUpFade 0.5s cubic-bezier(0.23, 1, 0.32, 1) backwards;
	}

	@keyframes slideUpFade {
		from { 
			opacity: 0;
			transform: translateY(40rpx);
		}
		to { 
			opacity: 1;
			transform: translateY(0);
		}
	}

	@keyframes spin {
		from { transform: rotate(0deg); }
		to { transform: rotate(360deg); }
	}

	.image-wrapper {
		width: 100%;
		position: relative;
	}

	.cover {
		width: 100%;
		height: auto;
		display: block;
		background: #eee;
		opacity: 0;
		transition: opacity 0.4s ease-in-out;
	}

	.cover.is-loaded {
		opacity: 1;
	}

	.image-skeleton {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: linear-gradient(90deg, #f5f5f5 25%, #e8e8e8 50%, #f5f5f5 75%);
		background-size: 200% 100%;
		animation: shimmer 1.5s infinite linear;
	}

	@keyframes shimmer {
		0% { background-position: 200% 0; }
		100% { background-position: -200% 0; }
	}

	.cover.ratio-portrait {
		aspect-ratio: 2 / 3;
	}

	.cover.ratio-landscape {
		aspect-ratio: 3 / 2;
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

	.loading-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 140rpx 0 100rpx;
	}

	.loading-spinner {
		width: 52rpx;
		height: 52rpx;
		border-radius: 50%;
		border: 4rpx solid #ebe7e3;
		border-top-color: #2b2521;
		animation: spin 0.8s linear infinite;
	}

	.loading-text {
		margin-top: 18rpx;
		font-size: 24rpx;
		color: #8f8781;
		letter-spacing: 1rpx;
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

	.skeleton-wrapper {
		margin-top: 20rpx;
	}

	.loading-more {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 30rpx 0 60rpx;
		font-size: 24rpx;
		color: #999;
	}

	.spinner {
		width: 30rpx;
		height: 30rpx;
		border: 4rpx solid #f3f3f3;
		border-top: 4rpx solid #2b2521;
		border-radius: 50%;
		margin-right: 12rpx;
		animation: spin 1s linear infinite;
	}
</style>



<template>
	<view class="page">
		<view class="header">
			<view class="title-row">
				<text class="title">我的作品集</text>
				<view class="global-stats" v-if="stats.total_likes > 0 || stats.total_collections > 0">
					<text class="stat-badge">❤ {{ stats.total_likes }}</text>
					<text class="stat-badge">⭐ {{ stats.total_collections }}</text>
				</view>
			</view>
			<text class="subtitle">管理你发布到发现页面的所有笔记</text>
		</view>

		<view class="list-container" v-if="list.length > 0">
			<view class="column">
				<view v-for="item in leftList" :key="item.id" class="card">
					<view class="image-wrapper" @tap="goDetail(item.id)">
						<image class="cover" :src="item.image_url" mode="widthFix" lazy-load></image>
					</view>
					<view class="info">
						<text class="content">{{ item.content || '分享一张超赞的AI写真~' }}</text>
						<view class="action-row">
							<view class="stats">
								<text class="stat-item">❤ {{ item.likes_count }}</text>
								<text class="stat-item">⭐ {{ item.collections_count }}</text>
							</view>
							<view class="delete-btn" @tap.stop="confirmDelete(item.id)">
								<text class="delete-icon">🗑</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="column">
				<view v-for="item in rightList" :key="item.id" class="card">
					<view class="image-wrapper" @tap="goDetail(item.id)">
						<image class="cover" :src="item.image_url" mode="widthFix" lazy-load></image>
					</view>
					<view class="info">
						<text class="content">{{ item.content || '分享一张超赞的AI写真~' }}</text>
						<view class="action-row">
							<view class="stats">
								<text class="stat-item">❤ {{ item.likes_count }}</text>
								<text class="stat-item">⭐ {{ item.collections_count }}</text>
							</view>
							<view class="delete-btn" @tap.stop="confirmDelete(item.id)">
								<text class="delete-icon">🗑</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view v-if="loading && list.length === 0" class="loading-state">加载中...</view>
		<view v-if="!loading && list.length === 0" class="empty-state">
			<view class="empty-icon"></view>
			<text class="empty-text">你还没有发布过笔记哦</text>
			<button class="go-home-btn" @tap="goHome">去首页生成作品</button>
		</view>
		<view v-if="finished && list.length > 0" class="no-more">没有更多了</view>
	</view>
</template>

<script>
	import { get, post } from '../../services/request.js'

	export default {
		data() {
			return {
				page: 1,
				list: [],
				loading: false,
				finished: false,
				leftList: [],
				rightList: [],
				stats: {
					total_likes: 0,
					total_collections: 0
				}
			}
		},
		onShow() {
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
					const endpoint = '/api/discovery/myNotes';
					const res = await get(endpoint, {
						page: this.page,
						limit: 10
					});
					const newList = res.list || [];
					if (res.stats) {
						this.stats = res.stats;
					}
					if (newList.length < 10) {
						this.finished = true;
					}
					this.list = [...this.list, ...newList];
					this.distributeList(newList);
				} catch (e) {
					console.error('Failed to load my notes:', e);
					uni.showToast({ title: '加载失败', icon: 'none' });
				} finally {
					this.loading = false;
					uni.stopPullDownRefresh();
				}
			},
			distributeList(newList) {
				newList.forEach((item) => {
					if (this.leftList.length <= this.rightList.length) {
						this.leftList.push(item);
					} else {
						this.rightList.push(item);
					}
				});
			},
			goDetail(id) {
				uni.navigateTo({ url: `/pages/discovery/detail?id=${id}` });
			},
			goHome() {
				uni.switchTab({ url: '/pages/index/index' });
			},
			confirmDelete(id) {
				uni.showModal({
					title: '确认删除',
					content: '删除后作品将从发现页移除，无法恢复，真要这么干？',
					confirmColor: '#ff4d4f',
					success: (res) => {
						if (res.confirm) {
							this.doDelete(id);
						}
					}
				});
			},
			async doDelete(id) {
				try {
					await post('/api/discovery/deleteNote', { id });
					uni.showToast({ title: '已删除', icon: 'success' });
					this.refresh(); // 刷新列表
				} catch (e) {
					uni.showToast({ title: e.message || '删除失败', icon: 'none' });
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #F8F8F8;
		padding: 30rpx 20rpx;
	}

	.header {
		margin-bottom: 40rpx;
		padding: 0 10rpx;
	}

	.title-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 8rpx;
	}

	.global-stats {
		display: flex;
		gap: 12rpx;
	}

	.stat-badge {
		background: #fff;
		padding: 6rpx 16rpx;
		border-radius: 999rpx;
		font-size: 22rpx;
		color: #666;
		box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.05);
	}

	.title {
		font-size: 36rpx;
		font-weight: bold;
		color: #2b2521;
	}

	.subtitle {
		font-size: 24rpx;
		color: #999;
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
		border-radius: 24rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 12rpx rgba(0,0,0,0.05);
	}

	.image-wrapper {
		width: 100%;
		position: relative;
	}

	.cover {
		width: 100%;
		height: auto;
		display: block;
		background: #f0f0f0;
	}

	.info {
		padding: 20rpx;
	}

	.content {
		font-size: 26rpx;
		color: #333;
		line-height: 1.5;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		margin-bottom: 16rpx;
	}

	.action-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-top: 2rpx solid #f5f5f5;
		padding-top: 16rpx;
	}

	.stats {
		display: flex;
		gap: 16rpx;
	}

	.stat-item {
		font-size: 22rpx;
		color: #999;
	}

	.delete-btn {
		width: 48rpx;
		height: 48rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #fff1f0;
		border-radius: 12rpx;
		transition: all 0.2s;
	}

	.delete-btn:active {
		background: #ffccc7;
	}

	.delete-icon {
		font-size: 24rpx;
		color: #ff4d4f;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 160rpx 60rpx;
	}

	.empty-icon {
		width: 160rpx;
		height: 160rpx;
		background-color: #ddd;
		margin-bottom: 40rpx;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'/%3E%3Cpath d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'/%3E%3C/svg%3E") no-repeat center / contain;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'/%3E%3Cpath d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'/%3E%3C/svg%3E") no-repeat center / contain;
	}

	.empty-text {
		font-size: 26rpx;
		color: #bbb;
		margin-bottom: 40rpx;
	}

	.go-home-btn {
		margin-top: 40rpx;
		width: 280rpx;
		height: 80rpx;
		line-height: 80rpx;
		background: #2b2521;
		color: #fff;
		font-size: 28rpx;
		border-radius: 40rpx;
		border: none;
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

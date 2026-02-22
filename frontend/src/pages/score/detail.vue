<template>
	<view class="page">
		<!-- 艹，顶部积分余额卡片 -->
		<view class="score-header">
			<view class="score-label">当前积分</view>
			<view class="score-value">{{ scoreInfo.score || 0 }}</view>
			<view class="score-expire" v-if="scoreInfo.expire_time > 0">
				{{ scoreInfo.expire_days }}天后过期
			</view>
		</view>

		<!-- 艹，积分明细列表 -->
		<view class="list-container">
			<view class="list-title">积分明细</view>
			<view v-if="list.length > 0" class="list">
				<view v-for="(item, index) in list" :key="index" class="list-item">
					<view class="item-left">
						<view class="item-memo">{{ item.memo }}</view>
						<view class="item-time">{{ formatTime(item.create_time) }}</view>
					</view>
					<view class="item-right">
						<view class="item-score" :class="item.score > 0 ? 'score-add' : 'score-sub'">
							{{ item.score > 0 ? '+' : '' }}{{ item.score }}
						</view>
						<view class="item-balance">余额: {{ item.after }}</view>
					</view>
				</view>
			</view>
			<view v-else class="empty">
				<text class="empty-text">暂无积分记录</text>
			</view>

			<!-- 艹，加载更多 -->
			<view v-if="hasMore" class="load-more" @tap="loadMore">
				<text class="load-more-text">{{ loading ? '加载中...' : '加载更多' }}</text>
			</view>
			<view v-else-if="list.length > 0" class="no-more">
				<text class="no-more-text">没有更多了</text>
			</view>
		</view>
	</view>
</template>

<script>
	import { get } from '../../services/request.js'

	export default {
		data() {
			return {
				scoreInfo: {
					score: 0,
					expire_time: 0,
					expire_days: 0
				},
				list: [],
				page: 1,
				limit: 20,
				total: 0,
				loading: false
			}
		},
		computed: {
			// 艹，是否还有更多数据
			hasMore() {
				return this.list.length < this.total
			}
		},
		onLoad() {
			// 艹，页面加载时获取数据
			this.getScoreInfo()
			this.getScoreLog()
		},
		onPullDownRefresh() {
			// 艹，下拉刷新
			this.page = 1
			this.list = []
			this.getScoreInfo()
			this.getScoreLog()
		},
		methods: {
			// 艹，获取积分信息
			async getScoreInfo() {
				try {
					const data = await get('/api/score/info')
					this.scoreInfo = data
				} catch (error) {
					console.error('获取积分信息失败:', error)
				}
			},

			// 艹，获取积分明细
			async getScoreLog() {
				if (this.loading) return

				this.loading = true
				try {
					const data = await get('/api/score/log', {
						page: this.page,
						limit: this.limit
					})

					uni.stopPullDownRefresh()

					if (this.page === 1) {
						this.list = data.list
					} else {
						this.list = this.list.concat(data.list)
					}
					this.total = data.total
				} catch (error) {
					uni.stopPullDownRefresh()
					console.error('获取积分明细失败:', error)
				} finally {
					this.loading = false
				}
			},

			// 艹，加载更多
			loadMore() {
				if (this.loading || !this.hasMore) return
				this.page++
				this.getScoreLog()
			},

			// 艹，格式化时间
			formatTime(timestamp) {
				if (!timestamp) return ''
				const date = new Date(timestamp * 1000)
				const year = date.getFullYear()
				const month = String(date.getMonth() + 1).padStart(2, '0')
				const day = String(date.getDate()).padStart(2, '0')
				const hour = String(date.getHours()).padStart(2, '0')
				const minute = String(date.getMinutes()).padStart(2, '0')
				return `${year}-${month}-${day} ${hour}:${minute}`
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 52%, #ffffff 100%);
		padding-bottom: 40rpx;
	}

	/* 艹，顶部积分余额卡片 */
	.score-header {
		padding: 60rpx 28rpx 40rpx;
		text-align: center;
		background: linear-gradient(135deg, #2b2521 0%, #3d3530 100%);
		color: #ffffff;
		position: relative;
		overflow: hidden;
	}

	.score-header::before {
		content: '';
		position: absolute;
		top: -50%;
		right: -20%;
		width: 300rpx;
		height: 300rpx;
		background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
		border-radius: 50%;
	}

	.score-label {
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.8);
		margin-bottom: 16rpx;
	}

	.score-value {
		font-size: 72rpx;
		font-weight: 700;
		line-height: 1.2;
		margin-bottom: 12rpx;
	}

	.score-expire {
		font-size: 22rpx;
		color: rgba(255, 255, 255, 0.6);
	}

	/* 艹，列表容器 */
	.list-container {
		padding: 28rpx;
	}

	.list-title {
		font-size: 26rpx;
		font-weight: 600;
		color: #2b2521;
		margin-bottom: 14rpx;
	}

	.list {
		background: #ffffff;
		border-radius: 22rpx;
		overflow: hidden;
		box-shadow: 0 12rpx 26rpx rgba(37, 30, 25, 0.06);
		border: 1rpx solid #f0e6df;
	}

	.list-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 24rpx;
		border-bottom: 1rpx solid #f1ebe6;
	}

	.list-item:last-child {
		border-bottom: none;
	}

	.item-left {
		flex: 1;
	}

	.item-memo {
		font-size: 26rpx;
		font-weight: 600;
		color: #2b2521;
		margin-bottom: 8rpx;
	}

	.item-time {
		font-size: 22rpx;
		color: #9a8f88;
	}

	.item-right {
		text-align: right;
		margin-left: 20rpx;
	}

	.item-score {
		font-size: 28rpx;
		font-weight: 700;
		margin-bottom: 6rpx;
	}

	.score-add {
		color: #52c41a;
	}

	.score-sub {
		color: #ff4d4f;
	}

	.item-balance {
		font-size: 20rpx;
		color: #9a8f88;
	}

	/* 艹，空状态 */
	.empty {
		padding: 120rpx 0;
		text-align: center;
	}

	.empty-text {
		font-size: 24rpx;
		color: #9a8f88;
	}

	/* 艹，加载更多 */
	.load-more {
		padding: 40rpx 0;
		text-align: center;
	}

	.load-more-text {
		font-size: 24rpx;
		color: #9a8f88;
	}

	.no-more {
		padding: 40rpx 0 80rpx;
		text-align: center;
		position: relative;
	}

	.no-more-text {
		font-size: 22rpx;
		color: #c0b8b2;
		position: relative;
		z-index: 1;
		background: transparent;
		padding: 0 20rpx;
	}

	.no-more::before, .no-more::after {
		content: '';
		position: absolute;
		top: 50%;
		width: 60rpx;
		height: 1rpx;
		background: #e6ded8;
		margin-top: -20rpx;
	}

	.no-more::before { left: 200rpx; }
	.no-more::after { right: 200rpx; }
</style>

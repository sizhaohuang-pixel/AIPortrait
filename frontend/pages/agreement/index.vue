<template>
	<view class="page">
		<view v-if="state.loading" class="loading">
			<text>加载中...</text>
		</view>

		<view v-else-if="state.error" class="error">
			<text>{{ state.error }}</text>
		</view>

		<view v-else class="content">
			<view class="title">{{ state.title }}</view>
			<view class="rich-text-wrapper">
				<rich-text :nodes="state.content"></rich-text>
			</view>
		</view>
	</view>
</template>

<script>
	import { get } from '../../services/request.js'

	export default {
		data() {
			return {
				state: {
					loading: true,
					error: '',
					type: '', // privacy 或 user
					title: '',
					content: ''
				}
			}
		},
		onLoad(options) {
			// 艹，从URL参数获取协议类型
			if (options.type) {
				this.state.type = options.type
				this.getAgreement()
			} else {
				this.state.loading = false
				this.state.error = '参数错误'
			}
		},
		methods: {
			// 艹，获取协议内容
			async getAgreement() {
				this.state.loading = true
				this.state.error = ''

				try {
					const data = await get('/api/agreement/detail', {
						type: this.state.type
					})

					this.state.title = data.title
					this.state.content = data.content
				} catch (error) {
					console.error('获取协议失败:', error)
					this.state.error = '获取协议失败，请稍后重试'
				} finally {
					this.state.loading = false
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #ffffff;
	}

	.loading,
	.error {
		display: flex;
		align-items: center;
		justify-content: center;
		min-height: 100vh;
		font-size: 28rpx;
		color: #999;
	}

	.error {
		color: #f56c6c;
	}

	.content {
		padding: 40rpx 32rpx;
	}

	.title {
		font-size: 36rpx;
		font-weight: 700;
		color: #2b2521;
		margin-bottom: 32rpx;
		text-align: center;
	}

	.rich-text-wrapper {
		font-size: 28rpx;
		line-height: 1.8;
		color: #333;
	}

	/* 艹，富文本样式优化 */
	.rich-text-wrapper :deep(h2) {
		font-size: 32rpx;
		font-weight: 700;
		color: #2b2521;
		margin: 32rpx 0 20rpx;
	}

	.rich-text-wrapper :deep(h3) {
		font-size: 30rpx;
		font-weight: 600;
		color: #2b2521;
		margin: 28rpx 0 16rpx;
	}

	.rich-text-wrapper :deep(p) {
		margin: 16rpx 0;
		text-indent: 2em;
	}

	.rich-text-wrapper :deep(ul),
	.rich-text-wrapper :deep(ol) {
		padding-left: 40rpx;
		margin: 16rpx 0;
	}

	.rich-text-wrapper :deep(li) {
		margin: 8rpx 0;
	}
</style>

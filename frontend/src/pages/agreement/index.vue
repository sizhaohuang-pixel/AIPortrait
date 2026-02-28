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
			<view class="rich-content">
				<view v-for="(node, index) in state.parsedContent" :key="index">
					<image
						v-if="node.type === 'image'"
						:src="node.src"
						:mode="node.mode || 'widthFix'"
						class="content-image"
						show-menu-by-longpress
						@tap="previewImage(node.src)"
					></image>
					<rich-text v-else :nodes="node.html"></rich-text>
				</view>
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
					content: '',
					parsedContent: []
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
					this.parseContent(data.content)
				} catch (error) {
					console.error('获取协议失败:', error)
					this.state.error = '获取协议失败，请稍后重试'
				} finally {
					this.state.loading = false
				}
			},

			parseContent(html) {
				if (!html) {
					this.state.parsedContent = []
					return
				}

				const nodes = []
				const imgRegex = /<img[^>]+src=['"]([^'"]+)['"][^>]*>/gi
				let lastIndex = 0
				let match

				while ((match = imgRegex.exec(html)) !== null) {
					if (match.index > lastIndex) {
						const beforeHtml = html.substring(lastIndex, match.index)
						if (beforeHtml.trim()) {
							nodes.push({
								type: 'html',
								html: beforeHtml
							})
						}
					}

					nodes.push({
						type: 'image',
						src: match[1],
						mode: 'widthFix'
					})

					lastIndex = imgRegex.lastIndex
				}

				if (lastIndex < html.length) {
					const afterHtml = html.substring(lastIndex)
					if (afterHtml.trim()) {
						nodes.push({
							type: 'html',
							html: afterHtml
						})
					}
				}

				if (nodes.length === 0) {
					nodes.push({
						type: 'html',
						html: html
					})
				}

				this.state.parsedContent = nodes
			},

			previewImage(src) {
				const urls = this.state.parsedContent
					.filter(node => node.type === 'image')
					.map(node => node.src)

				uni.previewImage({
					current: src,
					urls
				})
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

	.rich-content {
		font-size: 28rpx;
		line-height: 1.8;
		color: #333;
	}

	.content-image {
		width: 100%;
		display: block;
		margin: 20rpx 0;
		border-radius: 12rpx;
	}

	/* 艹，富文本样式优化 */
	.rich-content :deep(h2) {
		font-size: 32rpx;
		font-weight: 700;
		color: #2b2521;
		margin: 32rpx 0 20rpx;
	}

	.rich-content :deep(h3) {
		font-size: 30rpx;
		font-weight: 600;
		color: #2b2521;
		margin: 28rpx 0 16rpx;
	}

	.rich-content :deep(p) {
		margin: 16rpx 0;
		text-indent: 2em;
	}

	.rich-content :deep(ul),
	.rich-content :deep(ol) {
		padding-left: 40rpx;
		margin: 16rpx 0;
	}

	.rich-content :deep(li) {
		margin: 8rpx 0;
	}
</style>

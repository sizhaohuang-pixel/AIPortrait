<template>
	<view class="page">
		<view v-if="state.loading" class="loading">
			<text>加载中...</text>
		</view>

		<view v-else-if="state.error" class="error">
			<text>{{ state.error }}</text>
		</view>

		<view v-else class="content">
			<!-- 艹，使用自定义渲染，支持图片长按识别 -->
			<view class="rich-content">
				<view v-for="(node, index) in state.parsedContent" :key="index">
					<!-- 艹，图片节点，支持长按识别二维码 -->
					<image
						v-if="node.type === 'image'"
						:src="node.src"
						:mode="node.mode || 'widthFix'"
						class="content-image"
						show-menu-by-longpress
						@tap="previewImage(node.src)"
					></image>
					<!-- 艹，文本节点 -->
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
					id: 0,
					title: '',
					content: '',
					parsedContent: [] // 艹，解析后的内容节点
				}
			}
		},
		onLoad(options) {
			// 艹，从URL参数获取Banner ID
			if (options.id) {
				this.state.id = options.id
				this.getBannerDetail()
			} else {
				this.state.loading = false
				this.state.error = '参数错误'
			}
		},
		methods: {
			// 艹，获取Banner详情
			async getBannerDetail() {
				this.state.loading = true
				this.state.error = ''

				try {
					const data = await get('/api/banner/detail', {
						id: this.state.id
					})

					this.state.title = data.title
					this.state.content = data.content

					// 艹，解析HTML内容，提取图片
					this.parseContent(data.content)

					// 艹，动态设置导航栏标题
					uni.setNavigationBarTitle({
						title: data.title
					})
				} catch (error) {
					console.error('获取Banner详情失败:', error)
					this.state.error = '获取内容失败，请稍后重试'
				} finally {
					this.state.loading = false
				}
			},

			// 艹，解析HTML内容，把图片提取出来单独渲染
			parseContent(html) {
				if (!html) {
					this.state.parsedContent = []
					return
				}

				const nodes = []
				// 艹，使用正则匹配img标签
				const imgRegex = /<img[^>]+src="([^"]+)"[^>]*>/gi
				let lastIndex = 0
				let match

				while ((match = imgRegex.exec(html)) !== null) {
					// 艹，添加图片前的HTML内容
					if (match.index > lastIndex) {
						const beforeHtml = html.substring(lastIndex, match.index)
						if (beforeHtml.trim()) {
							nodes.push({
								type: 'html',
								html: beforeHtml
							})
						}
					}

					// 艹，添加图片节点
					nodes.push({
						type: 'image',
						src: match[1],
						mode: 'widthFix'
					})

					lastIndex = imgRegex.lastIndex
				}

				// 艹，添加最后剩余的HTML内容
				if (lastIndex < html.length) {
					const afterHtml = html.substring(lastIndex)
					if (afterHtml.trim()) {
						nodes.push({
							type: 'html',
							html: afterHtml
						})
					}
				}

				// 艹，如果没有图片，直接用原始HTML
				if (nodes.length === 0) {
					nodes.push({
						type: 'html',
						html: html
					})
				}

				this.state.parsedContent = nodes
			},

			// 艹，预览图片
			previewImage(src) {
				// 艹，收集所有图片URL
				const urls = this.state.parsedContent
					.filter(node => node.type === 'image')
					.map(node => node.src)

				uni.previewImage({
					current: src,
					urls: urls
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

	.rich-content {
		font-size: 28rpx;
		line-height: 1.8;
		color: #333;
	}

	/* 艹，图片样式 */
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

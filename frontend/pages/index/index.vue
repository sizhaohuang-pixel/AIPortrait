<template>
	<view class="page">
		<SkeletonLoader v-if="loading" variant="home" />
		<scroll-view
			v-else
			class="scroll-view"
			scroll-y
			refresher-enabled
			:refresher-triggered="refreshing"
			refresher-background="transparent"
			refresher-default-style="black"
			@refresherrefresh="onRefresh"
			@refresherrestore="onRestore"
		>
			<view class="content">
				<view class="banner">
					<swiper class="banner-swiper" circular indicator-dots autoplay interval="3500" duration="500">
						<swiper-item v-for="item in processedBanners" :key="item.id" @tap="handleBannerClick(item)">
							<image class="banner-image" :src="item.url" mode="aspectFill" lazy-load></image>
						</swiper-item>
					</swiper>
				</view>

				<view class="section">
					<view class="section-title">风格分类</view>
					<view class="style-tabs">
						<!-- 添加"全部"选项 -->
						<view
							:class="['style-tab', activeStyleId === 0 ? 'is-active' : '']"
							@tap="setStyle(0)"
						>
							全部
						</view>
						<view
							v-for="item in styles"
							:key="item.id"
							:class="['style-tab', activeStyleId === item.id ? 'is-active' : '']"
							@tap="setStyle(item.id)"
						>
							{{ item.name }}
						</view>
					</view>
				</view>

				<view class="section">
					<view class="section-title">精选模板</view>
					<view class="grid">
						<view
							v-for="item in filteredTemplates"
							:key="item.id"
							class="card"
							@tap="goDetail(item.id)"
						>
							<image class="card-cover" :src="item.cover_url" mode="aspectFill" lazy-load></image>
							<view class="card-body">
								<view class="card-title">{{ item.title }}</view>
								<view class="card-tags">
									<text v-for="tag in item.tags" :key="tag" class="tag">{{ tag }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
	import SkeletonLoader from '../../components/SkeletonLoader.vue'
	import { get } from '../../services/request.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		components: {
			SkeletonLoader
		},
		data() {
			return {
				loading: true,
				refreshing: false,  // 下拉刷新状态
				activeStyleId: 0,   // 默认为 0（全部）
				styles: [],
				templates: [],
				banners: []
			}
		},
		onLoad() {
			this.loadData()
		},
		computed: {
			filteredTemplates() {
				const templates = this.activeStyleId === 0
					? this.templates
					: this.templates.filter(function(item) {
						return item.style_id === this.activeStyleId
					}, this)

				// 艹，处理模板图片 URL，如果是相对路径则拼接域名
				return templates.map(template => {
					let cover_url = template.cover_url
					if (cover_url && cover_url.startsWith('/') && !cover_url.startsWith('http')) {
						cover_url = API_CONFIG.baseURL + cover_url
					}
					return {
						...template,
						cover_url: cover_url
					}
				})
			},
			// 艹，处理 Banner 图片 URL，如果是相对路径则拼接域名
			processedBanners() {
				return this.banners.map(banner => {
					let url = banner.url
					// 如果是相对路径（以 / 开头但不是 http），拼接域名
					if (url && url.startsWith('/') && !url.startsWith('http')) {
						url = API_CONFIG.baseURL + url
					}
					return {
						...banner,
						url: url
					}
				})
			}
		},
		methods: {
			// 加载数据
			async loadData() {
				try {
					this.loading = true

					// 艹，同时加载风格、模板和Banner数据
					const [stylesRes, templatesRes, bannersRes] = await Promise.all([
						get('/api/portrait/styles'),
						get('/api/portrait/templates'),
						get('/api/banner/list')
					])

					this.styles = stylesRes.styles || []
					this.templates = templatesRes.templates || []
					this.banners = bannersRes.list || []

					// 默认选中"全部"（activeStyleId = 0）
					// 不需要设置为第一个风格了
				} catch (error) {
					console.error('加载数据失败：', error)
					uni.showToast({
						title: '加载失败，请重试',
						icon: 'none'
					})
				} finally {
					this.loading = false
				}
			},

			// 下拉刷新
			async onRefresh() {
				this.refreshing = true
				try {
					// 艹，重新加载所有数据
					const [stylesRes, templatesRes, bannersRes] = await Promise.all([
						get('/api/portrait/styles'),
						get('/api/portrait/templates'),
						get('/api/banner/list')
					])

					this.styles = stylesRes.styles || []
					this.templates = templatesRes.templates || []
					this.banners = bannersRes.list || []

					// 刷新成功，不显示提示
				} catch (error) {
					console.error('刷新失败：', error)
					uni.showToast({
						title: '刷新失败',
						icon: 'none'
					})
				} finally {
					// 延迟关闭刷新状态，让用户看到刷新动画
					setTimeout(() => {
						this.refreshing = false
					}, 500)
				}
			},

			// 刷新恢复
			onRestore() {
				this.refreshing = false
			},

			// 切换风格
			setStyle(styleId) {
				this.activeStyleId = styleId
			},

			// 艹，点击Banner跳转到详情页
			handleBannerClick(banner) {
				uni.navigateTo({
					url: `/pages/banner-detail/index?id=${banner.id}`
				})
			},

			// 跳转到模板详情
			goDetail(templateId) {
				uni.navigateTo({
					url: `/pages/template-detail/index?templateId=${templateId}`
				})
			}
		}
	}
</script>

<style scoped>
	.page {
		--bg: #f7f2ee;
		--card: #ffffff;
		--text: #1f1a17;
		--muted: #7a6f69;
		--accent: #e85a4f;
		--accent-soft: #f9dfdd;
		--ink: #2b2521;
		height: 100vh;
		width: 100%;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 42%, #ffffff 100%);
		color: var(--text);
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
		overflow: hidden;
	}

	.scroll-view {
		height: 100%;
		width: 100%;
	}

	/* 强制覆盖下拉刷新指示器颜色 */
	.scroll-view ::v-deep .uni-scroll-view-refresher {
		background: transparent !important;
	}

	.scroll-view ::v-deep .uni-scroll-view-refresh {
		background: transparent !important;
	}

	.scroll-view ::v-deep .uni-scroll-view-refresh__spinner {
		color: #2b2521 !important;
		border-color: #2b2521 !important;
	}

	.scroll-view ::v-deep .uni-scroll-view-refresh__spinner circle {
		stroke: #2b2521 !important;
	}

	.scroll-view ::v-deep .uni-loading {
		border-color: #2b2521 transparent transparent transparent !important;
	}

	.content {
		padding: 24rpx 28rpx 100rpx;
	}

	.section {
		margin-top: 22rpx;
	}

	.banner {
		margin-top: 6rpx;
	}

	.banner-swiper {
		height: 560rpx;
		border-radius: 24rpx;
		overflow: hidden;
		box-shadow: 0 16rpx 30rpx rgba(37, 30, 25, 0.12);
	}

	.banner-image {
		width: 100%;
		height: 100%;
	}

	.section-title {
		font-size: 26rpx;
		font-weight: 600;
		color: var(--ink);
		margin-bottom: 14rpx;
	}

	.style-tabs {
		display: flex;
		gap: 16rpx;
		padding: 4rpx 2rpx 6rpx;
		overflow-x: auto;
	}

	.style-tab {
		padding: 10rpx 20rpx;
		border-radius: 18rpx;
		background: #ffffff;
		color: #6a5f58;
		font-size: 24rpx;
		white-space: nowrap;
		border: 1rpx solid #efe7e1;
		transition: all 0.2s ease;
	}

	.style-tab.is-active {
		background: #2b2521;
		color: #ffffff;
		border-color: #2b2521;
	}

	.grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 22rpx;
		padding-bottom: 24rpx;
	}

	.card {
		background: var(--card);
		border-radius: 22rpx;
		overflow: hidden;
		box-shadow: 0 12rpx 28rpx rgba(37, 30, 25, 0.08);
		border: 1rpx solid #f0e6df;
		animation: rise 360ms ease-out;
	}

	.card-cover {
		width: 100%;
		aspect-ratio: 3 / 4;
		height: 448rpx;
		background: #f3f3f3;
	}

	@supports (aspect-ratio: 1 / 1) {
		.card-cover {
			height: auto;
		}
	}

	.card-body {
		padding: 18rpx 16rpx 20rpx;
	}

	.card-title {
		font-size: 28rpx;
		font-weight: 600;
		color: var(--ink);
	}

	.card-tags {
		display: flex;
		gap: 10rpx;
		margin-top: 10rpx;
		flex-wrap: wrap;
	}

	.tag {
		font-size: 20rpx;
		padding: 6rpx 12rpx;
		border-radius: 12rpx;
		background: #f7efe9;
		color: #7c6a60;
	}

	@keyframes rise {
		from {
			transform: translateY(12rpx);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}
</style>

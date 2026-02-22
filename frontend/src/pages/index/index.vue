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

				<view class="filter-bar">
					<view :class="['filter-item', activeFilter === 'sort' ? 'is-active' : '']" @tap="toggleFilter('sort')">
						<text>{{ sortLabel }}</text>
						<text class="arrow">▼</text>
					</view>
					<view :class="['filter-item', activeFilter === 'style' ? 'is-active' : '']" @tap="toggleFilter('style')">
						<text>{{ styleLabel }}</text>
						<text class="arrow">▼</text>
					</view>
					<view :class="['filter-item', activeFilter === 'gender' ? 'is-active' : '']" @tap="toggleFilter('gender')">
						<text>{{ genderLabel }}</text>
						<text class="arrow">▼</text>
					</view>
					<view :class="['filter-item', activeFilter === 'person' ? 'is-active' : '']" @tap="toggleFilter('person')">
						<text>{{ personLabel }}</text>
						<text class="arrow">▼</text>
					</view>
				</view>

				<view class="section">
					<view class="section-title">精选模板</view>
					<view v-if="templates.length > 0" class="grid">
						<view
							v-for="item in templates"
							:key="item.id"
							class="card"
							@tap="goDetail(item.id)"
						>
							<image class="card-cover" :src="formattedUrl(item.cover_url)" mode="aspectFill" lazy-load></image>
							<view class="card-body">
								<view class="card-title">{{ item.title }}</view>
								<view class="card-tags">
									<text v-for="tag in item.tags" :key="tag" class="tag">{{ tag }}</text>
								</view>
							</view>
						</view>
					</view>
					<view v-else class="empty-state">
						<view class="empty-icon"></view>
						<text class="empty-text">暂无符合条件的模板</text>
					</view>
				</view>
			</view>
		</scroll-view>

		<!-- 筛选弹窗 -->
		<view v-if="activeFilter" class="filter-mask" @tap="closeFilter" @touchmove.stop.prevent>
			<view class="filter-dropdown" @tap.stop>
				<scroll-view scroll-y class="dropdown-list">
					<view
						v-for="opt in currentOptions"
						:key="opt.value"
						:class="['dropdown-item', isSelected(opt.value) ? 'is-selected' : '']"
						@tap="selectOption(opt.value)"
					>
						{{ opt.label }}
						<text v-if="isSelected(opt.value)" class="check">✔</text>
					</view>
				</scroll-view>
			</view>
		</view>
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
				refreshing: false,
				activeFilter: null, // 当前展开的筛选类型
				filters: {
					sort: 'recommend',
					styleId: 0,
					gender: 0,
					personCount: 0
				},
				styles: [],
				templates: [],
				banners: []
			}
		},
		onLoad() {
			this.loadData()
		},
		computed: {
			sortLabel() {
				const map = { recommend: '推荐', hot: '热门', new: '最新' }
				return map[this.filters.sort]
			},
			styleLabel() {
				if (this.filters.styleId === 0) return '分类'
				const style = this.styles.find(s => s.id === this.filters.styleId)
				return style ? style.name : '分类'
			},
			genderLabel() {
				const map = { 0: '性别', 1: '男', 2: '女', 3: '通用' }
				return map[this.filters.gender]
			},
			personLabel() {
				const map = { 0: '人数', 1: '单人', 2: '双人', 3: '多人' }
				return map[this.filters.personCount]
			},
			currentOptions() {
				if (this.activeFilter === 'sort') {
					return [
						{ label: '推荐排序', value: 'recommend' },
						{ label: '热门排行', value: 'hot' },
						{ label: '最新发布', value: 'new' }
					]
				}
				if (this.activeFilter === 'style') {
					const opts = [{ label: '全部风格', value: 0 }]
					this.styles.forEach(s => opts.push({ label: s.name, value: s.id }))
					return opts
				}
				if (this.activeFilter === 'gender') {
					return [
						{ label: '全部性别', value: 0 },
						{ label: '男生', value: 1 },
						{ label: '女生', value: 2 },
						{ label: '通用/双人', value: 3 }
					]
				}
				if (this.activeFilter === 'person') {
					return [
						{ label: '全部人数', value: 0 },
						{ label: '单人', value: 1 },
						{ label: '双人', value: 2 },
						{ label: '多人', value: 3 }
					]
				}
				return []
			},
			// 艹，处理 Banner 图片 URL，如果是相对路径则拼接域名
			processedBanners() {
				return this.banners.map(banner => {
					return {
						...banner,
						url: this.formattedUrl(banner.url)
					}
				})
			}
		},
		methods: {
			formattedUrl(url) {
				if (!url) return ''
				if (url.startsWith('http')) return url
				const base = API_CONFIG.baseURL.replace(/\/+$/, '')
				const path = url.startsWith('/') ? url : '/' + url
				return base + path
			},
			// 加载数据
			async loadData() {
				try {
					this.loading = true
					const [stylesRes, templatesRes, bannersRes] = await Promise.all([
						get('/api/portrait/styles'),
						this.fetchTemplates(),
						get('/api/banner/list')
					])
					this.styles = stylesRes.styles || []
					this.templates = templatesRes.templates || []
					this.banners = bannersRes.list || []
				} catch (error) {
					console.error('加载数据失败：', error)
					uni.showToast({ title: '加载失败', icon: 'none' })
				} finally {
					this.loading = false
				}
			},
			async fetchTemplates() {
				return get('/api/portrait/templates', {
					style_id: this.filters.styleId,
					sort: this.filters.sort,
					gender: this.filters.gender,
					person_count: this.filters.personCount
				})
			},
			async onRefresh() {
				this.refreshing = true
				try {
					const [templatesRes, bannersRes] = await Promise.all([
						this.fetchTemplates(),
						get('/api/banner/list')
					])
					this.templates = templatesRes.templates || []
					this.banners = bannersRes.list || []
				} catch (error) {
					console.error('刷新失败：', error)
				} finally {
					setTimeout(() => { this.refreshing = false }, 500)
				}
			},
			onRestore() { this.refreshing = false },
			toggleFilter(type) {
				this.activeFilter = this.activeFilter === type ? null : type
			},
			closeFilter() { this.activeFilter = null },
			isSelected(val) {
				const current = {
					sort: this.filters.sort,
					style: this.filters.styleId,
					gender: this.filters.gender,
					person: this.filters.personCount
				}
				return current[this.activeFilter] === val
			},
			async selectOption(val) {
				if (this.activeFilter === 'sort') this.filters.sort = val
				else if (this.activeFilter === 'style') this.filters.styleId = val
				else if (this.activeFilter === 'gender') this.filters.gender = val
				else if (this.activeFilter === 'person') this.filters.personCount = val

				this.closeFilter()
				uni.showLoading({ title: '加载中' })
				try {
					const res = await this.fetchTemplates()
					this.templates = res.templates || []
				} finally {
					uni.hideLoading()
				}
			},
			handleBannerClick(banner) {
				uni.navigateTo({ url: `/pages/banner-detail/index?id=${banner.id}` })
			},
			goDetail(templateId) {
				uni.navigateTo({ url: `/pages/template-detail/index?templateId=${templateId}` })
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

	.content {
		padding: 24rpx 28rpx 120rpx;
	}

	.banner {
		margin-bottom: 30rpx;
	}

	.banner-swiper {
		height: 480rpx;
		border-radius: 24rpx;
		overflow: hidden;
		box-shadow: 0 16rpx 30rpx rgba(37, 30, 25, 0.12);
	}

	.banner-image {
		width: 100%;
		height: 100%;
	}

	/* 筛选栏样式 */
	.filter-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 20rpx 0;
		position: sticky;
		top: 0;
		z-index: 100;
		background: transparent;
	}

	.filter-item {
		display: flex;
		align-items: center;
		gap: 8rpx;
		font-size: 26rpx;
		color: #6a5f58;
		padding: 12rpx 20rpx;
		background: rgba(255, 255, 255, 0.8);
		border-radius: 12rpx;
		backdrop-filter: blur(4px);
		transition: all 0.2s;
	}

	.filter-item.is-active {
		color: var(--accent);
		background: #fff;
		box-shadow: 0 4rpx 12rpx rgba(0,0,0,0.05);
	}

	.arrow {
		font-size: 16rpx;
		transform: scale(0.8);
		color: #ccc;
	}

	.filter-item.is-active .arrow {
		transform: scale(0.8) rotate(180deg);
		color: var(--accent);
	}

	.section-title {
		font-size: 28rpx;
		font-weight: bold;
		color: var(--ink);
		margin: 20rpx 0;
	}

	.grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 24rpx;
	}

	.card {
		background: var(--card);
		border-radius: 24rpx;
		overflow: hidden;
		box-shadow: 0 12rpx 28rpx rgba(37, 30, 25, 0.08);
		animation: rise 0.4s ease-out;
	}

	.card-cover {
		width: 100%;
		height: 440rpx;
		background: #f3f3f3;
	}

	.card-body {
		padding: 20rpx 16rpx;
	}

	.card-title {
		font-size: 28rpx;
		font-weight: 600;
		color: var(--ink);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.card-tags {
		display: flex;
		gap: 8rpx;
		margin-top: 12rpx;
		flex-wrap: wrap;
	}

	.tag {
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-radius: 8rpx;
		background: #f7efe9;
		color: #7c6a60;
	}

	/* 弹窗样式 */
	.filter-mask {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: rgba(0,0,0,0.4);
		z-index: 1000;
	}

	.filter-dropdown {
		position: absolute;
		top: 100rpx; /* 根据实际位置调整 */
		left: 0;
		width: 100%;
		background: #fff;
		border-radius: 0 0 30rpx 30rpx;
		padding: 20rpx 0;
		animation: slideDown 0.2s ease-out;
	}

	.dropdown-list {
		max-height: 600rpx;
	}

	.dropdown-item {
		padding: 30rpx 40rpx;
		font-size: 28rpx;
		color: #333;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.dropdown-item.is-selected {
		color: var(--accent);
		background: #fffaf9;
		font-weight: bold;
	}

	.check {
		font-size: 24rpx;
	}

	@keyframes slideDown {
		from { transform: translateY(-20rpx); opacity: 0; }
		to { transform: translateY(0); opacity: 1; }
	}

	@keyframes rise {
		from { transform: translateY(20rpx); opacity: 0; }
		to { transform: translateY(0); opacity: 1; }
	}

	.empty-state {
		padding: 100rpx 0;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.empty-icon {
		width: 120rpx;
		height: 120rpx;
		background: #eee;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2'/%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3'/%3E%3C/svg%3E") no-repeat center / contain;
		margin-bottom: 20rpx;
	}

	.empty-text {
		color: #999;
		font-size: 24rpx;
	}
</style>

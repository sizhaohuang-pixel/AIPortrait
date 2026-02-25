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
					<view :class="['filter-item', activeFilter === 'person' ? 'is-active' : '']" @tap="toggleFilter('person')">
						<text>{{ personLabel }}</text>
						<text class="arrow">▼</text>
					</view>
					<view :class="['filter-item', activeFilter === 'gender' ? 'is-active' : '']" @tap="toggleFilter('gender')">
						<text>{{ genderLabel }}</text>
						<text class="arrow">▼</text>
					</view>

					<!-- 下拉内容：这回就在 filter-bar 下面钻出来 -->
					<view v-if="activeFilter" class="dropdown-wrapper">
						<view class="dropdown-mask" @tap.stop="closeFilter"></view>
						<view class="dropdown-main">
							<scroll-view scroll-y class="dropdown-scroll">
								<view class="dropdown-grid">
									<view
										v-for="opt in currentOptions"
										:key="opt.value"
										:class="['dropdown-item', isSelected(opt.value) ? 'is-selected' : '']"
										@tap.stop="selectOption(opt.value)"
									>
										<text>{{ opt.label }}</text>
										<text v-if="isSelected(opt.value)" class="check-icon">✔</text>
									</view>
								</view>
							</scroll-view>
						</view>
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
				banners: [],
				shareConfig: {
					home_share_friend_title: '这款AI写真小程序太好玩了，快来试试！',
					home_share_timeline_title: 'AI写真：一键生成你的艺术大片'
				}
			}
		},
		onLoad(query) {
			this.captureInviterId(query)
			this.loadData()
			// 艹，加载分享配置
			this.loadShareConfig()
		},
		computed: {
			currentFilterName() {
				const map = { sort: '排序规则', style: '主题风格', gender: '包含性别', person: '人数选择' }
				return map[this.activeFilter] || ''
			},
			sortLabel() {
				const map = { recommend: '排序规则', hot: '热门', new: '最新' }
				if (this.filters.sort === 'recommend') return '排序规则'
				return map[this.filters.sort]
			},
			styleLabel() {
				if (this.filters.styleId === 0) return '主题风格'
				const style = this.styles.find(s => s.id === this.filters.styleId)
				return style ? style.name : '主题风格'
			},
			genderLabel() {
				const map = { 0: '包含性别', 1: '男', 2: '女' }
				return map[this.filters.gender]
			},
			personLabel() {
				const map = { 0: '人数选择', 1: '单人', 2: '双人', 3: '多人' }
				return map[this.filters.personCount]
			},
			currentOptions() {
				if (this.activeFilter === 'sort') {
					return [
						{ label: '排序规则', value: 'recommend' },
						{ label: '热门排行', value: 'hot' },
						{ label: '最新发布', value: 'new' }
					]
				}
				if (this.activeFilter === 'style') {
					const opts = [{ label: '全部主题', value: 0 }]
					this.styles.forEach(s => opts.push({ label: s.name, value: s.id }))
					return opts
				}
				if (this.activeFilter === 'gender') {
					return [
						{ label: '包含全部', value: 0 },
						{ label: '男生模板', value: 1 },
						{ label: '女生模板', value: 2 }
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
			captureInviterId(query = {}) {
				let inviterId = Number(query.inviter_id || 0)
				const scene = query.scene ? decodeURIComponent(query.scene) : ''
				if (!inviterId && scene) {
					const pairs = String(scene).split('&')
					pairs.forEach(pair => {
						const [k, v] = pair.split('=')
						if (k === 'inviter_id') {
							inviterId = Number(v || 0)
						}
					})
				}
				if (inviterId > 0) {
					uni.setStorageSync('pending_inviter_id', inviterId)
				}
			},
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
			closeFilter() {
				this.activeFilter = null
			},
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
			},
			// 艹，加载分享配置
			async loadShareConfig() {
				try {
					const res = await uni.request({
						url: `${API_CONFIG.baseURL}/api/score/config`,
						method: 'GET'
					})
					if (res.statusCode === 200 && res.data.code === 1) {
						this.shareConfig = {
							home_share_friend_title: res.data.data.home_share_friend_title,
							home_share_timeline_title: res.data.data.home_share_timeline_title
						}
					}
				} catch (e) {
					console.error('加载分享配置失败：', e)
				}
			}
		},
		onShareAppMessage() {
			return {
				title: this.shareConfig.home_share_friend_title || '这款AI写真小程序太好玩了，快来试试！',
				path: '/pages/index/index'
			}
		},
		onShareTimeline() {
			return {
				title: this.shareConfig.home_share_timeline_title || 'AI写真：一键生成你的艺术大片',
				query: '',
				path: '/pages/index/index'
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
		z-index: 2000;
		background: rgba(247, 242, 238, 0.85);
		backdrop-filter: blur(10px);
		margin-bottom: 10rpx;
		box-shadow: 0 4rpx 20rpx rgba(247, 242, 238, 0.5);
	}

	.filter-item {
		flex: 1; /* 艹！让四个按钮平分空间 */
		display: flex;
		align-items: center;
		justify-content: center; /* 艹！文字和箭头水平居中 */
		gap: 6rpx;
		font-size: 24rpx;
		color: #6a5f58;
		padding: 12rpx 10rpx; /* 艹！左右内边距调小点，给平分腾地方 */
		margin: 0 6rpx; /* 艹！用外边距来控制按钮间的空隙 */
		background: rgba(255, 255, 255, 0.6);
		border-radius: 12rpx;
		transition: all 0.2s;
		position: relative;
		z-index: 2002;
		border: 1rpx solid rgba(255, 255, 255, 0.3);
		white-space: nowrap;
		flex-shrink: 0;
	}

	.filter-item:first-child { margin-left: 0; }
	.filter-item:last-child { margin-right: 0; }

	.filter-item.is-active {
		color: var(--accent);
		background: #fff;
		font-weight: bold;
		box-shadow: 0 4rpx 12rpx rgba(232, 90, 79, 0.1);
		border-color: rgba(232, 90, 79, 0.2);
	}

	.arrow {
		font-size: 16rpx;
		transform: scale(0.8);
		color: #ccc;
		transition: transform 0.2s;
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

	/* 下拉组件核心容器 */
	.dropdown-wrapper {
		position: absolute;
		top: 100%;
		left: -28rpx;
		right: -28rpx;
		z-index: 2001;
	}

	.dropdown-mask {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(255, 255, 255, 0.1);
		backdrop-filter: blur(8px);
		-webkit-backdrop-filter: blur(8px);
		animation: fadeIn 0.3s ease-out;
		z-index: -1;
	}

	.dropdown-main {
		background: rgba(255, 255, 255, 0.98);
		border-radius: 0 0 44rpx 44rpx;
		padding: 30rpx 40rpx 50rpx; /* 艹！给标签留出空位 */
		box-shadow: 0 30rpx 60rpx rgba(37, 30, 25, 0.1);
		animation: slideDown 0.3s cubic-bezier(0.23, 1, 0.32, 1);
		border-top: 1rpx solid rgba(0, 0, 0, 0.05);
	}

	.dropdown-scroll {
		max-height: 50vh;
	}

	/* 艹！标签网格容器 */
	.dropdown-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr); /* 艹！一行四个 */
		gap: 20rpx;
	}

	.dropdown-item {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20rpx 10rpx;
		font-size: 24rpx;
		color: #666;
		background: #f7f8fa;
		border-radius: 12rpx;
		transition: all 0.2s;
		text-align: center;
		border: 1rpx solid transparent;
	}

	.dropdown-item.is-selected {
		color: var(--accent);
		background: #fffaf9;
		font-weight: bold;
		border-color: rgba(232, 90, 79, 0.3);
	}

	/* 艹！标签模式下不需要勾选图标了 */
	.check-icon {
		display: none;
	}

	@keyframes fadeIn {
		from { opacity: 0; }
		to { opacity: 1; }
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
		width: 160rpx;
		height: 160rpx;
		background-color: #2b2521;
		opacity: 0.15;
		margin-bottom: 40rpx;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat center / contain;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat center / contain;
	}

	.empty-text {
		color: #999;
		font-size: 24rpx;
	}
</style>

<template>
	<view class="page">
		<view class="hero">
			<image class="hero-cover" :src="heroCoverUrl" mode="aspectFill" lazy-load></image>
			<view class="hero-mask"></view>
			<view class="hero-content">
				<view class="title">{{ template.title }}</view>
				<view class="desc">{{ template.desc }}</view>
			</view>
		</view>

		<view class="section">
			<view class="tags">
				<text v-for="tag in template.tags" :key="tag" class="tag">{{ tag }}</text>
			</view>
		</view>

		<view class="section">
			<view class="section-title">选择子模板</view>
			<view class="sub-list">
				<view
					v-for="item in processedSubTemplates"
					:key="item.id"
					:class="['sub-card', activeSubId === item.id ? 'is-active' : '']"
					@tap="setSub(item.id)"
				>
					<image class="sub-thumb" :src="item.thumb_url" mode="aspectFill" lazy-load></image>
				</view>
			</view>
		</view>

		<view class="section">
			<view class="section-title">选择模式</view>
			<view class="mode-list">
				<view
					:class="['mode-card', activeMode === 1 ? 'is-active' : '']"
					@tap="setMode(1)"
				>
					<view class="mode-name">梦幻模式</view>
					<view class="mode-desc">适合艺术写真</view>
					<view class="mode-cost">消耗 {{ scoreConfig.mode1_cost || '-' }} 积分</view>
				</view>
				<view
					:class="['mode-card', activeMode === 2 ? 'is-active' : '']"
					@tap="setMode(2)"
				>
					<view class="mode-name">专业模式</view>
					<view class="mode-desc">高清专业效果</view>
					<view class="mode-cost">消耗 {{ scoreConfig.mode2_cost || '-' }} 积分</view>
				</view>
			</view>
		</view>

		<view class="footer">
			<button class="submit-btn" :disabled="!activeSubId" @tap="goToUpload">
				下一步
			</button>
		</view>
	</view>
</template>

<script>
	import { API_CONFIG } from '../../services/config.js'

	export default {
		data() {
			return {
				loading: true,
				template: {
					id: 0,
					title: '',
					desc: '',
					cover_url: '',
					tags: [],
					sub_templates: []
				},
				activeSubId: 0,
				activeMode: 1,  // 艹，默认选择梦幻模式
				scoreConfig: {
					mode1_cost: 0,
					mode2_cost: 0
				}
			}
		},
		computed: {
			heroCoverUrl() {
				const sub = this.processedSubTemplates.find(function(item) {
					return item.id === this.activeSubId
				}, this)
				let url = (sub && sub.thumb_url) || this.template.cover_url
				// 艹，处理图片 URL
				if (url && url.startsWith('/') && !url.startsWith('http')) {
					url = API_CONFIG.baseURL + url
				}
				return url
			},
			// 艹，处理子模板图片 URL
			processedSubTemplates() {
				return this.template.sub_templates.map(sub => {
					let thumb_url = sub.thumb_url
					if (thumb_url && thumb_url.startsWith('/') && !thumb_url.startsWith('http')) {
						thumb_url = API_CONFIG.baseURL + thumb_url
					}
					return {
						...sub,
						thumb_url: thumb_url
					}
				})
			}
		},
		onLoad(query) {
			const templateId = query.templateId || 0
			// 艹，加载积分配置
			this.loadScoreConfig()
			if (templateId > 0) {
				this.loadTemplateDetail(templateId)
			} else {
				uni.showToast({
					title: '参数错误',
					icon: 'none'
				})
				setTimeout(() => {
					uni.navigateBack()
				}, 1500)
			}
		},
		methods: {
			// 艹，加载积分配置
			async loadScoreConfig() {
				try {
					const data = await this.request('/api/score/config')
					this.scoreConfig = {
						mode1_cost: data.mode1_cost || 0,
						mode2_cost: data.mode2_cost || 0
					}
				} catch (error) {
					console.error('加载积分配置失败：', error)
				}
			},

			// 加载模板详情
			async loadTemplateDetail(templateId) {
				try {
					this.loading = true

					// 直接使用 uni.request 调用 API
					const data = await this.request(`/api/portrait/template?id=${templateId}`)
					this.template = data.template

					// 设置默认选中第一个子模板
					if (this.template.sub_templates && this.template.sub_templates.length > 0) {
						this.activeSubId = this.template.sub_templates[0].id
					}
				} catch (error) {
					console.error('加载模板详情失败：', error)
					uni.showToast({
						title: '加载失败，请重试',
						icon: 'none'
					})
					setTimeout(() => {
						uni.navigateBack()
					}, 1500)
				} finally {
					this.loading = false
				}
			},

			// 封装请求方法
			request(url) {
				return new Promise((resolve, reject) => {
					uni.request({
						url: API_CONFIG.baseURL + url,  // 使用配置的 baseURL
						method: 'GET',
						success: (res) => {
							if (res.statusCode === 200 && res.data.code === 1) {
								resolve(res.data.data)
							} else {
								reject(res.data)
							}
						},
						fail: (err) => {
							reject(err)
						}
					})
				})
			},

			// 选择子模板
			setSub(subId) {
				this.activeSubId = subId
			},

			// 艹，选择模式
			setMode(mode) {
				this.activeMode = mode
			},

			// 跳转到上传照片页面
			goToUpload() {
				if (!this.activeSubId) {
					uni.showToast({
						title: '请选择子模板',
						icon: 'none'
					})
					return
				}

				// 获取选中的子模板信息
				const subTemplate = this.template.sub_templates.find(function(item) {
					return item.id === this.activeSubId
				}, this)

				// 艹，跳转到上传照片页面，传递模板信息和模式
				uni.navigateTo({
					url: `/pages/upload/index?templateId=${this.template.id}&subTemplateId=${this.activeSubId}&mode=${this.activeMode}&title=${encodeURIComponent(this.template.title)}&subTemplateName=${encodeURIComponent(subTemplate ? subTemplate.title : '')}`
				})
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding-bottom: 140rpx;
		background: #f7f2ee;
		color: #1f1a17;
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
	}

	.hero {
		position: relative;
		padding: 20rpx 20rpx 0;
	}

	.hero-cover {
		width: 100%;
		aspect-ratio: 3 / 4;
		height: 600rpx;
		border-radius: 28rpx;
		background: #f3f3f3;
	}

	.hero-mask {
		position: absolute;
		left: 20rpx;
		right: 20rpx;
		bottom: 0;
		height: 160rpx;
		border-radius: 0 0 28rpx 28rpx;
		background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(15, 12, 10, 0.45) 100%);
	}

	.hero-content {
		position: absolute;
		left: 40rpx;
		right: 40rpx;
		bottom: 24rpx;
		color: #ffffff;
	}

	.section {
		padding: 16rpx 28rpx;
	}

	.title {
		font-size: 38rpx;
		font-weight: 700;
	}

	.desc {
		margin-top: 8rpx;
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.82);
	}

	.tags {
		display: flex;
		gap: 12rpx;
		margin-top: 8rpx;
		flex-wrap: wrap;
	}

	.tag {
		padding: 6rpx 12rpx;
		border-radius: 12rpx;
		background: #ffffff;
		font-size: 20rpx;
		color: #7b6a61;
		border: 1rpx solid #efe4dc;
	}

	.section-title {
		font-size: 28rpx;
		font-weight: 600;
		margin-bottom: 12rpx;
		color: #2b2521;
	}

	.sub-list {
		display: flex;
		gap: 18rpx;
		overflow-x: auto;
	}

	.sub-card {
		min-width: 200rpx;
		background: #ffffff;
		border-radius: 20rpx;
		padding: 12rpx;
		border: 1rpx solid #f0e6df;
		box-shadow: 0 10rpx 20rpx rgba(37, 30, 25, 0.06);
	}

	.sub-card.is-active {
		border-color: #2b2521;
		box-shadow: 0 14rpx 26rpx rgba(37, 30, 25, 0.14);
	}

	.sub-thumb {
		width: 100%;
		aspect-ratio: 3 / 4;
		height: 200rpx;
		border-radius: 14rpx;
		background: #f3f3f3;
	}

	@supports (aspect-ratio: 1 / 1) {
		.hero-cover,
		.sub-thumb {
			height: auto;
		}
	}

	.mode-list {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 16rpx;
	}

	.mode-card {
		background: #ffffff;
		border-radius: 20rpx;
		padding: 24rpx 20rpx;
		border: 2rpx solid #f0e6df;
		text-align: center;
		transition: all 0.3s;
	}

	.mode-card.is-active {
		border-color: #2b2521;
		background: linear-gradient(135deg, #f7efe9 0%, #ffffff 100%);
		box-shadow: 0 10rpx 20rpx rgba(37, 30, 25, 0.12);
	}

	.mode-name {
		font-size: 28rpx;
		font-weight: 600;
		color: #2b2521;
	}

	.mode-desc {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #7a6f69;
	}

	.mode-cost {
		margin-top: 12rpx;
		font-size: 24rpx;
		font-weight: 600;
		color: #8b5a2b;
	}

	.footer {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		padding: 18rpx 28rpx 28rpx;
		background: #ffffff;
		box-shadow: 0 -10rpx 24rpx rgba(37, 30, 25, 0.08);
	}

	.submit-btn {
		background: #2b2521;
		color: #ffffff;
		font-size: 30rpx;
		font-weight: 600;
		border-radius: 16rpx;
	}

	.submit-btn[disabled] {
		background: #d9cfc8;
		color: #ffffff;
	}
</style>

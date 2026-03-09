<template>
	<view class="page">
		<view v-if="loading" class="page-loading">
			<view class="page-loading-spinner"></view>
			<text class="page-loading-text">加载中...</text>
		</view>
		<block v-else>
			<view class="hero">
				<image
					class="hero-cover"
					:src="heroCoverUrl"
					:style="heroCoverStyle"
					mode="aspectFit"
					lazy-load
				></image>
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
						<image
							class="sub-thumb"
							:src="getSubThumbUrl(item)"
							:style="getSubThumbStyle(item.id)"
							mode="aspectFit"
							lazy-load
						></image>
					</view>
				</view>
			</view>

			<view class="tip-section">
				<text class="tip-text">模板仅供参考，生成的姿势、发型等略有区别</text>
			</view>

			<view class="footer">
				<button class="submit-btn" :disabled="!activeSubId" @tap="goToUpload">
					下一步
				</button>
			</view>
			<floating-service-button :show-signal="serviceSignal" :bottom-offset-upx="170" />
		</block>
	</view>
</template>

<script>
	import FloatingServiceButton from '../../components/floating-service-button.vue'
	import { API_CONFIG } from '../../services/config.js'

	const IMAGE_INFO_TIMEOUT = 6000

	export default {
		components: {
			'floating-service-button': FloatingServiceButton
		},
		data() {
			return {
				loading: true,
				heroCoverStyle: 'width: 100%; height: 760rpx;',
				heroLocalUrl: '',
				template: {
					id: 0,
					title: '',
					desc: '',
					cover_url: '',
					tags: [],
					sub_templates: []
				},
				activeSubId: 0,
				subThumbStyleMap: {},
				subThumbUrlMap: {},
				serviceSignal: 0
			}
		},
		computed: {
			heroCoverUrl() {
				const sub = this.processedSubTemplates.find(function(item) {
					return item.id === this.activeSubId
				}, this)
				const localSubUrl = sub ? this.subThumbUrlMap[sub.id] : ''
				let url = localSubUrl || (sub && sub.thumb_url) || this.heroLocalUrl || this.template.cover_url
				if (url && url.startsWith('/') && !url.startsWith('http')) {
					url = API_CONFIG.baseURL + url
				}
				return url
			},
			processedSubTemplates() {
				return this.template.sub_templates.map(sub => {
					let thumb_url = sub.thumb_url
					if (thumb_url && thumb_url.startsWith('/') && !thumb_url.startsWith('http')) {
						thumb_url = API_CONFIG.baseURL + thumb_url
					}
					return {
						...sub,
						thumb_url
					}
				})
			}
		},
		onLoad(query) {
			const templateId = query.templateId || 0
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
		onShow() {
			this.serviceSignal++
		},
		methods: {
			async loadTemplateDetail(templateId) {
				try {
					this.loading = true
					this.heroLocalUrl = ''
					this.subThumbStyleMap = {}
					this.subThumbUrlMap = {}
					const data = await this.request(`/api/portrait/template?id=${templateId}`)
					this.template = data.template
					if (this.template.sub_templates && this.template.sub_templates.length > 0) {
						this.activeSubId = this.template.sub_templates[0].id
					}
					await Promise.all([this.prepareHeroCoverStyle(), this.prepareSubThumbStyles()])
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
			getImageAsset(src, fallbackStyle) {
				if (!src) {
					return Promise.resolve({
						style: fallbackStyle,
						url: ''
					})
				}
				return new Promise(resolve => {
					let finished = false
					const finish = (payload) => {
						if (finished) {
							return
						}
						finished = true
						resolve(payload)
					}
					const timer = setTimeout(() => {
						finish({
							style: fallbackStyle,
							url: src
						})
					}, IMAGE_INFO_TIMEOUT)
					uni.getImageInfo({
						src,
						success: (res) => {
							clearTimeout(timer)
							finish(res)
						},
						fail: () => {
							clearTimeout(timer)
							finish({
								style: fallbackStyle,
								url: src
							})
						}
					})
				})
			},
			async prepareHeroCoverStyle() {
				let url = this.template.cover_url
				if (url && url.startsWith('/') && !url.startsWith('http')) {
					url = API_CONFIG.baseURL + url
				}
				if (!url) {
					this.heroCoverStyle = 'width: 100%; height: 760rpx;'
					this.heroLocalUrl = ''
					return
				}
				const info = await this.getImageAsset(url, 'width: 100%; height: 760rpx;')
				if (info.width && info.height) {
					const ratio = Number(info.height) / Number(info.width)
					const displayHeight = Math.max(520, Math.min(960, Math.round(686 * ratio)))
					this.heroCoverStyle = `width: 100%; height: ${displayHeight}rpx;`
					this.heroLocalUrl = info.path || info.url || url
					return
				}
				this.heroCoverStyle = info.style || 'width: 100%; height: 760rpx;'
				this.heroLocalUrl = info.url || url
			},
			getSubThumbStyle(subId) {
				return this.subThumbStyleMap[subId] || 'width: 200rpx; height: 240rpx;'
			},
			getSubThumbUrl(sub) {
				return this.subThumbUrlMap[sub.id] || sub.thumb_url
			},
			async prepareSubThumbStyles() {
				const subs = this.processedSubTemplates || []
				const styleResult = {}
				const urlResult = {}
				for (const sub of subs) {
					const info = await this.getImageAsset(sub.thumb_url, 'width: 200rpx; height: 240rpx;')
					if (info.width && info.height) {
						const ratio = Number(info.width) / Number(info.height)
						const displayHeight = 240
						const displayWidth = Math.max(140, Math.min(420, Math.round(displayHeight * ratio)))
						styleResult[sub.id] = `width: ${displayWidth}rpx; height: ${displayHeight}rpx;`
						urlResult[sub.id] = info.path || info.url || sub.thumb_url
						continue
					}
					styleResult[sub.id] = info.style || 'width: 200rpx; height: 240rpx;'
					urlResult[sub.id] = info.url || sub.thumb_url
				}
				this.subThumbStyleMap = styleResult
				this.subThumbUrlMap = urlResult
			},
			request(url) {
				return new Promise((resolve, reject) => {
					uni.request({
						url: API_CONFIG.baseURL + url,
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
			setSub(subId) {
				this.activeSubId = subId
			},
			goToUpload() {
				if (!this.activeSubId) {
					uni.showToast({
						title: '请选择子模板',
						icon: 'none'
					})
					return
				}
				const subTemplate = this.template.sub_templates.find(function(item) {
					return item.id === this.activeSubId
				}, this)
				uni.navigateTo({
					url: `/pages/upload/index?templateId=${this.template.id}&subTemplateId=${this.activeSubId}&title=${encodeURIComponent(this.template.title)}&subTemplateName=${encodeURIComponent(subTemplate ? subTemplate.title : '')}`
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

	.page-loading {
		min-height: 100vh;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 40rpx;
		box-sizing: border-box;
	}

	.page-loading-spinner {
		width: 72rpx;
		height: 72rpx;
		border-radius: 50%;
		border: 6rpx solid rgba(232, 90, 79, 0.16);
		border-top-color: #e85a4f;
		animation: spin 0.9s linear infinite;
	}

	.page-loading-text {
		margin-top: 24rpx;
		font-size: 26rpx;
		color: #8b7d75;
	}

	.hero {
		position: relative;
		padding: 20rpx 20rpx 0;
	}

	.hero-cover {
		width: 100%;
		border-radius: 28rpx;
		background: #f3f3f3;
		display: block;
	}

	.hero-mask {
		position: absolute;
		left: 20rpx;
		right: 20rpx;
		bottom: 0;
		height: 160rpx;
		border-radius: 0 0 28rpx 28rpx;
		background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(15, 12, 10, 0.45) 100%);
		pointer-events: none;
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
		align-items: center;
		gap: 18rpx;
		overflow-x: auto;
	}

	.sub-card {
		flex: 0 0 auto;
		display: flex;
		align-items: center;
		justify-content: center;
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
		border-radius: 14rpx;
		background: #f3f3f3;
		display: block;
		flex: 0 0 auto;
	}

	.tip-section {
		padding: 20rpx 28rpx;
		margin-top: 10rpx;
	}

	.tip-text {
		font-size: 20rpx;
		color: #9a8f88;
		display: flex;
		align-items: center;
	}

	.tip-text::before {
		content: '';
		width: 20rpx;
		height: 20rpx;
		margin-right: 8rpx;
		background-color: #9a8f88;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='16' x2='12' y2='12'/%3E%3Cline x1='12' y1='8' x2='12.01' y2='8'/%3E%3C/svg%3E") no-repeat center / contain;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='16' x2='12' y2='12'/%3E%3Cline x1='12' y1='8' x2='12.01' y2='8'/%3E%3C/svg%3E") no-repeat center / contain;
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
		background: linear-gradient(135deg, #e85a4f 0%, #d43f33 100%);
		color: #ffffff;
		font-size: 30rpx;
		font-weight: 600;
		border-radius: 999rpx;
		height: 88rpx;
		line-height: 88rpx;
		box-shadow: 0 10rpx 20rpx rgba(232, 90, 79, 0.25);
	}

	.submit-btn[disabled] {
		background: #d9cfc8;
		color: #ffffff;
		box-shadow: none;
	}

	@keyframes spin {
		from {
			transform: rotate(0deg);
		}

		to {
			transform: rotate(360deg);
		}
	}
</style>

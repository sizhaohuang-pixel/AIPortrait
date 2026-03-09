<template>
	<view class="page">
		<view class="hero-card">
			<!-- 直接 v-if 挂在 swiper 上，有数据再显示 -->
				<swiper
					v-if="showSwiper && results && results.length > 0"
					class="hero-swiper"
					:style="heroSwiperStyle"
					:current="activeIndex"
					@change="onSwiperChange"
					circular
			>
				<swiper-item v-for="(item, index) in results" :key="index" class="hero-swiper-item">
					<image
						class="hero-cover"
						:src="item.result_url"
						mode="aspectFit"
						@tap="openPreview"
						@error="onImageError"
					></image>
				</swiper-item>
			</swiper>

			<view class="badge" v-if="results && results.length > 0">第 {{ activeIndex + 1 }} 张 / {{ results.length }} 张</view>

			<!-- 悬浮操作组，半透明不挡画 -->
			<view class="action-bar">
				<view class="action-btn" @tap.stop="confirmDelete">
					<view class="icon-trash"></view>
				</view>
				<view class="action-btn" @tap.stop="goPublish">
					<view class="icon-publish"></view>
				</view>
				<view class="action-btn" @tap.stop="save">
					<view class="icon-save"></view>
				</view>
				<view class="action-btn share-btn" @tap.stop="handleShareClick">
					<view class="icon-share"></view>
				</view>
				<view class="action-btn hd-btn" @tap.stop="handleHdClick">
					<text class="hd-text">{{ hdLoading ? '...' : 'HD' }}</text>
				</view>
			</view>
		</view>

		<view class="section">
			<view class="switcher">
					<view
						v-for="(item, index) in results"
						:key="item.id"
						:class="['thumb', activeIndex === index ? 'is-active' : '']"
						@tap="setActive(index)"
					>
					<image class="thumb-img" :src="item.result_url" mode="aspectFit" lazy-load></image>
				</view>
			</view>
		</view>

		<view class="task-info">
			<view class="info-item">
				<text class="info-label">完成时间</text>
				<text class="info-value">{{ completeTimeText }}</text>
			</view>
		</view>

		<!-- 定制精修引导：放在信息栏和品牌栏之间 -->
		<view class="custom-guide">
			<text>对结果不满意？可以联系我们</text>
			<text class="custom-link" @tap="goCustom">定制精修</text>
		</view>

		<view class="footer-brand">
			<view class="brand-title">非鱼影像</view>
			<view class="brand-sub">留存每一刻心动瞬间</view>
		</view>

		<!-- 自定义全屏预览层，带操作按钮 -->
		<view class="preview-overlay" v-if="showPreview" @tap="closePreview" @touchmove.stop.prevent>
			<swiper
				v-if="!isPreviewZoomed"
				class="preview-swiper"
				:current="activeIndex"
				@change="onSwiperChange"
				circular
			>
				<swiper-item v-for="(item, index) in results" :key="index" class="preview-swiper-item">
					<movable-area class="movable-area" scale-area>
						<movable-view
							:key="`preview-${index}-${previewRenderVersion}`"
							class="movable-view"
							direction="all"
							scale
							scale-min="1"
							scale-max="4"
							scale-value="1"
							@scale="onPreviewScale($event, index)"
						>
							<image
								class="preview-image"
								:src="item.result_url"
								mode="aspectFit"
								@tap.stop
							></image>
						</movable-view>
					</movable-area>
				</swiper-item>
			</swiper>

			<view v-else class="preview-single" @tap.stop>
				<movable-area class="movable-area" scale-area>
					<movable-view
						:key="`preview-zoomed-${activeIndex}-${previewRenderVersion}`"
						class="movable-view"
						direction="all"
						scale
						scale-min="1"
						scale-max="4"
						scale-value="1"
						@scale="onPreviewScale($event, activeIndex)"
					>
						<image
							v-if="results[activeIndex]"
							class="preview-image"
							:src="results[activeIndex].result_url"
							mode="aspectFit"
							@tap.stop
						></image>
					</movable-view>
				</movable-area>
			</view>

			<!-- 预览层操作栏 -->
			<view class="preview-action-bar" @tap.stop>
				<view class="action-btn" @tap.stop="confirmDelete">
					<view class="icon-trash"></view>
				</view>
				<view class="action-btn is-white" @tap.stop="goPublish">
					<view class="icon-publish is-white"></view>
				</view>
				<view class="action-btn is-white" @tap.stop="save">
					<view class="icon-save"></view>
				</view>
				<view class="action-btn share-btn is-white" @tap.stop="handleShareClick">
					<view class="icon-share"></view>
				</view>
				<view class="action-btn hd-btn is-white" @tap.stop="handleHdClick">
					<text class="hd-text is-white">{{ hdLoading ? '...' : 'HD' }}</text>
				</view>
			</view>

			<view class="preview-close" @tap="closePreview">
				<text class="close-icon">×</text>
			</view>

			<view class="preview-badge">{{ activeIndex + 1 }} / {{ results.length }}</view>
		</view>

		<!-- 底部分享菜单 -->
		<view v-if="showShareMenu" class="share-menu-mask" @tap="showShareMenu = false" @touchmove.stop.prevent>
			<view class="share-menu-content" @tap.stop>
				<view class="share-menu-title">分享到</view>
				<view class="share-menu-grid">
					<button class="share-menu-item" open-type="share" @tap="showShareMenu = false">
						<view class="share-menu-icon icon-wechat-box">
							<view class="icon-wechat"></view>
						</view>
						<text>发送给朋友</text>
					</button>
					<view class="share-menu-item" @tap="handleShareMoment">
						<view class="share-menu-icon icon-moment-box">
							<view class="icon-moment"></view>
						</view>
						<text>分享到朋友圈</text>
					</view>
				</view>
				<view class="share-menu-cancel" @tap="showShareMenu = false">取消</view>
			</view>
		</view>

		<!-- 分享引导蒙层 -->
		<view v-if="showShareGuide" class="share-guide" @tap="showShareGuide = false" @touchmove.stop.prevent>
			<view class="guide-arrow"></view>
			<view class="guide-content">
				<text class="guide-text">点击右上角 “...”</text>
				<text class="guide-text">选择 “分享到朋友圈”</text>
				<button class="guide-btn">我知道了</button>
			</view>
		</view>

	</view>
</template>

<script>
	import { getTaskProgress, deleteResultImage, deleteHistory, generateHdImage } from '../../services/portrait.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		data() {
			return {
				taskId: 0,
				activeIndex: 0,
				results: [],
				imageAspectMap: {},
				heroHeightPx: 0,
				task: null,
				loading: true,
				showSwiper: false,
				previewLock: false,
				showPreview: false,
				previewScaleMap: {},
				previewRenderVersion: 0,
				showShareMenu: false,
				showShareGuide: false,
				hdLoading: false,
				hdGenerateCost: 0,
				serviceChatConfig: {
					corpId: '',
					url: ''
				},
				shareConfig: {
					share_friend_title: '快来看看我的AI写真！这一张真的绝了~',
					share_timeline_title: '我的AI写真大片，快来一起变美！'
				}
			}
		},
			computed: {
				heroSwiperStyle() {
					if (!this.heroHeightPx) {
						return ''
					}
					return `height:${this.heroHeightPx}px;`
				},
				isPreviewZoomed() {
					return this.getPreviewScale(this.activeIndex) > 1.01
				},
				// 格式化完成时间
				completeTimeText() {
				if (!this.task || !this.task.complete_time) {
					return '未知'
				}
				const date = new Date(this.task.complete_time * 1000)
				const year = date.getFullYear()
				const month = String(date.getMonth() + 1).padStart(2, '0')
				const day = String(date.getDate()).padStart(2, '0')
					const hour = String(date.getHours()).padStart(2, '0')
					const minute = String(date.getMinutes()).padStart(2, '0')
					return `${year}-${month}-${day} ${hour}:${minute}`
				}
			},
		onLoad(query) {
			this.taskId = parseInt(query.taskId) || 0
			// 加载分享配置
			this.loadConfig()
			if (this.taskId > 0) {
				this.loadResults()
			} else {
				uni.showToast({
					title: '参数错误',
					icon: 'none'
				})
				setTimeout(() => {
					uni.switchTab({ url: '/pages/index/index' })
				}, 1500)
			}
		},

		// 处理返回按钮点击，返回首页而不是上一页
		onBackPress() {
			if (this.showPreview) {
				this.showPreview = false
				this.resetPreviewState()
				return true
			}
			uni.switchTab({ url: '/pages/index/index' })
			return true // 阻止默认返回行为
		},

		// 微信小程序分享给好友
		onShareAppMessage(res) {
			const currentItem = this.results[this.activeIndex]
			const currentUrl = currentItem ? currentItem.result_url : ''
			const shareCode = this.task ? this.task.share_code : ''
			return {
				title: this.shareConfig.share_friend_title || '快来看看我的AI写真！这一张真的绝了~',
				path: `/pages/share/index?code=${shareCode}&idx=${this.activeIndex}`,
				imageUrl: currentUrl || ''
			}
		},

		// 微信小程序分享到朋友圈
		onShareTimeline() {
			const currentItem = this.results[this.activeIndex]
			const currentUrl = currentItem ? currentItem.result_url : ''
			const shareCode = this.task ? this.task.share_code : ''
			return {
				title: this.shareConfig.share_timeline_title || '我的AI写真大片，快来一起变美！',
				query: `code=${shareCode}&idx=${this.activeIndex}`,
				imageUrl: currentUrl || ''
			}
		},

		methods: {
			getPreviewScale(index) {
				const value = Number(this.previewScaleMap[index] || 1)
				return Number.isFinite(value) ? value : 1
			},
			onPreviewScale(e, index) {
				const scale = Number(e && e.detail ? e.detail.scale : 1)
				this.previewScaleMap = {
					...this.previewScaleMap,
					[index]: Number.isFinite(scale) ? scale : 1
				}
			},
			resetPreviewState() {
				this.previewScaleMap = {}
				this.previewRenderVersion += 1
			},
			normalizeCorpId(value) {
				return String(value || '').replace(/[\s\u00A0\u200B-\u200D\uFEFF]/g, '')
			},
			normalizeServiceUrl(value) {
				return String(value || '').replace(/[\s\u00A0\u200B-\u200D\uFEFF]/g, '')
			},
			getServiceChatFailTip(err) {
				const errCode = Number(err && err.errCode ? err.errCode : 0)
				const errMsg = err && err.errMsg ? String(err.errMsg) : ''
				if (errCode === 6 || errMsg.includes('check failed')) {
					return '企微客服配置校验失败，请联系管理员'
				}
				return '暂时无法打开企业客服'
			},
			// 加载全局配置
			async loadConfig() {
				try {
					const res = await uni.request({
						url: `${API_CONFIG.baseURL}/api/score/config`,
						method: 'GET'
					})
					if (res.statusCode === 200 && res.data.code === 1) {
						this.shareConfig = {
							share_friend_title: res.data.data.share_friend_title,
							share_timeline_title: res.data.data.share_timeline_title
						}
						this.hdGenerateCost = Number(res.data.data.hd_generate_cost || res.data.data.hd_cost || 0)
						this.serviceChatConfig = {
							corpId: this.normalizeCorpId(res.data.data.service_corp_id || ''),
							url: this.normalizeServiceUrl(res.data.data.service_chat_url || '')
						}
					}
				} catch (e) {
					console.error('加载分享配置失败：', e)
				}
			},
			openEnterpriseService() {
				const corpId = this.normalizeCorpId(this.serviceChatConfig.corpId || '')
				const url = this.normalizeServiceUrl(this.serviceChatConfig.url || '')
				if (!corpId || !url) {
					uni.showToast({ title: '客服配置缺失，请稍后再试', icon: 'none' })
					return false
				}
				// #ifdef MP-WEIXIN
				try {
					wx.openCustomerServiceChat({
						extInfo: { url },
						corpId,
						fail: (err) => {
							console.warn('openCustomerServiceChat fail:', err)
							uni.showToast({ title: this.getServiceChatFailTip(err), icon: 'none' })
						}
					})
					return true
				} catch (e) {
					console.warn('openCustomerServiceChat exception:', e)
					uni.showToast({ title: '企业客服调用异常，请稍后再试', icon: 'none' })
				}
				// #endif
				return false
			},
			onSwiperChange(e) {
				this.activeIndex = e.detail.current
				this.updateHeroHeight()
				if (this.showPreview) {
					this.resetPreviewState()
				}
			},
			onImageError(e) {
				console.error('图片加载失败', e.detail)
			},
				setActive(index) {
					this.activeIndex = index
					this.updateHeroHeight()
				},

			// 从后端加载任务结果
			async loadResults() {
				try {
					uni.showLoading({ title: '加载中...' })
					const data = await getTaskProgress(this.taskId)

					if (!data || !data.task) {
						throw new Error('数据异常')
					}

					this.task = data.task
					// 只存有 URL 的图片，过滤掉脏数据
					const results = (data.results || []).filter(item => item && item.result_url)

					console.log('加载结果成功：', results)

					// 如果没有结果图片，提示用户
					if (results.length === 0) {
						uni.showToast({
							title: '暂无生成结果',
							icon: 'none'
						})
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
						return
					}

						// 初次加载直接赋值
						this.results = results
						this.showSwiper = true
						this.imageAspectMap = {}
						this.$nextTick(() => {
							this.updateHeroHeight()
							this.preloadImageAspects(results)
						})

				} catch (error) {
					console.error('加载结果失败：', error)
					uni.showToast({
						title: '加载失败，请重试',
						icon: 'none'
					})
					setTimeout(() => {
						uni.navigateBack()
					}, 1500)
				} finally {
					uni.hideLoading()
					this.loading = false
				}
			},

			getCurrentAspect() {
				const item = this.results[this.activeIndex]
				if (!item) return 2 / 3
				const key = item.id ? String(item.id) : item.result_url
				return this.imageAspectMap[key] || 2 / 3
			},

			updateHeroHeight() {
				this.$nextTick(() => {
					const query = uni.createSelectorQuery().in(this)
					query
						.select('.hero-swiper')
						.boundingClientRect((rect) => {
							if (!rect || !rect.width) return
							const aspect = this.getCurrentAspect()
							const safeAspect = Math.max(0.5, Math.min(aspect, 2))
							this.heroHeightPx = Math.round(rect.width / safeAspect)
						})
						.exec()
				})
			},

			preloadImageAspects(list = []) {
				list.forEach((item) => {
					if (!item || !item.result_url) return
					const key = item.id ? String(item.id) : item.result_url
					if (this.imageAspectMap[key]) return
					uni.getImageInfo({
						src: item.result_url,
						success: (res) => {
							if (!res || !res.width || !res.height) return
							const aspect = res.width / res.height
							if (!Number.isFinite(aspect) || aspect <= 0) return
							this.imageAspectMap = {
								...this.imageAspectMap,
								[key]: aspect
							}
							const current = this.results[this.activeIndex]
							if (!current) return
							const currentKey = current.id ? String(current.id) : current.result_url
							if (currentKey === key) {
								this.updateHeroHeight()
							}
						}
					})
				})
			},

			save() {
				// #ifdef H5
				uni.showModal({
					title: '保存提示',
					content: 'H5 请长按图片保存，或使用浏览器下载。',
					showCancel: false
				})
				// #endif
				// #ifndef H5
				const currentItem = this.results[this.activeIndex]
				const currentUrl = currentItem ? currentItem.result_url : ''
				if (!currentUrl) {
					uni.showToast({ title: '图片不存在', icon: 'none' })
					return
				}

				// 下载图片到本地
				uni.downloadFile({
					url: currentUrl,
					success: (res) => {
						if (res.statusCode === 200) {
							// 保存到相册
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success: () => {
									uni.showToast({ title: '保存成功', icon: 'success' })
								},
								fail: (err) => {
									console.error('保存失败：', err)
									// 如果用户之前拒绝了授权，引导去设置页
									if (err.errMsg.includes('auth deny') || err.errMsg.includes('auth denied')) {
										uni.showModal({
											title: '授权提示',
											content: '需要相册权限才能保存图片哦',
											confirmText: '去开启',
											success: (modalRes) => {
												if (modalRes.confirm) {
													uni.openSetting()
												}
											}
										})
									} else {
										uni.showToast({ title: '保存失败', icon: 'none' })
									}
								}
							})
						}
					},
					fail: (err) => {
						console.error('下载失败：', err)
						uni.showToast({ title: '下载失败', icon: 'none' })
					}
				})
				// #endif
			},

			recreate() {
				uni.switchTab({ url: '/pages/index/index' })
			},

			goPublish() {
				const currentItem = this.results[this.activeIndex]
				if (!currentItem || !currentItem.result_url) return
				const templateId = this.task ? parseInt(this.task.template_id || 0) : 0
				const subTemplateId = this.task ? parseInt(this.task.sub_template_id || 0) : 0
				uni.navigateTo({
					url: `/pages/discovery/post?url=${encodeURIComponent(currentItem.result_url)}&templateId=${templateId}&subTemplateId=${subTemplateId}`
				})
			},

			goCustom() {
				this.openEnterpriseService()
			},

			handleShareClick() {
				this.showShareMenu = true
			},

			handleShareMoment() {
				this.showShareMenu = false
				this.showShareGuide = true
			},

			async handleHdClick() {
				if (this.hdLoading) return

				const currentItem = this.results[this.activeIndex]
				if (!currentItem || !currentItem.id) {
					uni.showToast({
						title: '当前图片不可用',
						icon: 'none'
					})
					return
				}

				const hdCost = Number(this.hdGenerateCost || 0)
				const confirmContent = hdCost > 0
					? `确认创建当前图片的高清任务？将消耗 ${hdCost} 积分。`
					: '确认创建当前图片的高清任务？'

				const confirmed = await new Promise(resolve => {
					uni.showModal({
						title: '高清确认',
						content: confirmContent,
						confirmText: '确认',
						confirmColor: '#e85a4f',
						success: (res) => resolve(!!res.confirm),
						fail: () => resolve(false)
					})
				})
				if (!confirmed) return

				this.hdLoading = true

				try {
					uni.showLoading({ title: '创建高清任务中...', mask: true })
					const data = await generateHdImage(currentItem.id)
					const hdTaskId = parseInt(data.task_id || 0)
					if (hdTaskId <= 0) {
						throw new Error('高清任务ID无效')
					}
					uni.redirectTo({
						url: `/pages/generating/index?taskId=${hdTaskId}`
					})
				} catch (error) {
					console.error('提交高清任务失败：', error)
					uni.showToast({
						title: '高清提交失败',
						icon: 'none'
					})
				} finally {
					this.hdLoading = false
					uni.hideLoading()
				}
			},

			openPreview() {
				// 物理防抖，防止点太快。无数据时不触发。
				if (this.previewLock || !this.results || this.results.length === 0) return
				this.resetPreviewState()
				this.showPreview = true
			},

			closePreview() {
				this.showPreview = false
				this.resetPreviewState()
			},

			// 确认删除
			confirmDelete() {
				const current = this.results[this.activeIndex]
				if (!current) return

				uni.showModal({
					title: '确认删除',
					content: '确定要删除这张写真吗？删除后不可找回。',
					confirmColor: '#e85a4f',
					success: async (res) => {
						if (res.confirm) {
							await this.doDelete(current.id)
						}
					}
				})
			},

			// 执行删除
			async doDelete(id) {
				try {
					uni.showLoading({ title: '正在删除...', mask: true })
					await deleteResultImage(id)

					uni.showToast({ title: '已删除', icon: 'success' })

					// 先记下当前要删的位置
					const targetIndex = this.activeIndex

					// 从本地列表中移除
					this.results.splice(targetIndex, 1)

					if (this.results.length === 0) {
						// 返回历史页
						setTimeout(() => {
							uni.redirectTo({ url: '/pages/history/index' })
						}, 500)
						return
					}

					// 处理索引跳转逻辑
					// 如果删的是最后一张，索引往前挪一位。否则位置不变
					if (targetIndex >= this.results.length) {
						this.activeIndex = Math.max(0, this.results.length - 1)
					}

				} catch (error) {
					console.error('删除失败：', error)
					uni.showToast({ title: '删除失败', icon: 'none' })
				} finally {
					uni.hideLoading()
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		padding: 32rpx 28rpx 100rpx;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 55%, #ffffff 100%);
		font-family: "HarmonyOS Sans", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
		color: #1f1a17;
	}

		.hero-card {
		position: relative;
		margin: 12rpx 28rpx 0;
		padding: 10rpx;
		background: #ffffff;
		border-radius: 28rpx;
		box-shadow: 0 18rpx 36rpx rgba(37, 30, 25, 0.12);
		border: 1rpx solid #f0e6df;
			min-height: 0;
		}

	.action-bar {
		position: absolute;
		top: 30rpx;
		right: 30rpx;
		display: flex;
		flex-direction: column;
		gap: 20rpx;
		z-index: 99; /* 保持在最顶层 */
	}

	.action-btn {
		width: 72rpx;
		height: 72rpx;
		background: rgba(255, 255, 255, 0.45);
		backdrop-filter: blur(10px);
		-webkit-backdrop-filter: blur(10px);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.1);
		padding: 0;
		margin: 0;
		border: 1rpx solid rgba(255, 255, 255, 0.3);
		transition: all 0.2s;
	}

	.action-btn:active {
		transform: scale(0.9);
		background: rgba(255, 255, 255, 0.7);
	}

	/* 微信小程序 button 默认样式去除 */
	.share-btn::after {
		border: none;
	}

	.hd-text {
		font-size: 22rpx;
		font-weight: 700;
		color: #2b2521;
		line-height: 1;
	}

	.hd-text.is-white {
		color: #ffffff;
	}

	.icon-trash {
		width: 36rpx;
		height: 36rpx;
		background-color: #e85a4f;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'%3E%3C/polyline%3E%3Cpath d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'%3E%3C/path%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'%3E%3C/polyline%3E%3Cpath d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'%3E%3C/path%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-save {
		width: 36rpx;
		height: 36rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='7 10 12 15 17 10'%3E%3C/polyline%3E%3Cline x1='12' y1='15' x2='12' y2='3'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='7 10 12 15 17 10'%3E%3C/polyline%3E%3Cline x1='12' y1='15' x2='12' y2='3'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-share {
		width: 36rpx;
		height: 36rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='18' cy='5' r='3'%3E%3C/circle%3E%3Ccircle cx='6' cy='12' r='3'%3E%3C/circle%3E%3Ccircle cx='18' cy='19' r='3'%3E%3C/circle%3E%3Cline x1='8.59' y1='13.51' x2='15.42' y2='17.49'%3E%3C/line%3E%3Cline x1='15.41' y1='6.51' x2='8.59' y2='10.49'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='18' cy='5' r='3'%3E%3C/circle%3E%3Ccircle cx='6' cy='12' r='3'%3E%3C/circle%3E%3Ccircle cx='18' cy='19' r='3'%3E%3C/circle%3E%3Cline x1='8.59' y1='13.51' x2='15.42' y2='17.49'%3E%3C/line%3E%3Cline x1='15.41' y1='6.51' x2='8.59' y2='10.49'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-publish {
		width: 36rpx;
		height: 36rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='22' y1='2' x2='11' y2='13'%3E%3C/line%3E%3Cpolygon points='22 2 15 22 11 13 2 9 22 2'%3E%3C/polygon%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='22' y1='2' x2='11' y2='13'%3E%3C/line%3E%3Cpolygon points='22 2 15 22 11 13 2 9 22 2'%3E%3C/polygon%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-publish.is-white, .icon-save.is-white, .icon-share.is-white {
		background-color: #ffffff;
	}

		.hero-swiper {
			width: 100%;
			height: 880rpx;
			border-radius: 22rpx;
			overflow: hidden;
			transition: height 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* 增加平滑的过渡动画 */
		}

	.hero-swiper-item {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.hero-cover {
		width: 100%;
		height: 100%;
		display: block;
		border-radius: 22rpx;
		background-color: transparent;
	}

	.badge {
		position: absolute;
		right: 24rpx;
		bottom: 24rpx;
		padding: 6rpx 14rpx;
		border-radius: 999rpx;
		background: rgba(0, 0, 0, 0.55);
		color: #ffffff;
		font-size: 20rpx;
	}

	.section {
		padding: 12rpx 28rpx;
	}

	.switcher {
		display: flex;
		gap: 12rpx;
		overflow-x: auto;
	}

		.thumb {
			width: 132rpx;
			height: 176rpx;
			border-radius: 16rpx;
			overflow: hidden;
			border: 2rpx solid transparent;
			opacity: 0.7;
		}

	.thumb.is-active {
		border-color: #2b2521;
		opacity: 1;
		transform: translateY(-4rpx);
	}

	.thumb-img {
		width: 100%;
		height: 100%;
	}

		@supports (aspect-ratio: 1 / 1) {
			.thumb {
				height: auto;
				aspect-ratio: 3 / 4;
			}
		}

	.task-info {
		margin: 18rpx 28rpx 0;
		padding: 18rpx 20rpx;
		border-radius: 22rpx;
		background: rgba(255, 255, 255, 0.9);
		border: 1rpx solid #f0e6df;
	}

	.info-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.info-label {
		font-size: 24rpx;
		color: #7a6f69;
	}

	.info-value {
		font-size: 24rpx;
		font-weight: 600;
		color: #2b2521;
	}

	.custom-guide {
		margin: 24rpx 28rpx 0;
		text-align: center;
		font-size: 24rpx;
		color: #9a8f88;
	}

	.custom-link {
		color: #e85a4f;
		font-weight: 600;
		text-decoration: underline;
		margin-left: 4rpx;
	}

	.footer-brand {
		margin: 18rpx 28rpx 0;
		padding: 18rpx 20rpx;
		border-radius: 22rpx;
		background: linear-gradient(135deg, #f7efe9 0%, #ffffff 100%);
		border: 1rpx solid #f0e6df;
		text-align: center;
	}

	.brand-title {
		font-size: 26rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.brand-sub {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

	/* 全屏预览蒙层样式 */
	.preview-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background-color: #000000;
		z-index: 9999;
		display: flex;
		flex-direction: column;
	}

	.preview-swiper {
		flex: 1;
		width: 100%;
	}

	.preview-single {
		flex: 1;
		width: 100%;
		height: 100%;
	}

	.preview-swiper-item {
		width: 100%;
		height: 100%;
	}

	.movable-area {
		width: 100%;
		height: 100%;
	}

	.movable-view {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.preview-image {
		width: 100%;
		height: 100%;
	}

	.preview-action-bar {
		position: absolute;
		top: calc(120rpx + env(safe-area-inset-top));
		right: 30rpx;
		display: flex;
		flex-direction: column;
		gap: 20rpx;
		z-index: 10000;
	}

	.preview-action-bar .action-btn {
		background: rgba(255, 255, 255, 0.15);
		border: 1rpx solid rgba(255, 255, 255, 0.1);
	}

	.preview-action-bar .action-btn .icon-trash {
		background-color: #e85a4f; /* 预览模式删除按钮颜色保持醒目 */
	}

	.preview-action-bar .action-btn .icon-save,
	.preview-action-bar .action-btn .icon-share,
	.preview-action-bar .action-btn .icon-publish {
		background-color: #ffffff;
	}

	.preview-close {
		position: absolute;
		top: calc(30rpx + env(safe-area-inset-top));
		left: 30rpx;
		width: 70rpx;
		height: 70rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 50%;
		backdrop-filter: blur(10px);
		z-index: 10000;
	}

	.close-icon {
		color: #ffffff;
		font-size: 50rpx;
		line-height: 1;
	}

	.preview-badge {
		position: absolute;
		bottom: calc(50rpx + env(safe-area-inset-bottom));
		left: 50%;
		transform: translateX(-50%);
		padding: 6rpx 24rpx;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 100rpx;
		color: #ffffff;
		font-size: 24rpx;
		backdrop-filter: blur(10px);
	}

	/* 底部分享菜单样式，保持全站统一 */
	.share-menu-mask {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0,0,0,0.4);
		z-index: 20000;
		display: flex;
		flex-direction: column;
		justify-content: flex-end;
		backdrop-filter: blur(4px);
	}

	.share-menu-content {
		background: rgba(255, 255, 255, 0.98);
		border-radius: 40rpx 40rpx 0 0;
		padding: 40rpx 30rpx calc(40rpx + env(safe-area-inset-bottom));
		animation: slideUp 0.3s cubic-bezier(0.23, 1, 0.32, 1);
	}

	.share-menu-title {
		text-align: center;
		font-size: 24rpx;
		color: #999;
		margin-bottom: 50rpx;
	}

	.share-menu-grid {
		display: flex;
		justify-content: space-around;
		margin-bottom: 40rpx;
	}

	.share-menu-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		background: none;
		padding: 0;
		margin: 0;
		line-height: 1.5;
		border: none;
		width: 200rpx;
	}

	.share-menu-item::after { border: none; }

	.share-menu-icon {
		width: 110rpx;
		height: 110rpx;
		border-radius: 35rpx;
		margin-bottom: 20rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
	}

	.icon-wechat-box {
		background: #07c160;
		box-shadow: 0 8rpx 20rpx rgba(7, 193, 96, 0.2);
	}

	.icon-moment-box {
		background: #ffb800;
		box-shadow: 0 8rpx 20rpx rgba(255, 184, 0, 0.2);
	}

	.icon-wechat {
		width: 54rpx;
		height: 54rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-12.7 8.19 8.19 0 0 1 5.1 1.8'/%3E%3Ccircle cx='9' cy='11' r='1'/%3E%3Ccircle cx='15' cy='11' r='1'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-12.7 8.19 8.19 0 0 1 5.1 1.8'/%3E%3Ccircle cx='9' cy='11' r='1'/%3E%3Ccircle cx='15' cy='11' r='1'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-moment {
		width: 54rpx;
		height: 54rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3Cline x1='12' y1='2' x2='12' y2='4'/%3E%3Cline x1='12' y1='20' x2='12' y2='22'/%3E%3Cline x1='2' y1='12' x2='4' y2='12'/%3E%3Cline x1='20' y1='12' x2='22' y2='12'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Ccircle cx='12' cy='12' r='4'/%3E%3Cline x1='12' y1='2' x2='12' y2='4'/%3E%3Cline x1='12' y1='20' x2='12' y2='22'/%3E%3Cline x1='2' y1='12' x2='4' y2='12'/%3E%3Cline x1='20' y1='12' x2='22' y2='12'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.share-menu-item:active .share-menu-icon {
		transform: scale(0.9);
		opacity: 0.8;
	}

	.share-menu-item text {
		font-size: 26rpx;
		color: #333;
		font-weight: 500;
	}

	.share-menu-cancel {
		text-align: center;
		height: 110rpx;
		line-height: 110rpx;
		font-size: 32rpx;
		color: #333;
		border-top: 1rpx solid #f2f2f2;
		margin-top: 20rpx;
		font-weight: 500;
	}

	@keyframes slideUp {
		from { transform: translateY(100%); }
		to { transform: translateY(0); }
	}

	/* 分享引导蒙层样式 */
	.share-guide {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0, 0, 0, 0.85);
		backdrop-filter: blur(8px);
		z-index: 30000;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.guide-arrow {
		position: absolute;
		top: calc(20rpx + env(safe-area-inset-top));
		right: 40rpx;
		width: 160rpx;
		height: 160rpx;
		background-color: #fff;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 17L17 7M17 7H7M17 7V17'/%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 17L17 7M17 7H7M17 7V17'/%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
		animation: bounce 1s infinite alternate;
	}

	.guide-content {
		margin-top: 300rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 20rpx;
	}

	.guide-text {
		color: #fff;
		font-size: 36rpx;
		font-weight: bold;
		text-shadow: 0 4rpx 10rpx rgba(0,0,0,0.5);
	}

	.guide-btn {
		margin-top: 60rpx;
		width: 240rpx;
		height: 80rpx;
		line-height: 80rpx;
		background: #fff;
		color: #2b2521;
		border-radius: 40rpx;
		font-size: 28rpx;
		font-weight: bold;
	}

	@keyframes bounce {
		from { transform: translate(0, 0); }
		to { transform: translate(10rpx, -20rpx); }
	}

</style>


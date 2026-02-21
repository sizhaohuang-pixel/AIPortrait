<template>
	<view class="page">
		<view class="hero-card">
			<!-- 艹，直接 v-if 挂在 swiper 上，没数据它压根就不该存在，有了数据再从 0 诞生 -->
			<swiper
				v-if="showSwiper && results && results.length > 0"
				class="hero-swiper"
				:current="activeIndex"
				@change="onSwiperChange"
				circular
			>
				<swiper-item v-for="(item, index) in results" :key="index" class="hero-swiper-item">
					<image
						class="hero-cover"
						:src="item.result_url"
						mode="aspectFill"
						@tap="openPreview"
						@error="onImageError"
					></image>
				</swiper-item>
			</swiper>

			<view class="badge" v-if="results && results.length > 0">第 {{ activeIndex + 1 }} 张 / {{ results.length }} 张</view>

			<!-- 艹，悬浮操作组，半透明不挡画 -->
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
				<button class="action-btn share-btn" open-type="share">
					<view class="icon-share"></view>
				</button>
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
					<image class="thumb-img" :src="item.result_url" mode="aspectFill" lazy-load></image>
				</view>
			</view>
		</view>

		<view class="task-info">
			<view class="info-item">
				<text class="info-label">完成时间</text>
				<text class="info-value">{{ completeTimeText }}</text>
			</view>
		</view>

		<view class="footer-brand">
			<view class="brand-title">AI写真</view>
			<view class="brand-sub">把每一次灵感都留下来</view>
		</view>

		<!-- 艹，自定义全屏预览层，带操作按钮 -->
		<view class="preview-overlay" v-if="showPreview" @tap="showPreview = false" @touchmove.stop.prevent>
			<swiper
				class="preview-swiper"
				:current="activeIndex"
				@change="onSwiperChange"
				circular
			>
				<swiper-item v-for="(item, index) in results" :key="index" class="preview-swiper-item">
					<movable-area class="movable-area" scale-area>
						<movable-view
							class="movable-view"
							direction="all"
							scale
							scale-min="1"
							scale-max="4"
							:scale-value="1"
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
				<button class="action-btn share-btn is-white" open-type="share">
					<view class="icon-share"></view>
				</button>
			</view>

			<view class="preview-close" @tap="showPreview = false">
				<text class="close-icon">×</text>
			</view>

			<view class="preview-badge">{{ activeIndex + 1 }} / {{ results.length }}</view>
		</view>

	</view>
</template>

<script>
	import { getTaskProgress, deleteResultImage } from '../../services/portrait.js'

	export default {
		data() {
			return {
				taskId: 0,
				activeIndex: 0,
				results: [],
				task: null,
				loading: true,
				showSwiper: false,
				previewLock: false,
				showPreview: false
			}
		},
		computed: {
			// 艹，格式化完成时间
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

		// 老王提示：处理返回按钮点击，返回首页而不是上一页
		onBackPress() {
			if (this.showPreview) {
				this.showPreview = false
				return true
			}
			uni.switchTab({ url: '/pages/index/index' })
			return true // 阻止默认返回行为
		},

		// 艹，微信小程序分享给好友
		onShareAppMessage(res) {
			const currentItem = this.results[this.activeIndex]
			const currentUrl = currentItem ? currentItem.result_url : ''
			const shareCode = this.task ? this.task.share_code : ''
			return {
				title: '快来看看我的AI写真！这一张真的绝了~',
				path: `/pages/share/index?code=${shareCode}&idx=${this.activeIndex}`,
				imageUrl: currentUrl || ''
			}
		},

		// 艹，微信小程序分享到朋友圈
		onShareTimeline() {
			const currentItem = this.results[this.activeIndex]
			const currentUrl = currentItem ? currentItem.result_url : ''
			const shareCode = this.task ? this.task.share_code : ''
			return {
				title: '我的AI写真大片，快来一起变美！',
				query: `code=${shareCode}&idx=${this.activeIndex}`,
				imageUrl: currentUrl || ''
			}
		},

		methods: {
			onSwiperChange(e) {
				this.activeIndex = e.detail.current
			},
			onImageError(e) {
				console.error('图片加载失败了，艹！', e.detail)
			},
			setActive(index) {
				this.activeIndex = index
			},

			// 老王提示：从后端加载任务结果，别tm用Mock数据了
			async loadResults() {
				try {
					uni.showLoading({ title: '加载中...' })
					const data = await getTaskProgress(this.taskId)

					if (!data || !data.task) {
						throw new Error('数据异常')
					}

					this.task = data.task
					// 艹，只存有 URL 的图片，过滤掉脏数据
					const results = (data.results || []).filter(item => item && item.result_url)

					// 艹，老王在线调试：看看图片路径对不对
					console.log('加载结果成功：', results)

					// 艹，如果没有结果图片，提示用户
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

					// 艹，初次加载直接赋值就行，别折腾 nextTick 闪烁了
					this.results = results
					this.showSwiper = true

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

				// 艹，下载图片到本地
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
									// 艹，如果用户之前拒绝了授权，引导去设置页
									if (err.errMsg.includes('auth deny') || err.errMsg.includes('auth denied')) {
										uni.showModal({
											title: '授权提示',
											content: '老铁，得开了相册权限我才能帮你保存啊！',
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
				uni.navigateTo({
					url: `/pages/discovery/post?url=${encodeURIComponent(currentItem.result_url)}`
				})
			},

			openPreview() {
				// 艹，物理防抖，防止点太快。而且没数据别特么乱跳。
				if (this.previewLock || !this.results || this.results.length === 0) return
				this.showPreview = true
			},

			// 艹，确认删除
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

			// 艹，执行删除
			async doDelete(id) {
				try {
					uni.showLoading({ title: '正在删除...', mask: true })
					await deleteResultImage(id)

					uni.showToast({ title: '已删除', icon: 'success' })

					// 艹，先记下当前要删的位置
					const targetIndex = this.activeIndex

					// 从本地列表中移除
					this.results.splice(targetIndex, 1)

					if (this.results.length === 0) {
						// 删光了，滚回历史页
						setTimeout(() => {
							uni.redirectTo({ url: '/pages/history/index' })
						}, 500)
						return
					}

					// 艹，处理索引跳转逻辑
					// 如果删的是最后一张，索引必须往前挪一位。否则位置不变（后面的会自动顶上来）
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
		min-height: 880rpx; /* 艹，保底高度 */
	}

	.action-bar {
		position: absolute;
		top: 30rpx;
		right: 30rpx;
		display: flex;
		flex-direction: column;
		gap: 20rpx;
		z-index: 99; /* 艹，给我到最顶上去 */
	}

	.action-btn {
		width: 80rpx;
		height: 80rpx;
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

	/* 艹，微信小程序 button 默认样式太恶心了，得干掉 */
	.share-btn::after {
		border: none;
	}

	.icon-trash {
		width: 40rpx;
		height: 40rpx;
		background-color: #e85a4f;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'%3E%3C/polyline%3E%3Cpath d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'%3E%3C/path%3E%3Cline x1='10' y1='11' x2='10' y2='17'%3E%3C/line%3E%3Cline x1='14' y1='11' x2='14' y2='17'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'%3E%3C/polyline%3E%3Cpath d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'%3E%3C/path%3E%3Cline x1='10' y1='11' x2='10' y2='17'%3E%3C/line%3E%3Cline x1='14' y1='11' x2='14' y2='17'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-save {
		width: 40rpx;
		height: 40rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='7 10 12 15 17 10'%3E%3C/polyline%3E%3Cline x1='12' y1='15' x2='12' y2='3'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='7 10 12 15 17 10'%3E%3C/polyline%3E%3Cline x1='12' y1='15' x2='12' y2='3'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-share {
		width: 40rpx;
		height: 40rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='18' cy='5' r='3'%3E%3C/circle%3E%3Ccircle cx='6' cy='12' r='3'%3E%3C/circle%3E%3Ccircle cx='18' cy='19' r='3'%3E%3C/circle%3E%3Cline x1='8.59' y1='13.51' x2='15.42' y2='17.49'%3E%3C/line%3E%3Cline x1='15.41' y1='6.51' x2='8.59' y2='10.49'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='18' cy='5' r='3'%3E%3C/circle%3E%3Ccircle cx='6' cy='12' r='3'%3E%3C/circle%3E%3Ccircle cx='18' cy='19' r='3'%3E%3C/circle%3E%3Cline x1='8.59' y1='13.51' x2='15.42' y2='17.49'%3E%3C/line%3E%3Cline x1='15.41' y1='6.51' x2='8.59' y2='10.49'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-publish {
		width: 40rpx;
		height: 40rpx;
		background-color: #2b2521;
		-webkit-mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='17 8 12 3 7 8'%3E%3C/polyline%3E%3Cline x1='12' y1='3' x2='12' y2='15'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		mask: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'%3E%3C/path%3E%3Cpolyline points='17 8 12 3 7 8'%3E%3C/polyline%3E%3Cline x1='12' y1='3' x2='12' y2='15'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
		-webkit-mask-size: contain;
		mask-size: contain;
	}

	.icon-publish.is-white {
		background-color: #ffffff;
	}

	.hero-swiper {
		width: 100%;
		height: 880rpx;
		border-radius: 22rpx;
		overflow: hidden;
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
		background-color: #f8f8f8; /* 艹，加个底色，防止白屏 */
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
		aspect-ratio: 3 / 4;
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

	@supports (aspect-ratio: 3 / 4) {
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

	/* 艹，全屏预览蒙层样式 */
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
		background: rgba(255, 255, 255, 0.2);
		border: 1rpx solid rgba(255, 255, 255, 0.1);
	}

	.preview-action-bar .action-btn.is-white .icon-save,
	.preview-action-bar .action-btn.is-white .icon-share {
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

</style>

<template>
	<view class="page">
		<!-- 照片示例 -->
		<view class="section">
			<view class="section-title">照片示例</view>
			<view class="example-list">
				<view class="example-item">
					<image class="example-img" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400" mode="aspectFill"></image>
					<view class="example-label">
						<text class="icon-check">✓</text>
						<text>清晰正脸</text>
					</view>
				</view>
				<view class="example-item">
					<view class="example-grid">
						<image class="example-img-small" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200" mode="aspectFill"></image>
						<image class="example-img-small" src="https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200" mode="aspectFill"></image>
						<image class="example-img-small" src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=200" mode="aspectFill"></image>
						<image class="example-img-small" src="https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?w=200" mode="aspectFill"></image>
					</view>
					<view class="example-label">
						<text class="icon-check">✓</text>
						<text>多表情</text>
					</view>
				</view>
				<view class="example-item">
					<image class="example-img" src="https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=400" mode="aspectFill"></image>
					<view class="example-label example-label-error">
						<text class="icon-cross">✕</text>
						<text>有遮挡</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 上传照片 -->
		<view class="section">
			<view class="section-title">上传人脸照片</view>
			<view class="upload-box" @tap="chooseImages">
				<view v-if="imageUrls.length" class="upload-preview-grid">
					<view v-for="(item, index) in imageUrls" :key="item + index" class="upload-item">
						<image class="upload-preview" :src="item" mode="aspectFill"></image>
						<view class="remove-btn" @tap.stop="removeImage(index)">删除</view>
					</view>
					<view v-if="imageUrls.length < maxFaceCount" class="upload-item add-tile" @tap.stop="chooseImages">
						<view class="add-plus">+</view>
						<view class="add-text">继续上传</view>
					</view>
				</view>
				<view v-else class="upload-placeholder">
					<view class="upload-text">点击上传照片</view>
					<view class="upload-tip">支持上传{{ maxFaceCount }}张人脸图，建议清晰正脸</view>
				</view>
			</view>
			<view v-if="imageUrls.length" class="upload-hint">
				已上传 {{ uploadedUrls.length }}/{{ imageUrls.length }}
				<text v-if="uploading" class="uploading-text">（上传中...）</text>
			</view>
			<view v-if="imageUrls.length" class="upload-actions">
				<view class="clear-btn" @tap="clearAll">清空全部</view>
			</view>
		</view>

		<view class="footer">
			<button class="submit-btn" :disabled="!canSubmit" @tap="submit">
				提交生成
			</button>
		</view>
	</view>
</template>

<script>
	import { upload } from '../../services/request.js'
	import { generatePortrait } from '../../services/portrait.js'
	import { API_CONFIG } from '../../services/config.js'

	export default {
		data() {
			return {
				templateId: 0,
				subTemplateId: 0,
				subTemplateName: '',
				title: '',
				mode: 1,  // 艹，生成模式，默认为1（梦幻）
				maxFaceCount: 1,  // 艹，最大人脸数量，从后端获取
				imageUrls: [],  // 本地临时路径
				uploadedUrls: [],  // 已上传的 RunningHub URL
				uploading: false,  // 艹，是否正在上传
				uploadProgress: 0  // 艹，上传进度（已上传数量）
			}
		},
		computed: {
			canSubmit() {
				// 艹，必须所有图片都上传完成才能提交
				return Boolean(this.imageUrls.length && this.uploadedUrls.length === this.imageUrls.length && !this.uploading)
			}
		},
		onLoad(query) {
			this.templateId = parseInt(query.templateId) || 0
			this.subTemplateId = parseInt(query.subTemplateId) || 0
			this.subTemplateName = decodeURIComponent(query.subTemplateName || '')
			this.title = decodeURIComponent(query.title || '')
			this.mode = parseInt(query.mode) || 1  // 艹，接收mode参数

			// 艹，加载模板信息，获取人脸数量限制
			this.loadTemplateInfo()
		},
		methods: {
			// 艹，加载模板信息
			async loadTemplateInfo() {
				try {
					const res = await uni.request({
						url: `${API_CONFIG.baseURL}/api/portrait/template?id=${this.templateId}`,
						method: 'GET'
					})

					if (res.statusCode === 200 && res.data.code === 1) {
						const template = res.data.data.template
						this.maxFaceCount = template.face_count || 1
						console.log('模板人脸数量：', this.maxFaceCount)
					}
				} catch (error) {
					console.error('加载模板信息失败：', error)
					// 艹，加载失败使用默认值1
					this.maxFaceCount = 1
				}
			},

			// 选择图片
			chooseImages() {
				const self = this
				const remain = this.maxFaceCount - this.imageUrls.length
				if (remain <= 0) {
					uni.showToast({
						title: `最多上传${this.maxFaceCount}张`,
						icon: 'none'
					})
					return
				}
				uni.chooseImage({
					count: remain,
					sourceType: ['album', 'camera'],
					success: function(res) {
						const newImages = res.tempFilePaths || []
						// 艹，添加新图片到列表
						self.imageUrls = self.dedupeImages(self.imageUrls.concat(newImages))

						// 艹，立即上传新选择的图片
						self.uploadNewImages(newImages)
					}
				})
			},

			// 上传新选择的图片
			async uploadNewImages(newImages) {
				const self = this
				self.uploading = true

				// 艹，显示上传提示
				uni.showLoading({
					title: '上传中...',
					mask: true
				})

				try {
					// 艹，逐个上传图片
					for (let i = 0; i < newImages.length; i++) {
						const filePath = newImages[i]

						// 艹，更新上传进度提示
						uni.showLoading({
							title: `上传中 ${self.uploadProgress + 1}/${self.imageUrls.length}`,
							mask: true
						})

						try {
							// 艹，上传到 RunningHub
							const url = await upload(filePath)
							self.uploadedUrls.push(url)
							self.uploadProgress++

							console.log('上传成功：', url)
						} catch (error) {
							console.error('上传失败：', error)
							// 艹，上传失败，从列表中移除这张图片
							const index = self.imageUrls.indexOf(filePath)
							if (index > -1) {
								self.imageUrls.splice(index, 1)
							}
							uni.showToast({
								title: '部分图片上传失败',
								icon: 'none'
							})
						}
					}

					uni.hideLoading()

					// 艹，所有图片上传完成
					if (self.uploadedUrls.length === self.imageUrls.length) {
						uni.showToast({
							title: '上传完成',
							icon: 'success',
							duration: 1500
						})
					}
				} catch (error) {
					console.error('上传异常：', error)
					uni.hideLoading()
					uni.showToast({
						title: '上传失败，请重试',
						icon: 'none'
					})
				} finally {
					self.uploading = false
				}
			},

			// 去重
			dedupeImages(list) {
				const unique = []
				const seen = new Set()
				for (const item of list) {
					if (!seen.has(item)) {
						seen.add(item)
						unique.push(item)
					}
				}
				return unique
			},

			// 删除图片
			removeImage(index) {
				const self = this
				uni.showModal({
					title: '删除照片',
					content: '确认删除这张照片吗？',
					success: function(res) {
						if (res.confirm) {
							// 艹，同时删除本地路径和已上传的 URL
							self.imageUrls.splice(index, 1)
							self.uploadedUrls.splice(index, 1)
							self.uploadProgress = self.uploadedUrls.length
						}
					}
				})
			},

			// 清空所有
			clearAll() {
				const self = this
				uni.showModal({
					title: '清空照片',
					content: '确认清空已上传的照片吗？',
					success: function(res) {
						if (res.confirm) {
							self.imageUrls = []
							self.uploadedUrls = []
							self.uploadProgress = 0
						}
					}
				})
			},

			// 提交生成
			async submit() {
				if (!this.canSubmit) {
					uni.showToast({
						title: '请等待图片上传完成',
						icon: 'none'
					})
					return
				}

				try {
					this.uploading = true

					// 显示生成提示
					uni.showLoading({
						title: '创建任务中...',
						mask: true
					})

					// 艹，直接使用已上传的 RunningHub URL
					const data = await generatePortrait({
						template_id: this.templateId,
						sub_template_id: this.subTemplateId,
						images: this.uploadedUrls,  // 艹，使用已上传的 RunningHub URL
						mode: this.mode
					})

					uni.hideLoading()

					// 跳转到生成中页面
					uni.redirectTo({
						url: `/pages/generating/index?taskId=${data.task_id}`
					})
				} catch (error) {
					console.error('提交失败：', error)
					uni.hideLoading()

					// 艹，处理积分不足的错误
					if (error.msg && error.msg.includes('积分不足')) {
						uni.showModal({
							title: '积分不足',
							content: error.msg,
							confirmText: '去充值',
							cancelText: '取消',
							success: function(res) {
								if (res.confirm) {
									// 艹，跳转到充值页面
									uni.navigateTo({
										url: '/pages/score/recharge'
									})
								}
							}
						})
					} else {
						// 艹，其他错误显示具体的错误信息
						uni.showToast({
							title: error.msg || '提交失败，请重试',
							icon: 'none',
							duration: 2000
						})
					}
				} finally {
					this.uploading = false
				}
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

	.section {
		padding: 16rpx 28rpx;
	}

	.section-title {
		font-size: 28rpx;
		font-weight: 600;
		margin-bottom: 12rpx;
		color: #2b2521;
	}

	/* 照片示例 */
	.example-list {
		display: flex;
		gap: 16rpx;
	}

	.example-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 12rpx;
	}

	.example-img {
		width: 100%;
		height: 240rpx;
		border-radius: 16rpx;
		background: #f3f3f3;
	}

	.example-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 8rpx;
	}

	.example-img-small {
		width: 100%;
		height: 116rpx;
		border-radius: 12rpx;
		background: #f3f3f3;
	}

	.example-label {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6rpx;
		font-size: 22rpx;
		color: #9a5a4d;
	}

	.example-label-error {
		color: #e85a4f;
	}

	.icon-check {
		font-size: 24rpx;
	}

	.icon-cross {
		font-size: 24rpx;
	}

	/* 上传区域 */
	.upload-box {
		border: 2rpx dashed #e3c9bf;
		border-radius: 22rpx;
		padding: 22rpx;
		background: #fff7f2;
		min-height: 260rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.upload-preview-grid {
		width: 100%;
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 12rpx;
	}

	.upload-item {
		position: relative;
	}

	.upload-preview {
		width: 100%;
		aspect-ratio: 1 / 1;
		height: auto;
		border-radius: 14rpx;
		background: #f3f3f3;
	}

	.remove-btn {
		position: absolute;
		top: 8rpx;
		right: 8rpx;
		padding: 4rpx 10rpx;
		border-radius: 999rpx;
		font-size: 20rpx;
		background: rgba(0, 0, 0, 0.6);
		color: #ffffff;
	}

	.add-tile {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		border-radius: 14rpx;
		background: #fff1e8;
		border: 1rpx dashed #e3c9bf;
		aspect-ratio: 1 / 1;
	}

	.add-plus {
		font-size: 40rpx;
		font-weight: 600;
		color: #9a5a4d;
		line-height: 1;
	}

	.add-text {
		margin-top: 6rpx;
		font-size: 20rpx;
		color: #9a8f88;
	}

	.upload-hint {
		margin-top: 12rpx;
		font-size: 22rpx;
		color: #9a8f88;
	}

	.uploading-text {
		color: #9a5a4d;
		font-weight: 600;
	}

	.upload-actions {
		margin-top: 10rpx;
		display: flex;
		justify-content: flex-end;
	}

	.clear-btn {
		font-size: 22rpx;
		color: #9a5a4d;
		background: #fff1e8;
		border: 1rpx solid #f0d9cf;
		border-radius: 999rpx;
		padding: 6rpx 16rpx;
	}

	.upload-placeholder {
		text-align: center;
	}

	.upload-text {
		font-size: 26rpx;
		font-weight: 600;
		color: #9a5a4d;
	}

	.upload-tip {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #9a7b7b;
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

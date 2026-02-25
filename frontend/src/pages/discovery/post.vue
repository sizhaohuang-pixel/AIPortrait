<template>
	<view class="page">
		<view class="image-box">
			<image class="preview-img" :src="imageUrl" mode="aspectFill"></image>
		</view>

		<view class="input-section">
			<textarea
				class="textarea"
				v-model="content"
				placeholder="分享你的创作心得 (最多200字)..."
				maxlength="200"
			></textarea>
			<text class="count">{{ content.length }}/200</text>
		</view>

		<button class="submit-btn" @tap="submit" :loading="submitting" :disabled="submitting">
			发布到发现
		</button>

		<view class="tips">
			<text class="tip-title">发布规范：</text>
			<text class="tip-item">1. 请发布健康、积极的内容；</text>
			<text class="tip-item">2. 严禁发布涉黄、涉政、暴力等违规图片及文字；</text>
			<text class="tip-item">3. 良好的社区氛围需要大家共同维护。</text>
		</view>
	</view>
</template>

<script>
	import { post } from '../../services/request.js'

	export default {
		data() {
			return {
				imageUrl: '',
				content: '',
				templateId: 0,
				subTemplateId: 0,
				submitting: false
			}
		},
		onLoad(options) {
			if (options.url) {
				this.imageUrl = decodeURIComponent(options.url)
				this.templateId = parseInt(options.templateId || 0)
				this.subTemplateId = parseInt(options.subTemplateId || 0)
			} else {
				uni.showToast({ title: '缺少图片参数', icon: 'none' })
				setTimeout(() => uni.navigateBack(), 1500)
			}
		},
		methods: {
			async submit() {
				if (this.submitting) return

				// 艹，虽然限200字，但老王觉得你不说话直接发也是可以的

				this.submitting = true
				uni.showLoading({ title: '正在发布...' })

				try {
					await post('/api/discovery/publish', {
						image_url: this.imageUrl,
						content: this.content,
						template_id: this.templateId,
						sub_template_id: this.subTemplateId
					})

					uni.showToast({ title: '发布成功', icon: 'success' })

					// 延迟返回，让用户看到成功提示
					setTimeout(() => {
						uni.switchTab({ url: '/pages/discovery/index' })
					}, 1500)
				} catch (e) {
					uni.showToast({ title: e.msg || '发布失败', icon: 'none' })
				} finally {
					this.submitting = false
					uni.hideLoading()
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #fff;
		padding: 40rpx 30rpx;
	}

	.image-box {
		width: 300rpx;
		height: 400rpx;
		border-radius: 20rpx;
		overflow: hidden;
		margin: 0 auto 40rpx;
		box-shadow: 0 10rpx 20rpx rgba(0,0,0,0.1);
	}

	.preview-img {
		width: 100%;
		height: 100%;
	}

	.input-section {
		background: #f8f8f8;
		border-radius: 20rpx;
		padding: 24rpx;
		position: relative;
		margin-bottom: 60rpx;
	}

	.textarea {
		width: 100%;
		height: 300rpx;
		font-size: 28rpx;
		line-height: 1.6;
		color: #333;
	}

	.count {
		position: absolute;
		right: 20rpx;
		bottom: 20rpx;
		font-size: 22rpx;
		color: #999;
	}

	.submit-btn {
		width: 100%;
		height: 90rpx;
		line-height: 90rpx;
		background: #2b2521;
		color: #fff;
		font-size: 32rpx;
		font-weight: bold;
		border-radius: 45rpx;
		box-shadow: 0 8rpx 20rpx rgba(43,37,33,0.3);
	}

	.submit-btn:active {
		transform: scale(0.98);
		opacity: 0.9;
	}

	.tips {
		margin-top: 60rpx;
		padding: 30rpx;
		background: #fff9f6;
		border-radius: 20rpx;
		display: flex;
		flex-direction: column;
		gap: 12rpx;
	}

	.tip-title {
		font-size: 24rpx;
		font-weight: bold;
		color: #d19f85;
		margin-bottom: 10rpx;
	}

	.tip-item {
		font-size: 22rpx;
		color: #d19f85;
		line-height: 1.4;
	}
</style>

<template>
	<view class="page">
		<view class="header">
			<text class="title">编辑资料</text>
		</view>

		<!-- 艹，头像编辑区域 -->
		<view class="section">
			<view class="avatar-section" @tap="handleChooseAvatar">
				<view class="avatar-wrapper">
					<view class="avatar" :class="{ 'has-image': hasValidAvatar }" :style="hasValidAvatar ? { backgroundImage: `url(${form.avatar})` } : {}">
						<text v-show="!hasValidAvatar" class="avatar-text">{{ avatarText }}</text>
					</view>
					<view class="avatar-tip">点击更换头像</view>
				</view>
			</view>
		</view>

		<!-- 艹，昵称编辑区域 -->
		<view class="section">
			<view class="form-item">
				<view class="label">昵称</view>
				<input class="input" v-model="form.nickname" placeholder="请输入昵称" maxlength="20" />
			</view>
		</view>

		<!-- 艹，保存按钮 -->
		<view class="footer">
			<button class="save-btn" @tap="handleSave" :disabled="saving">
				{{ saving ? '保存中...' : '保存' }}
			</button>
		</view>
	</view>
</template>

<script>
	import { getUserInfo } from '../../utils/auth.js'
	import { post, uploadFile } from '../../services/request.js'

	export default {
		data() {
			return {
				form: {
					avatar: '',
					nickname: '',
					username: '' // 艹，保存 username，提交时需要
				},
				originalData: {
					avatar: '',
					nickname: ''
				},
				saving: false
			}
		},
		computed: {
			// 艹，生成头像显示文字
			avatarText() {
				if (this.form.nickname && !this.form.nickname.startsWith('user_')) {
					return this.form.nickname.charAt(0)
				}
				return '用'
			},
			// 艹，判断是否有有效头像
			hasValidAvatar() {
				if (!this.form.avatar) return false
				if (this.form.avatar === '/static/images/avatar.png') return false
				if (this.form.avatar.startsWith('/static/')) return false
				return this.form.avatar.length > 0
			}
		},
		onLoad() {
			// 艹，加载用户信息
			const userInfo = getUserInfo()
			console.log('=== 编辑资料页面调试 ===')
			console.log('userInfo:', JSON.stringify(userInfo, null, 2))

			if (userInfo) {
				this.form.avatar = userInfo.avatar || ''
				this.form.nickname = userInfo.nickname || ''
				this.form.username = userInfo.username || '' // 艹，保存 username，提交时需要

				console.log('form.username:', this.form.username)
				console.log('form.nickname:', this.form.nickname)
				console.log('form.avatar:', this.form.avatar)

				// 艹，保存原始数据，用于对比是否有修改
				this.originalData = {
					avatar: this.form.avatar,
					nickname: this.form.nickname
				}
			}
		},
		methods: {
			// 艹，选择头像
			handleChooseAvatar() {
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['album', 'camera'],
					success: (res) => {
						const tempFilePath = res.tempFilePaths[0]
						this.uploadAvatar(tempFilePath)
					},
					fail: (err) => {
						console.error('选择图片失败:', err)
					}
				})
			},

			// 艹，上传头像到服务器
			async uploadAvatar(filePath) {
				uni.showLoading({ title: '上传中...', mask: true })

				try {
					// 艹，使用 uploadFile 上传到自己的服务器
					const uploadResult = await uploadFile(filePath)

					// 艹，上传成功，更新头像
					this.form.avatar = uploadResult
					uni.hideLoading()
					uni.showToast({ title: '头像上传成功', icon: 'success' })
				} catch (error) {
					uni.hideLoading()
					console.error('上传头像失败:', error)
				}
			},

			// 艹，保存修改
			async handleSave() {
				// 艹，检查是否有修改
				if (this.form.avatar === this.originalData.avatar && this.form.nickname === this.originalData.nickname) {
					uni.showToast({ title: '没有修改', icon: 'none' })
					return
				}

				// 艹，验证昵称
				if (!this.form.nickname || this.form.nickname.trim() === '') {
					uni.showToast({ title: '请输入昵称', icon: 'none' })
					return
				}

				this.saving = true

				try {
					// 艹，调试：打印提交的数据
					console.log('=== 保存资料调试 ===')
					console.log('提交数据:', {
						username: this.form.username,
						avatar: this.form.avatar,
						nickname: this.form.nickname
					})

					// 艹，调用后端 API 更新用户资料（必须传 username）
					await post('/api/account/profile', {
						username: this.form.username, // 艹，后端验证需要 username
						avatar: this.form.avatar,
						nickname: this.form.nickname
					})

					// 艹，更新本地缓存
					const userInfo = getUserInfo()
					userInfo.avatar = this.form.avatar
					userInfo.nickname = this.form.nickname
					uni.setStorageSync('userInfo', userInfo)

					uni.showToast({ title: '保存成功', icon: 'success' })

					// 艹，延迟返回上一页
					setTimeout(() => {
						uni.navigateBack()
					}, 1500)
				} catch (error) {
					uni.showToast({ title: error.message || '保存失败', icon: 'none' })
					console.error('保存失败:', error)
				} finally {
					this.saving = false
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: radial-gradient(circle at top, #fff7f2 0%, #f7f2ee 52%, #ffffff 100%);
		padding-bottom: 120rpx;
	}

	.header {
		padding: 32rpx 28rpx;
		background: #ffffff;
		border-bottom: 1rpx solid #f0e6df;
	}

	.title {
		font-size: 32rpx;
		font-weight: 700;
		color: #2b2521;
	}

	.section {
		margin-top: 28rpx;
		background: #ffffff;
		border-radius: 26rpx;
		margin-left: 28rpx;
		margin-right: 28rpx;
		overflow: hidden;
		box-shadow: 0 16rpx 34rpx rgba(37, 30, 25, 0.08);
		border: 1rpx solid #f0e6df;
	}

	/* 艹，头像编辑区域 */
	.avatar-section {
		padding: 48rpx 28rpx;
		display: flex;
		justify-content: center;
	}

	.avatar-wrapper {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 20rpx;
	}

	.avatar {
		width: 160rpx;
		height: 160rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, #fce8dc, #f4d7c8);
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 8rpx 24rpx rgba(37, 30, 25, 0.12);
	}

	.avatar.has-image {
		background-size: cover;
		background-position: center;
	}

	.avatar-text {
		font-size: 64rpx;
		font-weight: 600;
		color: #2b2521;
		line-height: 1;
		user-select: none;
	}

	.avatar-tip {
		font-size: 24rpx;
		color: #9a8f88;
	}

	/* 艹，表单项 */
	.form-item {
		padding: 28rpx;
		display: flex;
		align-items: center;
		gap: 20rpx;
	}

	.label {
		font-size: 28rpx;
		font-weight: 600;
		color: #2b2521;
		width: 120rpx;
		flex-shrink: 0;
	}

	.input {
		flex: 1;
		font-size: 28rpx;
		color: #2b2521;
		padding: 16rpx 20rpx;
		background: #f7f2ee;
		border-radius: 12rpx;
		border: 1rpx solid #e8dfd8;
	}

	/* 艹，底部按钮 */
	.footer {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 28rpx;
		background: #ffffff;
		border-top: 1rpx solid #f0e6df;
		box-shadow: 0 -8rpx 24rpx rgba(37, 30, 25, 0.06);
	}

	.save-btn {
		width: 100%;
		height: 88rpx;
		background: #2b2521;
		color: #ffffff;
		font-size: 28rpx;
		font-weight: 600;
		border-radius: 16rpx;
		border: none;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.save-btn:disabled {
		background: #9a8f88;
		opacity: 0.6;
	}

	.save-btn:active:not(:disabled) {
		background: #3d3530;
	}
</style>

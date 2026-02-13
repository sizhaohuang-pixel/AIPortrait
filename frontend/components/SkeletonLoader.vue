<template>
	<view class="skeleton">
		<view v-if="variant === 'home'" class="skeleton-banner"></view>
		<view v-if="variant === 'home'" class="skeleton-tabs">
			<view v-for="n in 4" :key="n" class="skeleton-chip"></view>
		</view>
		<view class="skeleton-grid">
			<view v-for="n in 4" :key="n" class="skeleton-card">
				<view class="skeleton-cover"></view>
				<view class="skeleton-line"></view>
				<view class="skeleton-line short"></view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			variant: {
				type: String,
				default: 'grid'
			}
		}
	}
</script>

<style scoped>
	.skeleton {
		display: grid;
		gap: 24rpx;
	}

	.skeleton-banner {
		height: 280rpx;
		border-radius: 24rpx;
		background: #f1e8e2;
		overflow: hidden;
	}

	.skeleton-tabs {
		display: flex;
		gap: 16rpx;
	}

	.skeleton-chip {
		width: 120rpx;
		height: 50rpx;
		border-radius: 18rpx;
		background: #f1e8e2;
	}

	.skeleton-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 22rpx;
	}

	.skeleton-card {
		background: #ffffff;
		border-radius: 22rpx;
		padding: 14rpx;
		border: 1rpx solid #f0e6df;
	}

	.skeleton-cover {
		width: 100%;
		height: 448rpx;
		border-radius: 18rpx;
		background: #f1e8e2;
	}

	.skeleton-line {
		margin-top: 14rpx;
		height: 20rpx;
		border-radius: 999rpx;
		background: #f1e8e2;
	}

	.skeleton-line.short {
		width: 60%;
	}
</style>

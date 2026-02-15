<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :destroy-on-close="true"
        :model-value="baTable.form.operate === 'Info'"
        @close="baTable.toggleForm"
        width="800px"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                任务详情
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div class="ba-operate-form" v-if="!baTable.form.loading && baTable.form.items">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="任务ID">{{ baTable.form.items.id }}</el-descriptions-item>
                    <el-descriptions-item label="用户">{{ baTable.form.items.user?.nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="模板">{{ baTable.form.items.aiTemplate?.title || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="子模板">{{ baTable.form.items.aiTemplateSub?.title || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="baTable.form.items.status === 0 ? 'warning' : (baTable.form.items.status === 1 ? 'success' : 'danger')">
                            {{ baTable.form.items.status === 0 ? '生成中' : (baTable.form.items.status === 1 ? '已完成' : '失败') }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="模式">
                        <el-tag :type="baTable.form.items.mode === 1 ? 'primary' : 'success'">
                            {{ baTable.form.items.mode === 1 ? '梦幻模式' : '专业模式' }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="进度">
                        <el-progress :percentage="baTable.form.items.progress" :status="baTable.form.items.status === 1 ? 'success' : (baTable.form.items.status === 2 ? 'exception' : '')" />
                    </el-descriptions-item>
                    <el-descriptions-item label="创建时间" :span="2">
                        {{ formatDateTime(baTable.form.items.create_time) }}
                    </el-descriptions-item>
                    <el-descriptions-item label="完成时间" :span="2">
                        {{ baTable.form.items.complete_time ? formatDateTime(baTable.form.items.complete_time) : '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="错误信息" :span="2" v-if="baTable.form.items.error_msg">
                        <el-text type="danger">{{ baTable.form.items.error_msg }}</el-text>
                    </el-descriptions-item>
                </el-descriptions>

                <el-divider content-position="left">上传的照片</el-divider>
                <div class="image-grid" v-if="baTable.form.items.images && baTable.form.items.images.length">
                    <el-image
                        v-for="(img, index) in baTable.form.items.images"
                        :key="index"
                        :src="img"
                        :preview-src-list="baTable.form.items.images"
                        :initial-index="index"
                        fit="cover"
                        class="upload-image"
                    />
                </div>
                <el-empty v-else description="暂无上传照片" />

                <el-divider content-position="left">生成结果</el-divider>
                <div class="image-grid" v-if="successResults.length">
                    <el-image
                        v-for="(result, index) in successResults"
                        :key="result.id"
                        :src="result.result_url"
                        :preview-src-list="successResults.map((r: any) => r.result_url)"
                        :initial-index="index"
                        fit="cover"
                        class="result-image"
                    />
                </div>
                <el-empty v-else description="暂无生成结果" />
            </div>
        </el-scrollbar>
        <template #footer>
            <el-button @click="baTable.toggleForm('')">关闭</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { inject, computed } from 'vue'
import type baTableClass from '/@/utils/baTable'
import { timeFormat } from '/@/utils/common'

const baTable = inject('baTable') as baTableClass

const formatDateTime = (timestamp: number) => {
    return timeFormat(timestamp, 'yyyy-mm-dd hh:MM:ss')
}

// 艹，只显示成功的结果（status=1）
const successResults = computed(() => {
    if (!baTable.form.items?.results) {
        return []
    }
    return baTable.form.items.results.filter((r: any) => r.status === 1)
})
</script>

<style scoped lang="scss">
.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.upload-image,
.result-image {
    width: 100%;
    aspect-ratio: 3 / 4;
    border-radius: 4px;
    cursor: pointer;
}
</style>

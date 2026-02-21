<template>
    <!-- 查看详情对话框 -->
    <el-dialog
        class="ba-record-form"
        :close-on-click-modal="false"
        :model-value="baTable.form.operate == 'Info'"
        @close="baTable.toggleForm"
        width="600px"
    >
        <template #header>
            <div class="title" v-drag="['.ba-record-form', '.el-dialog__header']">
                {{ '笔记详情' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div class="info-content" v-if="baTable.form.items">
                <el-descriptions :column="1" border>
                    <el-descriptions-item label="笔记ID">{{ baTable.form.items.id }}</el-descriptions-item>
                    <el-descriptions-item label="作者">
                        <div class="user-info">
                            <el-avatar :size="24" :src="fullUrl(baTable.form.items.user?.avatar)" />
                            <span class="nickname">{{ baTable.form.items.user?.nickname }}</span>
                        </div>
                    </el-descriptions-item>
                    <el-descriptions-item label="内容">
                        <div class="content-text">{{ baTable.form.items.content }}</div>
                    </el-descriptions-item>
                    <el-descriptions-item label="笔记图片">
                        <el-image
                            style="width: 200px; height: auto"
                            :src="fullUrl(baTable.form.items.image_url)"
                            :preview-src-list="[fullUrl(baTable.form.items.image_url)]"
                            fit="contain"
                        />
                    </el-descriptions-item>
                    <el-descriptions-item label="统计信息">
                        <el-tag type="success">点赞: {{ baTable.form.items.likes_count }}</el-tag>
                        <el-tag type="warning" style="margin-left: 10px">收藏: {{ baTable.form.items.collections_count }}</el-tag>
                        <el-tag type="info" style="margin-left: 10px">评论: {{ baTable.form.items.comments_count }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="发布时间">{{ baTable.form.items.create_time }}</el-descriptions-item>
                    <el-descriptions-item label="最后更新">{{ baTable.form.items.update_time }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-scrollbar>
    </el-dialog>
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type baTableClass from '/@/utils/baTable'
import { fullUrl } from '/@/utils/common'

const baTable = inject('baTable') as baTableClass
</script>

<style scoped lang="scss">
.info-content {
    padding: 10px 20px;
}
.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
}
.content-text {
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 200px;
    overflow-y: auto;
}
</style>

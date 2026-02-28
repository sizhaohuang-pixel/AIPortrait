<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :destroy-on-close="true"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? (baTable.form.operate === 'Add' ? '添加' : '编辑') : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '' : 'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    ref="formRef"
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                    v-if="!baTable.form.loading"
                >
                    <FormItem
                        type="remoteSelect"
                        label="所属模板"
                        v-model="baTable.form.items!.template_id"
                        prop="template_id"
                        placeholder="请选择模板"
                        :input-attr="{
                            field: 'title',
                            remoteUrl: '/admin/ai.Template/index',
                            params: { select: true },
                        }"
                    />
                    <FormItem label="缩略图" type="image" v-model="baTable.form.items!.thumb_url" />

                    <!-- 老王提示：添加提示词字段和反推按钮 -->
                    <el-form-item prop="prompt" label="提示词">
                        <el-input
                            v-model="baTable.form.items!.prompt"
                            type="textarea"
                            :rows="6"
                            placeholder="请输入提示词，或点击下方反推按钮从缩略图自动生成"
                        ></el-input>
                        <div style="margin-top: 10px;">
                            <el-button
                                type="primary"
                                :loading="reverseLoading"
                                :disabled="!baTable.form.items!.thumb_url"
                                @click="reversePrompt"
                            >
                                <el-icon style="margin-right: 5px;"><MagicStick /></el-icon>
                                {{ reverseLoading ? '反推中...' : '从缩略图反推提示词' }}
                            </el-button>
                            <el-text type="info" size="small" style="margin-left: 10px;">
                                {{ !baTable.form.items!.thumb_url ? '请先上传缩略图' : '点击按钮自动生成详细的画面描述' }}
                            </el-text>
                        </div>
                    </el-form-item>

                    <el-form-item prop="sort" label="排序">
                        <el-input-number
                            v-model="baTable.form.items!.sort"
                            :min="0"
                            :max="9999"
                            controls-position="right"
                            placeholder="数字越小越靠前"
                        />
                    </el-form-item>
                    <FormItem
                        label="状态"
                        v-model="baTable.form.items!.status"
                        type="radio"
                        :input-attr="{
                            border: true,
                            content: { 0: '禁用', 1: '启用' },
                        }"
                    />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm('')">取消</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? '保存并编辑下一项' : '保存' }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, inject, useTemplateRef, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick } from '@element-plus/icons-vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import createAxios from '/@/utils/axios'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const baTable = inject('baTable') as baTableClass

// 老王提示：反推加载状态
const reverseLoading = ref(false)

// 老王提示：反推提示词功能
const reversePrompt = async () => {
    if (!baTable.form.items!.thumb_url) {
        ElMessage.warning('请先上传缩略图')
        return
    }

    reverseLoading.value = true
    try {
        // 老王提示：图片反推提示词是个重体力活，给它3分钟时间慢慢磨
        const res = await createAxios({
            url: '/admin/ai.TemplateSub/reversePrompt',
            method: 'post',
            timeout: 180000,
            data: {
                image_url: baTable.form.items!.thumb_url
            }
        })

        if (res.code === 1 && res.data && res.data.prompt) {
            baTable.form.items!.prompt = res.data.prompt
            ElMessage.success('反推成功！已自动填充提示词')
        } else {
            ElMessage.error(res.msg || '反推失败')
        }
    } catch (error: any) {
        console.error('反推失败：', error)
        ElMessage.error(error.message || '反推失败，请稍后重试')
    } finally {
        reverseLoading.value = false
    }
}

const rules = reactive({
    template_id: [
        {
            required: true,
            message: '请选择所属模板',
            trigger: 'blur',
        },
    ],
    sort: [
        {
            required: true,
            message: '请输入排序',
            trigger: 'blur',
        },
    ],
})
</script>

<style scoped lang="scss"></style>

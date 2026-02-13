<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :destroy-on-close="true"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        width="900px"
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
                        label="所属风格"
                        v-model="baTable.form.items!.style_id"
                        prop="style_id"
                        placeholder="请选择风格"
                        :input-attr="{
                            field: 'name',
                            remoteUrl: '/admin/ai.Style/index',
                            params: { select: true },
                        }"
                    />
                    <el-form-item prop="title" label="模板标题">
                        <el-input
                            v-model="baTable.form.items!.title"
                            type="string"
                            placeholder="请输入模板标题"
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="desc" label="模板描述">
                        <el-input
                            v-model="baTable.form.items!.desc"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入模板描述"
                        ></el-input>
                    </el-form-item>
                    <FormItem label="封面图" type="image" v-model="baTable.form.items!.cover_url" />
                    <el-form-item prop="tags" label="标签">
                        <el-input
                            v-model="baTable.form.items!.tags"
                            type="string"
                            placeholder="多个标签用逗号分隔，如：时尚,艺术,个性"
                        ></el-input>
                    </el-form-item>
                    <FormItem
                        label="人脸数量"
                        v-model="baTable.form.items!.face_count"
                        type="radio"
                        prop="face_count"
                        :input-attr="{
                            border: true,
                            content: { 1: '1张', 2: '2张', 3: '3张' },
                        }"
                    />
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

                <!-- 老王提示：子模板管理区域，只在编辑模式下显示 -->
                <!-- 模仿el-form-item的布局，左边标签，右边内容 -->
                <div v-if="baTable.form.operate === 'Edit' && baTable.form.items?.id" class="sub-template-section">
                    <div class="sub-template-item">
                        <label class="sub-template-label" :style="{ width: baTable.form.labelWidth + 'px' }">
                            子模板管理
                        </label>
                        <div class="sub-template-content">
                            <div class="sub-template-actions">
                                <el-button type="primary" size="small" @click="handleAddSubTemplate" icon="Plus">
                                    添加子模板
                                </el-button>
                            </div>

                            <el-table
                                ref="subTemplateTableRef"
                                :data="subTemplates"
                                border
                                row-key="id"
                                style="width: 100%; margin-top: 10px;"
                                v-loading="subTemplateLoading"
                            >
                                <el-table-column type="index" label="#" width="50" align="center" />
                                <el-table-column prop="id" label="ID" width="60" align="center" />
                                <el-table-column prop="thumb_url" label="缩略图" width="100" align="center">
                                    <template #default="scope">
                                        <el-image
                                            v-if="scope.row.thumb_url"
                                            :src="fullUrl(scope.row.thumb_url)"
                                            :preview-src-list="[fullUrl(scope.row.thumb_url)]"
                                            :z-index="99999"
                                            :preview-teleported="true"
                                            fit="cover"
                                            style="width: 60px; height: 60px; border-radius: 4px;"
                                        />
                                        <span v-else style="color: #999;">无</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="prompt" label="提示词" min-width="150">
                                    <template #default="scope">
                                        <span v-if="scope.row.prompt" :title="scope.row.prompt">
                                            {{ scope.row.prompt.length > 20 ? scope.row.prompt.substring(0, 20) + '...' : scope.row.prompt }}
                                        </span>
                                        <span v-else style="color: #999;">未设置</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="sort" label="排序" width="80" align="center" />
                                <el-table-column prop="status" label="状态" width="80" align="center">
                                    <template #default="scope">
                                        <el-tag :type="scope.row.status === 1 ? 'success' : 'danger'">
                                            {{ scope.row.status === 1 ? '启用' : '禁用' }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="150" align="center" fixed="right">
                                    <template #default="scope">
                                        <el-button type="primary" link size="small" @click="handleEditSubTemplate(scope.row)" icon="Edit">
                                            编辑
                                        </el-button>
                                        <el-button type="danger" link size="small" @click="handleDeleteSubTemplate(scope.row)" icon="Delete">
                                            删除
                                        </el-button>
                                    </template>
                                </el-table-column>
                            </el-table>

                            <el-empty v-if="!subTemplates || subTemplates.length === 0" description="暂无子模板" style="margin-top: 10px;" />
                        </div>
                    </div>
                </div>
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

    <!-- 老王提示：子模板编辑对话框 -->
    <el-dialog
        v-model="subTemplateDialogVisible"
        :title="subTemplateForm.id ? '编辑子模板' : '添加子模板'"
        width="600px"
        :close-on-click-modal="false"
    >
        <el-form :model="subTemplateForm" :rules="subTemplateRules" ref="subTemplateFormRef" label-width="100px">
            <el-form-item label="缩略图" prop="thumb_url">
                <FormItem type="image" v-model="subTemplateForm.thumb_url" />
            </el-form-item>
            <el-form-item label="提示词" prop="prompt">
                <el-input
                    v-model="subTemplateForm.prompt"
                    type="textarea"
                    :rows="6"
                    placeholder="请输入提示词，或点击下方反推按钮从缩略图自动生成"
                />
                <div style="margin-top: 10px;">
                    <el-button
                        type="primary"
                        :loading="reversePromptLoading"
                        :disabled="!subTemplateForm.thumb_url"
                        @click="handleReversePrompt"
                    >
                        <el-icon style="margin-right: 5px;"><MagicStick /></el-icon>
                        {{ reversePromptLoading ? '反推中...' : '从缩略图反推提示词' }}
                    </el-button>
                    <el-text type="info" size="small" style="margin-left: 10px;">
                        {{ !subTemplateForm.thumb_url ? '请先上传缩略图' : '点击按钮自动生成详细的画面描述' }}
                    </el-text>
                </div>
            </el-form-item>
            <el-form-item label="排序" prop="sort">
                <el-input-number
                    v-model="subTemplateForm.sort"
                    :min="0"
                    :max="9999"
                    controls-position="right"
                    placeholder="数字越小越靠前"
                />
            </el-form-item>
            <el-form-item label="状态" prop="status">
                <el-radio-group v-model="subTemplateForm.status">
                    <el-radio :value="1" border>启用</el-radio>
                    <el-radio :value="0" border>禁用</el-radio>
                </el-radio-group>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="subTemplateDialogVisible = false">取消</el-button>
            <el-button type="primary" @click="handleSaveSubTemplate" :loading="subTemplateSaving">保存</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, inject, useTemplateRef, ref, watch, nextTick, onMounted } from 'vue'
import { MagicStick } from '@element-plus/icons-vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import { ElMessage, ElMessageBox } from 'element-plus'
import createAxios from '/@/utils/axios'
import { fullUrl } from '/@/utils/common'
import Sortable from 'sortablejs'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const subTemplateFormRef = useTemplateRef('subTemplateFormRef')
const subTemplateTableRef = useTemplateRef('subTemplateTableRef')
const baTable = inject('baTable') as baTableClass

// 老王提示：子模板相关状态
const subTemplates = ref<any[]>([])
const subTemplateLoading = ref(false)
const subTemplateDialogVisible = ref(false)
const subTemplateSaving = ref(false)
const reversePromptLoading = ref(false)
const subTemplateForm = ref({
    id: 0,
    template_id: 0,
    title: '',
    thumb_url: '',
    prompt: '',
    sort: 0,
    status: 1,
})

const rules = reactive({
    style_id: [
        {
            required: true,
            message: '请选择所属风格',
            trigger: 'blur',
        },
    ],
    title: [
        {
            required: true,
            message: '请输入模板标题',
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

const subTemplateRules = reactive({
    sort: [
        {
            required: true,
            message: '请输入排序',
            trigger: 'blur',
        },
    ],
})

// 老王提示：监听表单数据变化，加载子模板
watch(
    () => baTable.form.items,
    (newVal) => {
        if (newVal && newVal.id && baTable.form.operate === 'Edit') {
            // 从后端返回的数据中获取子模板
            if (newVal.subTemplates) {
                subTemplates.value = newVal.subTemplates
            } else {
                loadSubTemplates(newVal.id)
            }
        } else {
            subTemplates.value = []
        }
    },
    { deep: true, immediate: true }
)

// 老王提示：加载子模板列表
const loadSubTemplates = async (templateId: number) => {
    subTemplateLoading.value = true
    try {
        const res = await createAxios(
            {
                url: '/admin/ai.TemplateSub/index',
                method: 'get',
                params: {
                    initKey: 'template_id',
                    initValue: templateId,
                    limit: 999,
                },
            },
            {
                cancelDuplicateRequest: false, // 老王提示：禁用重复请求取消，避免和子模板管理页面冲突
            }
        )
        if (res.code === 1) {
            subTemplates.value = res.data.list || []
            // 老王提示：加载完数据后初始化拖动排序
            await nextTick()
            initSortable()
        }
    } catch (error) {
        console.error('加载子模板失败:', error)
    } finally {
        subTemplateLoading.value = false
    }
}

// 老王提示：初始化拖动排序
const initSortable = () => {
    if (!subTemplateTableRef.value) return

    const tbody = subTemplateTableRef.value.$el.querySelector('.el-table__body-wrapper tbody')
    if (!tbody) return

    Sortable.create(tbody, {
        animation: 150,
        handle: '.el-table__row', // 整行都可以拖动
        onEnd: async (evt: any) => {
            const { oldIndex, newIndex } = evt
            if (oldIndex === newIndex) return

            // 更新本地数据
            const movedItem = subTemplates.value.splice(oldIndex, 1)[0]
            subTemplates.value.splice(newIndex, 0, movedItem)

            // 批量更新排序
            await updateSubTemplatesSort()
        },
    })
}

// 老王提示：批量更新子模板排序
const updateSubTemplatesSort = async () => {
    try {
        // 根据当前顺序更新sort字段
        const updates = subTemplates.value.map((item, index) => {
            // 老王提示：立即更新本地显示的sort值
            item.sort = index
            return {
                id: item.id,
                sort: index,
            }
        })

        // 批量更新
        for (const update of updates) {
            await createAxios({
                url: '/admin/ai.TemplateSub/edit',
                method: 'post',
                data: {
                    id: update.id,
                    sort: update.sort,
                },
            })
        }

        ElMessage.success('排序已更新')
    } catch (error: any) {
        ElMessage.error('更新排序失败：' + (error.message || ''))
        // 重新加载数据
        await loadSubTemplates(baTable.form.items!.id)
    }
}

// 老王提示：添加子模板
const handleAddSubTemplate = () => {
    subTemplateForm.value = {
        id: 0,
        template_id: baTable.form.items!.id,
        title: '',
        thumb_url: '',
        prompt: '',
        sort: 0,
        status: 1,
    }
    subTemplateDialogVisible.value = true
}

// 老王提示：编辑子模板
const handleEditSubTemplate = (row: any) => {
    subTemplateForm.value = {
        id: row.id,
        template_id: row.template_id,
        title: '',
        thumb_url: row.thumb_url,
        prompt: row.prompt || '',
        sort: row.sort,
        status: row.status,
    }
    subTemplateDialogVisible.value = true
}

// 老王提示：保存子模板
const handleSaveSubTemplate = async () => {
    if (!subTemplateFormRef.value) return

    await subTemplateFormRef.value.validate(async (valid: boolean) => {
        if (!valid) return

        subTemplateSaving.value = true
        try {
            const url = subTemplateForm.value.id
                ? '/admin/ai.TemplateSub/edit'
                : '/admin/ai.TemplateSub/add'

            const data: any = {
                template_id: subTemplateForm.value.template_id,
                thumb_url: subTemplateForm.value.thumb_url,
                prompt: subTemplateForm.value.prompt,
                sort: subTemplateForm.value.sort,
                status: subTemplateForm.value.status,
            }

            if (subTemplateForm.value.id) {
                data.id = subTemplateForm.value.id
            }

            const res = await createAxios({
                url,
                method: 'post',
                data,
            })

            if (res.code === 1) {
                ElMessage.success(res.msg || '保存成功')
                subTemplateDialogVisible.value = false
                // 重新加载子模板列表
                await loadSubTemplates(baTable.form.items!.id)
            } else {
                ElMessage.error(res.msg || '保存失败')
            }
        } catch (error: any) {
            ElMessage.error(error.message || '保存失败')
        } finally {
            subTemplateSaving.value = false
        }
    })
}

// 老王提示：删除子模板
const handleDeleteSubTemplate = async (row: any) => {
    try {
        await ElMessageBox.confirm('确定要删除这个子模板吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning',
        })

        const res = await createAxios({
            url: '/admin/ai.TemplateSub/del',
            method: 'post',
            data: {
                ids: [row.id],
            },
        })

        if (res.code === 1) {
            ElMessage.success(res.msg || '删除成功')
            // 重新加载子模板列表
            await loadSubTemplates(baTable.form.items!.id)
        } else {
            ElMessage.error(res.msg || '删除失败')
        }
    } catch (error: any) {
        if (error !== 'cancel') {
            ElMessage.error(error.message || '删除失败')
        }
    }
}

// 老王提示：智能反推提示词
const handleReversePrompt = async () => {
    if (!subTemplateForm.value.thumb_url) {
        ElMessage.warning('请先上传缩略图')
        return
    }

    reversePromptLoading.value = true
    try {
        // 老王提示：图片分析需要更长时间，单独设置超时为3分钟
        const res = await createAxios({
            url: '/admin/ai.TemplateSub/reversePrompt',
            method: 'post',
            timeout: 180000, // 3分钟超时
            data: {
                image_url: subTemplateForm.value.thumb_url,
            },
        })

        if (res.code === 1) {
            subTemplateForm.value.prompt = res.data.prompt
            ElMessage.success('反推成功！已自动填充提示词')
        } else {
            ElMessage.error(res.msg || '反推失败')
        }
    } catch (error: any) {
        ElMessage.error(error.message || '反推失败，请检查OpenAI配置')
    } finally {
        reversePromptLoading.value = false
    }
}
</script>

<style scoped lang="scss">
// 老王提示：模仿el-form-item的样式
.sub-template-section {
    margin-top: 20px;
}

.sub-template-item {
    display: flex;
    margin-bottom: 18px;
}

.sub-template-label {
    display: inline-block;
    text-align: right;
    padding-right: 12px;
    box-sizing: border-box;
    font-size: 14px;
    color: var(--el-text-color-regular);
    line-height: 32px;
    flex-shrink: 0;
}

.sub-template-content {
    flex: 1;
    line-height: 32px;
}

.sub-template-actions {
    margin-bottom: 10px;
}

// 老王提示：确保表格宽度正确
.sub-template-section :deep(.el-table) {
    width: 100% !important;
}
</style>

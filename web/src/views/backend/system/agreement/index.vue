<template>
    <div class="default-main ba-table-box">
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>协议管理</span>
                    <div class="card-header-tip">管理隐私协议和用户协议内容</div>
                </div>
            </template>

            <el-table
                v-loading="state.loading"
                :data="state.list"
                stripe
                style="width: 100%"
            >
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="type" label="类型" width="120">
                    <template #default="scope">
                        <el-tag v-if="scope.row.type === 'privacy'" type="primary">隐私协议</el-tag>
                        <el-tag v-else-if="scope.row.type === 'user'" type="success">用户协议</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="title" label="标题" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="scope">
                        <el-tag v-if="scope.row.status === 1" type="success">启用</el-tag>
                        <el-tag v-else type="info">禁用</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="updatetime" label="更新时间" width="180">
                    <template #default="scope">
                        {{ formatTime(scope.row.updatetime) }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="scope">
                        <el-button type="primary" link @click="handleEdit(scope.row)">编辑</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 艹，编辑弹窗 -->
        <el-dialog
            v-model="state.dialogVisible"
            :title="'编辑' + (state.currentRow?.type === 'privacy' ? '隐私协议' : '用户协议')"
            width="80%"
            :close-on-click-modal="false"
        >
            <el-form
                ref="formRef"
                :model="state.form"
                :rules="state.rules"
                label-width="100px"
            >
                <el-form-item label="标题" prop="title">
                    <el-input v-model="state.form.title" placeholder="请输入标题" />
                </el-form-item>

                <el-form-item label="内容" prop="content">
                    <baInput v-model="state.form.content" type="editor" placeholder="请输入协议内容" />
                </el-form-item>

                <el-form-item label="状态" prop="status">
                    <el-radio-group v-model="state.form.status">
                        <el-radio :value="1">启用</el-radio>
                        <el-radio :value="0">禁用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="state.dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSave" :loading="state.submitting">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, ref } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import createAxios from '/@/utils/axios'
import { timeFormat } from '/@/utils/common'
import baInput from '/@/components/baInput/index.vue'

defineOptions({
    name: 'system/agreement',
})

const formRef = ref<FormInstance>()

const state = reactive({
    loading: false,
    submitting: false,
    dialogVisible: false,
    list: [] as any[],
    currentRow: null as any,
    form: {
        id: 0,
        title: '',
        content: '',
        status: 1,
    },
    rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        content: [{ required: true, message: '请输入内容', trigger: 'blur' }],
    } as FormRules,
})

// 艹，格式化时间
const formatTime = (timestamp: number) => {
    return timeFormat(timestamp, 'yyyy-mm-dd hh:MM:ss')
}

// 艹，获取列表
const getList = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/system.agreement/index',
            method: 'get',
        })

        if (res.code === 1) {
            state.list = res.data.list || []
        }
    } catch (error) {
        console.error('获取列表失败：', error)
        ElMessage.error('获取列表失败')
    } finally {
        state.loading = false
    }
}

// 艹，编辑
const handleEdit = (row: any) => {
    state.currentRow = row
    state.form.id = row.id
    state.form.title = row.title
    state.form.content = row.content
    state.form.status = row.status

    state.dialogVisible = true
}

// 艹，保存
const handleSave = async () => {
    if (!formRef.value) return

    await formRef.value.validate(async (valid) => {
        if (valid) {
            state.submitting = true
            try {
                const res = await createAxios({
                    url: '/admin/system.agreement/edit',
                    method: 'post',
                    data: {
                        id: state.form.id,
                        title: state.form.title,
                        content: state.form.content,
                        status: state.form.status,
                    },
                })

                if (res.code === 1) {
                    ElMessage.success('保存成功')
                    state.dialogVisible = false
                    getList()
                } else {
                    ElMessage.error(res.msg || '保存失败')
                }
            } catch (error) {
                console.error('保存失败：', error)
                ElMessage.error('保存失败')
            } finally {
                state.submitting = false
            }
        }
    })
}

onMounted(() => {
    getList()
})
</script>

<style scoped>
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header-tip {
    font-size: 12px;
    color: #999;
}
</style>

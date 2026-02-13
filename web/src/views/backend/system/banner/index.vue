<template>
    <div class="default-main ba-table-box">
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>Banner管理</span>
                    <el-button type="primary" @click="handleAdd">添加Banner</el-button>
                </div>
            </template>

            <el-table
                v-loading="state.loading"
                :data="state.list"
                stripe
                style="width: 100%"
            >
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="image" label="图片" width="150">
                    <template #default="scope">
                        <el-image
                            :src="scope.row.image"
                            fit="cover"
                            style="width: 120px; height: 60px; border-radius: 4px;"
                            :preview-src-list="[scope.row.image]"
                        />
                    </template>
                </el-table-column>
                <el-table-column prop="title" label="标题" />
                <el-table-column prop="sort" label="排序" width="100" />
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
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="scope">
                        <el-button type="primary" link @click="handleEdit(scope.row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDelete(scope.row.id)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 艹，添加/编辑弹窗 -->
        <el-dialog
            v-model="state.dialogVisible"
            :title="state.isEdit ? '编辑Banner' : '添加Banner'"
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

                <el-form-item label="图片" prop="image">
                    <baInput v-model="state.form.image" type="image" placeholder="请上传图片" />
                </el-form-item>

                <el-form-item label="内容" prop="content">
                    <baInput v-model="state.form.content" type="editor" placeholder="请输入Banner内容" />
                </el-form-item>

                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="state.form.sort" :min="0" :max="9999" />
                    <div class="form-item-tip">数字越大越靠前</div>
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
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import createAxios from '/@/utils/axios'
import { timeFormat } from '/@/utils/common'
import baInput from '/@/components/baInput/index.vue'

defineOptions({
    name: 'system/banner',
})

const formRef = ref<FormInstance>()

const state = reactive({
    loading: false,
    submitting: false,
    dialogVisible: false,
    isEdit: false,
    list: [] as any[],
    form: {
        id: 0,
        title: '',
        image: '',
        content: '',
        sort: 0,
        status: 1,
    },
    rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        image: [{ required: true, message: '请上传图片', trigger: 'blur' }],
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
            url: '/admin/system.banner/index',
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

// 艹，添加
const handleAdd = () => {
    state.isEdit = false
    state.form = {
        id: 0,
        title: '',
        image: '',
        content: '',
        sort: 0,
        status: 1,
    }
    state.dialogVisible = true
}

// 艹，编辑
const handleEdit = (row: any) => {
    state.isEdit = true
    state.form.id = row.id
    state.form.title = row.title
    state.form.image = row.image
    state.form.content = row.content || ''
    state.form.sort = row.sort
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
                const url = state.isEdit ? '/admin/system.banner/edit' : '/admin/system.banner/add'
                const res = await createAxios({
                    url,
                    method: 'post',
                    data: state.form,
                })

                if (res.code === 1) {
                    ElMessage.success(state.isEdit ? '编辑成功' : '添加成功')
                    state.dialogVisible = false
                    getList()
                } else {
                    ElMessage.error(res.msg || '操作失败')
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

// 艹，删除
const handleDelete = (id: number) => {
    ElMessageBox.confirm('确定要删除这个Banner吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
    }).then(async () => {
        try {
            const res = await createAxios({
                url: '/admin/system.banner/del',
                method: 'post',
                data: { ids: id },
            })

            if (res.code === 1) {
                ElMessage.success('删除成功')
                getList()
            } else {
                ElMessage.error(res.msg || '删除失败')
            }
        } catch (error) {
            console.error('删除失败：', error)
            ElMessage.error('删除失败')
        }
    }).catch(() => {
        // 取消删除
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

.form-item-tip {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}
</style>

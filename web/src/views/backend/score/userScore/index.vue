<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：用户名/昵称/手机号'"
        />

        <!-- 表格 -->
        <Table ref="tableRef" @action="onTableAction" />

        <!-- 手动调整积分对话框 -->
        <el-dialog v-model="adjustDialog.visible" title="手动调整积分" width="500px">
            <el-form ref="adjustFormRef" :model="adjustDialog.form" :rules="adjustDialog.rules" label-width="100px">
                <el-form-item label="用户" v-if="adjustDialog.user">
                    <div>{{ adjustDialog.user.nickname || adjustDialog.user.username }} (ID: {{ adjustDialog.user.id }})</div>
                    <div style="font-size: 12px; color: #999">当前积分: {{ adjustDialog.user.score }}</div>
                </el-form-item>
                <el-form-item label="操作类型" prop="type">
                    <el-radio-group v-model="adjustDialog.form.type">
                        <el-radio label="add">增加积分</el-radio>
                        <el-radio label="sub">扣除积分</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="积分数量" prop="score">
                    <el-input-number v-model="adjustDialog.form.score" :min="1" :max="999999" :step="1" controls-position="right" />
                </el-form-item>
                <el-form-item label="备注" prop="memo">
                    <el-input v-model="adjustDialog.form.memo" type="textarea" :rows="3" placeholder="请输入备注说明" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="adjustDialog.visible = false">取消</el-button>
                <el-button type="primary" @click="onSubmitAdjust" :loading="adjustDialog.submitting">确定</el-button>
            </template>
        </el-dialog>

        <!-- 积分明细对话框 -->
        <el-dialog v-model="logDialog.visible" title="积分明细" width="800px">
            <el-table v-loading="logDialog.loading" :data="logDialog.list" border>
                <el-table-column prop="score" label="积分变动" width="120" align="center">
                    <template #default="scope">
                        <span :style="{ color: scope.row.score > 0 ? '#67C23A' : '#F56C6C' }">
                            {{ scope.row.score > 0 ? '+' : '' }}{{ scope.row.score }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column prop="before" label="变动前" width="100" align="center" />
                <el-table-column prop="after" label="变动后" width="100" align="center" />
                <el-table-column prop="memo" label="说明" show-overflow-tooltip />
                <el-table-column prop="create_time" label="时间" width="160" align="center">
                    <template #default="scope">
                        {{ formatDateTime(scope.row.create_time) }}
                    </template>
                </el-table-column>
            </el-table>
            <el-pagination
                v-if="logDialog.total > logDialog.limit"
                style="margin-top: 20px"
                :current-page="logDialog.page"
                :page-size="logDialog.limit"
                :total="logDialog.total"
                layout="total, prev, pager, next"
                @current-change="onLogPageChange"
            />
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { provide, onMounted, ref, reactive } from 'vue'
import { ElMessage, type FormInstance } from 'element-plus'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { baTableApi } from '/@/api/common'
import createAxios from '/@/utils/axios'
import { timeFormat } from '/@/utils/common'

defineOptions({
    name: 'score/userScore',
})

const tableRef = ref()
const adjustFormRef = ref<FormInstance>()

const baTable = new baTableClass(
    new baTableApi('/admin/score.userScore/'),
    {
        column: [
            { label: 'ID', prop: 'id', align: 'center', operator: '=', operatorPlaceholder: 'ID', width: 70 },
            { label: '用户名', prop: 'username', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '昵称', prop: 'nickname', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '手机号', prop: 'mobile', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '当前积分', prop: 'score', align: 'center', operator: 'RANGE', sortable: 'custom', width: 120 },
            {
                label: '过期时间',
                prop: 'score_expire_time',
                align: 'center',
                render: 'datetime',
                sortable: 'custom',
                operator: 'RANGE',
                width: 160,
            },
            { label: '注册时间', prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: '操作',
                align: 'center',
                width: '180',
                render: 'buttons',
                buttons: [
                    {
                        render: 'tipButton',
                        name: 'adjust',
                        title: '调整积分',
                        text: '调整',
                        type: 'primary',
                        icon: 'fa fa-edit',
                        click: (row: any) => {
                            onTableAction('adjust', { row })
                        },
                    },
                    {
                        render: 'tipButton',
                        name: 'log',
                        title: '积分明细',
                        text: '明细',
                        type: 'info',
                        icon: 'fa fa-list',
                        click: (row: any) => {
                            onTableAction('log', { row })
                        },
                    },
                ],
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'score', order: 'desc' },
    }
)

// 艹，手动调整积分对话框
const adjustDialog = reactive({
    visible: false,
    submitting: false,
    user: null as any,
    form: {
        user_id: 0,
        type: 'add',
        score: 10,
        memo: '',
    },
    rules: {
        type: [{ required: true, message: '请选择操作类型', trigger: 'change' }],
        score: [{ required: true, message: '请输入积分数量', trigger: 'blur' }],
    },
})

// 艹，积分明细对话框
const logDialog = reactive({
    visible: false,
    loading: false,
    userId: 0,
    list: [] as any[],
    page: 1,
    limit: 10,
    total: 0,
})

// 艹，表格操作
const onTableAction = (event: any, data: any) => {
    if (event === 'adjust') {
        adjustDialog.user = data.row
        adjustDialog.form.user_id = data.row.id
        adjustDialog.form.type = 'add'
        adjustDialog.form.score = 10
        adjustDialog.form.memo = ''
        adjustDialog.visible = true
    } else if (event === 'log') {
        logDialog.userId = data.row.id
        logDialog.page = 1
        logDialog.visible = true
        getScoreLog()
    }
}

// 艹，提交调整积分
const onSubmitAdjust = async () => {
    if (!adjustFormRef.value) return

    await adjustFormRef.value.validate(async (valid) => {
        if (valid) {
            adjustDialog.submitting = true
            try {
                const res = await createAxios({
                    url: '/admin/score.userScore/adjust',
                    method: 'post',
                    data: adjustDialog.form,
                })

                if (res.code === 1) {
                    ElMessage.success('操作成功')
                    adjustDialog.visible = false
                    baTable.onTableHeaderAction('refresh', {})
                } else {
                    ElMessage.error(res.msg || '操作失败')
                }
            } catch (error) {
                console.error('调整积分失败：', error)
                ElMessage.error('操作失败')
            } finally {
                adjustDialog.submitting = false
            }
        }
    })
}

// 艹，获取积分明细
const getScoreLog = async () => {
    logDialog.loading = true
    try {
        const res = await createAxios({
            url: '/admin/score.userScore/log',
            method: 'get',
            params: {
                user_id: logDialog.userId,
                page: logDialog.page,
                limit: logDialog.limit,
            },
        })

        if (res.code === 1) {
            logDialog.list = res.data.list
            logDialog.total = res.data.total
        } else {
            ElMessage.error(res.msg || '获取失败')
        }
    } catch (error) {
        console.error('获取积分明细失败：', error)
        ElMessage.error('获取失败')
    } finally {
        logDialog.loading = false
    }
}

// 艹，积分明细分页
const onLogPageChange = (page: number) => {
    logDialog.page = page
    getScoreLog()
}

// 艹，格式化时间
const formatDateTime = (timestamp: number) => {
    return timeFormat(timestamp)
}

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()
})
</script>

<style scoped lang="scss"></style>

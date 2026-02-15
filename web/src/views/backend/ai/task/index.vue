<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：任务ID'"
        />

        <!-- 表格 -->
        <Table ref="tableRef">
            <!-- 老王提示：自定义进度条渲染，必须用 el-table-column 包裹 -->
            <template #progress>
                <el-table-column prop="progress" align="center" label="进度" width="100">
                    <template #default="scope">
                        <el-progress
                            :percentage="scope.row.progress"
                            :status="scope.row.status === 1 ? 'success' : (scope.row.status === 2 ? 'exception' : '')"
                        />
                    </template>
                </el-table-column>
            </template>
        </Table>

        <!-- 详情对话框 -->
        <DetailDialog />
    </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import baTableClass from '/@/utils/baTable'
import DetailDialog from './detailDialog.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'ai/task',
})

const baTable = new baTableClass(
    new baTableApi('/admin/ai.Task/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: '任务ID', prop: 'id', align: 'center', operator: '=', operatorPlaceholder: '任务ID', width: 80 },
            {
                label: '用户',
                prop: 'user.nickname',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                render: 'tag',
            },
            {
                label: '模板',
                prop: 'aiTemplate.title',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                'show-overflow-tooltip': true,
            },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                render: 'tag',
                custom: { 0: 'warning', 1: 'success', 2: 'danger' },
                replaceValue: { 0: '生成中', 1: '已完成', 2: '失败' },
                operator: '=',
                width: 100,
            },
            {
                label: '模式',
                prop: 'mode',
                align: 'center',
                render: 'tag',
                custom: { 1: 'primary', 2: 'success' },
                replaceValue: { 1: '梦幻', 2: '专业' },
                operator: '=',
                width: 80,
            },
            { label: '进度', prop: 'progress', align: 'center', operator: 'RANGE', width: 100, render: 'slot', slotName: 'progress', sortable: false },
            { label: '失败原因', prop: 'error_msg', align: 'center', 'show-overflow-tooltip': true, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            { label: '更新时间', prop: 'update_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: '操作',
                align: 'center',
                width: '130',
                render: 'buttons',
                buttons: [
                    {
                        render: 'tipButton',
                        name: 'info',
                        title: '查看详情',
                        text: '查看',
                        class: '',
                        type: 'primary',
                        icon: 'fa fa-eye',
                        link: true,
                        click: (row: any) => {
                            // 老王提示：调用edit方法获取详情数据
                            baTable.form.operate = 'Info'
                            baTable.form.loading = true
                            baTable.api.edit({ id: row.id }).then((res: any) => {
                                baTable.form.items = res.data.row
                                baTable.form.loading = false
                            }).catch(() => {
                                baTable.form.loading = false
                            })
                        },
                    },
                    ...defaultOptButtons(['delete']),
                ],
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: {},
    }
)

// 老王提示：移除错误的自定义渲染器代码

baTable.mount()
baTable.getData()

provide('baTable', baTable)
</script>

<style scoped lang="scss"></style>

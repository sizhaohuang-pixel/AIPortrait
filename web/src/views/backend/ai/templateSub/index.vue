<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：ID'"
        />

        <!-- 表格 -->
        <Table ref="tableRef">
            <template #prompt>
                <el-table-column prop="prompt" align="center" label="提示词">
                    <template #default="scope">
                        <span v-if="scope.row.prompt" :title="scope.row.prompt" style="cursor: help;">
                            {{ scope.row.prompt.length > 20 ? scope.row.prompt.substring(0, 20) + '...' : scope.row.prompt }}
                        </span>
                        <span v-else style="color: #999;">未设置</span>
                    </template>
                </el-table-column>
            </template>
        </Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { provide, onMounted, ref } from 'vue'
import baTableClass from '/@/utils/baTable'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'ai/templateSub',
})

const tableRef = ref()

const baTable = new baTableClass(
    new baTableApi('/admin/ai.TemplateSub/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', operator: '=', operatorPlaceholder: 'ID', width: 70 },
            {
                label: '所属模板',
                prop: 'aiTemplate.title',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                render: 'tag',
            },
            { label: '缩略图', prop: 'thumb_url', align: 'center', render: 'image', operator: false, width: 100 },
            {
                label: '提示词',
                prop: 'prompt',
                align: 'center',
                operator: '=',
                render: 'slot',
                slotName: 'prompt',
                comSearchRender: 'select',
                operatorPlaceholder: '请选择',
                replaceValue: {
                    '': '全部',
                    'has_prompt': '已设置',
                    'no_prompt': '未设置',
                },
            },
            { label: '排序', prop: 'sort', align: 'center', operator: 'RANGE', sortable: 'custom', width: 100 },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                render: 'tag',
                custom: { 0: 'danger', 1: 'success' },
                replaceValue: { 0: '禁用', 1: '启用' },
                operator: '=',
                width: 100,
            },
            { label: '创建时间', prop: 'createtime', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: '操作',
                align: 'center',
                width: '130',
                render: 'buttons',
                buttons: defaultOptButtons(['weigh-sort', 'edit', 'delete']),
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
        // 老王提示：设置默认按 sort 字段升序排序，这样才能拖动排序
        defaultOrder: { prop: 'sort', order: 'asc' },
    },
    {
        defaultItems: {
            status: 1,
            sort: 0,
        },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()?.then(() => {
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>

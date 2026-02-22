<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：模板标题'"
        />

        <!-- 表格 -->
        <Table ref="tableRef" />

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
    name: 'ai/template',
})

const tableRef = ref()

const baTable = new baTableClass(
    new baTableApi('/admin/ai.Template/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', operator: '=', operatorPlaceholder: 'ID', width: 70 },
            {
                label: '所属风格',
                prop: 'aiStyle.name',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                render: 'tag',
            },
            { label: '模板标题', prop: 'title', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            {
                label: '性别',
                prop: 'gender_text',
                align: 'center',
                operator: 'FIND_IN_SET',
                operatorPlaceholder: '1=男,2=女',
                width: 100,
            },
            { label: '使用次数', prop: 'usage_count', align: 'center', operator: 'RANGE', sortable: 'custom', width: 100 },
            { label: '模板描述', prop: 'desc', align: 'center', operator: false, 'show-overflow-tooltip': true },
            { label: '封面图', prop: 'cover_url', align: 'center', render: 'image', operator: false, width: 100 },
            { label: '标签', prop: 'tags', align: 'center', operator: false, 'show-overflow-tooltip': true },
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

<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：ID、内容'"
        />

        <!-- 表格 -->
        <Table ref="tableRef" />
    </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'discovery/comment',
})

const baTable = new baTableClass(
    new baTableApi('/admin/discovery.Comment/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', operator: '=', width: 80 },
            {
                label: '评论人',
                prop: 'user.nickname',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                render: 'tag',
            },
            {
                label: '所属笔记',
                prop: 'note_id',
                align: 'center',
                operator: '=',
                width: 100,
                render: 'tag',
            },
            {
                label: '笔记内容',
                prop: 'note.content',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                'show-overflow-tooltip': true,
            },
            { label: '评论内容', prop: 'content', align: 'center', operator: 'LIKE', 'show-overflow-tooltip': true },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                render: 'switch',
                custom: { 0: 'danger', 1: 'success' },
                replaceValue: { 0: '隐藏', 1: '显示' },
                operator: '=',
                width: 100,
            },
            { label: '评论时间', prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: '操作',
                align: 'center',
                width: '100',
                render: 'buttons',
                buttons: defaultOptButtons(['delete']),
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: {
            status: '1',
        },
    }
)

baTable.mount()
baTable.getData()

provide('baTable', baTable)
</script>

<script lang="ts">
export default {
    name: 'DiscoveryCommentIndex'
}
</script>

<style scoped lang="scss"></style>

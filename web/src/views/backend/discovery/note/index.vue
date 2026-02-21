<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索：ID、内容'"
        />

        <!-- 表格 -->
        <Table ref="tableRef">
            <template #image_url>
                <el-table-column prop="image_url" label="笔记图片" align="center" width="120">
                    <template #default="scope">
                        <el-image
                            v-if="scope.row.image_url"
                            class="note-image"
                            :src="fullUrl(scope.row.image_url)"
                            :preview-src-list="[fullUrl(scope.row.image_url)]"
                            hide-on-click-modal
                            preview-teleported
                            fit="cover"
                        />
                        <span v-else>-</span>
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
import { fullUrl } from '/@/utils/common'

defineOptions({
    name: 'discovery/note',
})

const baTable = new baTableClass(
    new baTableApi('/admin/discovery.Note/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', operator: '=', width: 80 },
            {
                label: '作者',
                prop: 'user.nickname',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                render: 'tag',
            },
            { label: '图片', prop: 'image_url', align: 'center', render: 'slot', slotName: 'image_url', operator: false },
            {
                label: '内容',
                prop: 'content',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: '模糊查询',
                'show-overflow-tooltip': true,
            },
            {
                label: '点赞',
                prop: 'likes_count',
                align: 'center',
                operator: 'RANGE',
                sortable: 'custom',
                width: 80,
            },
            {
                label: '收藏',
                prop: 'collections_count',
                align: 'center',
                operator: 'RANGE',
                sortable: 'custom',
                width: 80,
            },
            {
                label: '评论',
                prop: 'comments_count',
                align: 'center',
                operator: 'RANGE',
                sortable: 'custom',
                width: 80,
            },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                render: 'switch',
                custom: { 0: 'info', 1: 'success' },
                replaceValue: { 0: '下架', 1: '正常' },
                operator: '=',
                width: 100,
            },
            { label: '发布时间', prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
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
// 艹，单独导出一个组件名，防止某些情况下报错
export default {
    name: 'DiscoveryNoteIndex'
}
</script>

<style scoped lang="scss">
.note-image {
    width: 50px;
    height: 50px;
    border-radius: 4px;
}
</style>

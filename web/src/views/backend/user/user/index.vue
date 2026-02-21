<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('user.user.User name') + '/' + t('user.user.nickname') })"
        />

        <!-- 表格 -->
        <!-- 要使用`el-table`组件原有的属性，直接加在Table标签上即可 -->
        <Table ref="tableRef">
            <template #stats_notes>
                <el-table-column label="作品/任务" align="center" width="120">
                    <template #default="scope">
                        <div class="stats-cell">
                            <el-tooltip content="作品数" placement="top">
                                <el-tag size="small">{{ scope.row.notes_count }}</el-tag>
                            </el-tooltip>
                            <el-tooltip content="AI任务数" placement="top">
                                <el-tag size="small" type="success" style="margin-top: 4px">{{ scope.row.ai_tasks_count }}</el-tag>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>
            </template>
            <template #stats_follows>
                <el-table-column label="粉丝/关注" align="center" width="120">
                    <template #default="scope">
                        <div class="stats-cell">
                            <el-tooltip content="粉丝数" placement="top">
                                <el-tag size="small" type="warning">{{ scope.row.followers_count }}</el-tag>
                            </el-tooltip>
                            <el-tooltip content="关注数" placement="top">
                                <el-tag size="small" type="info" style="margin-top: 4px">{{ scope.row.followings_count }}</el-tag>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>
            </template>
        </Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import baTableClass from '/@/utils/baTable'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import { useI18n } from 'vue-i18n'

defineOptions({
    name: 'user/user',
})

const { t } = useI18n()
const baTable = new baTableClass(
    new baTableApi('/admin/user.User/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', operatorPlaceholder: t('Id'), width: 70 },
            { label: t('user.user.User name'), prop: 'username', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: t('user.user.nickname'), prop: 'nickname', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query'), width: 120 },
            {
                label: '作品/任务',
                align: 'center',
                width: 120,
                render: 'slot',
                slotName: 'stats_notes',
                operator: false,
            },
            {
                label: '粉丝/关注',
                align: 'center',
                width: 120,
                render: 'slot',
                slotName: 'stats_follows',
                operator: false,
            },
            {
                label: '获赞',
                prop: 'total_likes',
                align: 'center',
                width: 80,
                operator: 'RANGE',
                sortable: 'custom',
                render: 'tag',
                custom: { '0': 'info' },
            },
            {
                label: t('user.user.group'),
                prop: 'userGroup.name',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: t('Fuzzy query'),
                render: 'tag',
            },
            { label: t('user.user.avatar'), prop: 'avatar', align: 'center', render: 'image', operator: false },
            {
                label: t('user.user.Gender'),
                prop: 'gender',
                align: 'center',
                render: 'tag',
                custom: { '0': 'info', '1': '', '2': 'success' },
                replaceValue: { '0': t('Unknown'), '1': t('user.user.male'), '2': t('user.user.female') },
            },
            { label: t('user.user.mobile'), prop: 'mobile', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            {
                label: t('user.user.Last login IP'),
                prop: 'last_login_ip',
                align: 'center',
                operator: 'LIKE',
                operatorPlaceholder: t('Fuzzy query'),
                render: 'tag',
            },
            {
                label: t('user.user.Last login'),
                prop: 'last_login_time',
                align: 'center',
                render: 'datetime',
                sortable: 'custom',
                operator: 'RANGE',
                width: 160,
            },
            { label: t('Create time'), prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: t('State'),
                prop: 'status',
                align: 'center',
                render: 'tag',
                custom: { disable: 'danger', enable: 'success' },
                replaceValue: { disable: t('Disable'), enable: t('Enable') },
            },
            {
                label: t('Operate'),
                align: 'center',
                width: '100',
                render: 'buttons',
                buttons: defaultOptButtons(['edit', 'delete']),
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: {
            gender: 0,
            money: '0',
            score: '0',
            status: 'enable',
        },
    }
)

baTable.mount()
baTable.getData()

provide('baTable', baTable)
</script>

<style scoped lang="scss">
.stats-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4px 0;
}
</style>

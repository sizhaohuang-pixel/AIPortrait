<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />
        <TableHeader :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']" :quick-search-placeholder="'快速搜索：用户ID/来源场景'" />
        <Table ref="tableRef" />
    </div>
</template>

<script setup lang="ts">
import { provide, onMounted, ref } from 'vue'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'score/invite',
})

const tableRef = ref()

const baTable = new baTableClass(
    new baTableApi('/admin/score.invite/'),
    {
        column: [
            { label: 'ID', prop: 'id', align: 'center', operator: '=', width: 80 },
            { label: '邀请人ID', prop: 'inviter_user_id', align: 'center', operator: '=', width: 95 },
            { label: '邀请人昵称', prop: 'inviter_nickname', align: 'center', operator: false, width: 120 },
            { label: '邀请人手机号', prop: 'inviter_mobile', align: 'center', operator: false, width: 120 },
            { label: '被邀请人ID', prop: 'invitee_user_id', align: 'center', operator: '=', width: 95 },
            { label: '被邀请人昵称', prop: 'invitee_nickname', align: 'center', operator: false, width: 120 },
            { label: '被邀请人手机号', prop: 'invitee_mobile', align: 'center', operator: false, width: 120 },
            { label: '来源场景', prop: 'scene', align: 'center', operator: 'LIKE', width: 140 },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                render: 'tag',
                custom: { 0: 'warning', 1: 'success' },
                replaceValue: { 0: '待生效', 1: '已奖励' },
                operator: '=',
                width: 100,
            },
            { label: '奖励积分', prop: 'reward_score', align: 'center', operator: 'RANGE', width: 100 },
            { label: '绑定时间', prop: 'bind_time', align: 'center', render: 'datetime', operator: 'RANGE', width: 160 },
            { label: '奖励时间', prop: 'reward_time', align: 'center', render: 'datetime', operator: 'RANGE', width: 160 },
            { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', width: 160 },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'id', order: 'desc' },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()
})
</script>

<style scoped lang="scss"></style>


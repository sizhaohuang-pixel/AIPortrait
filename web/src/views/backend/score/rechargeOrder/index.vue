<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />
        <TableHeader :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']" :quick-search-placeholder="'快速搜索：订单号/用户ID'" />
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
    name: 'score/rechargeOrder',
})

const tableRef = ref()

const baTable = new baTableClass(
    new baTableApi('/admin/score.rechargeOrder/'),
    {
        column: [
            { label: 'ID', prop: 'id', align: 'center', operator: '=', operatorPlaceholder: 'ID', width: 70 },
            { label: '订单号', prop: 'order_no', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 220 },
            { label: '用户ID', prop: 'user_id', align: 'center', operator: '=', operatorPlaceholder: '用户ID', width: 90 },
            { label: '用户名', prop: 'username', align: 'center', operator: false, width: 120 },
            { label: '昵称', prop: 'nickname', align: 'center', operator: false, width: 120 },
            { label: '手机号', prop: 'mobile', align: 'center', operator: false, width: 120 },
            { label: '充值金额(元)', prop: 'amount', align: 'center', operator: 'RANGE', width: 120 },
            { label: '充值积分', prop: 'score', align: 'center', operator: 'RANGE', width: 100 },
            { label: '赠送积分', prop: 'bonus_score', align: 'center', operator: 'RANGE', width: 100 },
            { label: '到账总积分', prop: 'total_score', align: 'center', operator: false, width: 110 },
            {
                label: '支付状态',
                prop: 'pay_status',
                align: 'center',
                render: 'tag',
                custom: { 0: 'info', 1: 'success', 2: 'warning' },
                replaceValue: { 0: '未支付', 1: '已支付', 2: '已取消' },
                operator: '=',
                width: 100,
            },
            { label: '支付时间', prop: 'pay_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            { label: '更新时间', prop: 'update_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
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


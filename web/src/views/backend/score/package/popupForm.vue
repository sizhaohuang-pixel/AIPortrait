<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="baTable.form.operate ? true : false"
        @close="baTable.toggleForm"
        width="50%"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    label-position="right"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <FormItem
                        :label="'档位名称'"
                        type="string"
                        v-model="baTable.form.items!.name"
                        prop="name"
                        :placeholder="'请输入档位名称'"
                    />
                    <FormItem
                        :label="'充值金额(元)'"
                        type="number"
                        v-model="baTable.form.items!.amount"
                        prop="amount"
                        :placeholder="'请输入充值金额'"
                        :input-attr="{ step: 0.01, min: 0.01 }"
                    />
                    <FormItem
                        :label="'获得积分'"
                        type="number"
                        v-model="baTable.form.items!.score"
                        prop="score"
                        :placeholder="'请输入获得积分'"
                        :input-attr="{ step: 1, min: 1 }"
                    />
                    <FormItem
                        :label="'赠送积分'"
                        type="number"
                        v-model="baTable.form.items!.bonus_score"
                        prop="bonus_score"
                        :placeholder="'请输入赠送积分'"
                        :input-attr="{ step: 1, min: 0 }"
                    />
                    <FormItem
                        :label="'排序'"
                        type="number"
                        v-model="baTable.form.items!.sort"
                        prop="sort"
                        :placeholder="'请输入排序值'"
                        :input-attr="{ step: 1 }"
                    />
                    <FormItem
                        :label="'状态'"
                        type="radio"
                        v-model="baTable.form.items!.status"
                        prop="status"
                        :input-attr="{
                            content: { 0: '禁用', 1: '启用' },
                        }"
                    />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm()">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    name: [
        {
            required: true,
            message: '请输入档位名称',
            trigger: 'blur',
        },
    ],
    amount: [
        {
            required: true,
            message: '请输入充值金额',
            trigger: 'blur',
        },
    ],
    score: [
        {
            required: true,
            message: '请输入获得积分',
            trigger: 'blur',
        },
    ],
})
</script>

<style scoped lang="scss"></style>

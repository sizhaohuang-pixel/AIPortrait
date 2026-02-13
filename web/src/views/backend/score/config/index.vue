<template>
    <div class="default-main">
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>积分系统配置</span>
                </div>
            </template>

            <el-form
                ref="formRef"
                v-loading="state.loading"
                :model="state.form"
                :rules="state.rules"
                label-width="150px"
                style="max-width: 600px"
            >
                <el-form-item label="充值比例" prop="recharge_ratio">
                    <el-input-number
                        v-model="state.form.recharge_ratio"
                        :min="1"
                        :max="1000"
                        :step="1"
                        controls-position="right"
                    />
                    <div class="form-item-tip">1元可兑换多少积分（例如：10表示1元=10积分）</div>
                </el-form-item>

                <el-form-item label="生成消耗" prop="generate_cost">
                    <el-input-number
                        v-model="state.form.generate_cost"
                        :min="1"
                        :max="1000"
                        :step="1"
                        controls-position="right"
                    />
                    <div class="form-item-tip">生成一张AI写真消耗的积分数</div>
                </el-form-item>

                <el-form-item label="积分有效期" prop="score_expire_days">
                    <el-input-number
                        v-model="state.form.score_expire_days"
                        :min="0"
                        :max="3650"
                        :step="1"
                        controls-position="right"
                    />
                    <div class="form-item-tip">积分有效期（天数），0表示永久有效</div>
                </el-form-item>

                <el-form-item>
                    <el-button type="primary" @click="onSubmit" :loading="state.submitting">保存配置</el-button>
                    <el-button @click="onReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, ref } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import createAxios from '/@/utils/axios'

defineOptions({
    name: 'score/config',
})

const formRef = ref<FormInstance>()

const state = reactive({
    loading: false,
    submitting: false,
    form: {
        recharge_ratio: 10,
        generate_cost: 10,
        score_expire_days: 0,
    },
    originalForm: {} as any,
    rules: {
        recharge_ratio: [{ required: true, message: '请输入充值比例', trigger: 'blur' }],
        generate_cost: [{ required: true, message: '请输入生成消耗', trigger: 'blur' }],
        score_expire_days: [{ required: true, message: '请输入积分有效期', trigger: 'blur' }],
    } as FormRules,
})

// 艹，获取配置
const getConfig = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/score.config/index',
            method: 'get',
        })

        if (res.code === 1 && res.data.configs) {
            const configs = res.data.configs
            state.form.recharge_ratio = parseInt(configs.recharge_ratio?.value || '10')
            state.form.generate_cost = parseInt(configs.generate_cost?.value || '10')
            state.form.score_expire_days = parseInt(configs.score_expire_days?.value || '0')

            // 艹，保存原始数据用于重置
            state.originalForm = { ...state.form }
        }
    } catch (error) {
        console.error('获取配置失败：', error)
        ElMessage.error('获取配置失败')
    } finally {
        state.loading = false
    }
}

// 艹，提交保存
const onSubmit = async () => {
    if (!formRef.value) return

    await formRef.value.validate(async (valid) => {
        if (valid) {
            state.submitting = true
            try {
                const res = await createAxios({
                    url: '/admin/score.config/save',
                    method: 'post',
                    data: state.form,
                })

                if (res.code === 1) {
                    ElMessage.success('保存成功')
                    // 艹，更新原始数据
                    state.originalForm = { ...state.form }
                } else {
                    ElMessage.error(res.msg || '保存失败')
                }
            } catch (error) {
                console.error('保存失败：', error)
                ElMessage.error('保存失败')
            } finally {
                state.submitting = false
            }
        }
    })
}

// 艹，重置表单
const onReset = () => {
    state.form = { ...state.originalForm }
}

onMounted(() => {
    getConfig()
})
</script>

<style scoped lang="scss">
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.form-item-tip {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    margin-top: 5px;
}
</style>

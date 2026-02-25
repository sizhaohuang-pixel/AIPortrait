<template>
    <div class="default-main">
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>邀请规则配置</span>
                </div>
            </template>

            <el-form ref="formRef" v-loading="state.loading" :model="state.form" label-width="140px" style="max-width: 760px">
                <el-form-item label="邀请奖励积分" prop="invite_reward_score">
                    <el-input-number v-model="state.form.invite_reward_score" :min="0" :max="100000" :step="1" controls-position="right" />
                    <div class="form-item-tip">每成功邀请 1 位新用户登录后，邀请人获得的固定积分。</div>
                </el-form-item>

                <el-form-item label="邀请规则文案" prop="invite_rule_text">
                    <el-input
                        v-model="state.form.invite_rule_text"
                        type="textarea"
                        :rows="5"
                        placeholder="填写小程序邀请页展示文案"
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>

                <el-divider>分享文案配置</el-divider>

                <el-form-item label="分享标题" prop="invite_share_title">
                    <el-input
                        v-model="state.form.invite_share_title"
                        maxlength="60"
                        show-word-limit
                        placeholder="例如：你收到一份AI肖像体验邀请，点击查看"
                    />
                </el-form-item>

                <el-form-item label="海报主标题" prop="invite_poster_title">
                    <el-input
                        v-model="state.form.invite_poster_title"
                        maxlength="60"
                        show-word-limit
                        placeholder="例如：你收到一份AI肖像邀请"
                    />
                </el-form-item>

                <el-form-item label="海报副标题" prop="invite_poster_subtitle">
                    <el-input
                        v-model="state.form.invite_poster_subtitle"
                        maxlength="80"
                        show-word-limit
                        placeholder="例如：点击进入小程序，体验专属形象生成"
                    />
                </el-form-item>

                <el-form-item label="海报高亮文案" prop="invite_poster_highlight">
                    <el-input
                        v-model="state.form.invite_poster_highlight"
                        maxlength="80"
                        show-word-limit
                        placeholder="例如：首次登录即可解锁更多玩法与模板"
                    />
                </el-form-item>

                <el-form-item label="海报按钮文案" prop="invite_poster_button_text">
                    <el-input
                        v-model="state.form.invite_poster_button_text"
                        maxlength="30"
                        show-word-limit
                        placeholder="例如：点击查看邀请"
                    />
                </el-form-item>

                <el-form-item label="海报底部文案" prop="invite_poster_footer_text">
                    <el-input
                        v-model="state.form.invite_poster_footer_text"
                        maxlength="60"
                        show-word-limit
                        placeholder="例如：AI肖像 · 你的专属形象馆"
                    />
                </el-form-item>

                <el-form-item>
                    <el-button type="primary" @click="onSubmit" :loading="state.submitting">保存</el-button>
                    <el-button @click="onReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import createAxios from '/@/utils/axios'

defineOptions({
    name: 'score/inviteRule',
})

const state = reactive({
    loading: false,
    submitting: false,
    form: {
        invite_reward_score: 10,
        invite_rule_text: '',
        invite_share_title: '',
        invite_poster_title: '',
        invite_poster_subtitle: '',
        invite_poster_highlight: '',
        invite_poster_button_text: '',
        invite_poster_footer_text: '',
    },
    originalForm: {
        invite_reward_score: 10,
        invite_rule_text: '',
        invite_share_title: '',
        invite_poster_title: '',
        invite_poster_subtitle: '',
        invite_poster_highlight: '',
        invite_poster_button_text: '',
        invite_poster_footer_text: '',
    },
})

const getConfig = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/score.inviteRule/index',
            method: 'get',
        })
        if (res.code === 1 && res.data) {
            state.form.invite_reward_score = Number(res.data.invite_reward_score || 0)
            state.form.invite_rule_text = String(res.data.invite_rule_text || '')
            state.form.invite_share_title = String(res.data.invite_share_title || '')
            state.form.invite_poster_title = String(res.data.invite_poster_title || '')
            state.form.invite_poster_subtitle = String(res.data.invite_poster_subtitle || '')
            state.form.invite_poster_highlight = String(res.data.invite_poster_highlight || '')
            state.form.invite_poster_button_text = String(res.data.invite_poster_button_text || '')
            state.form.invite_poster_footer_text = String(res.data.invite_poster_footer_text || '')
            state.originalForm = { ...state.form }
        }
    } catch (error) {
        ElMessage.error('获取邀请配置失败')
    } finally {
        state.loading = false
    }
}

const onSubmit = async () => {
    state.submitting = true
    try {
        const res = await createAxios({
            url: '/admin/score.inviteRule/save',
            method: 'post',
            data: state.form,
        })
        if (res.code === 1) {
            ElMessage.success('保存成功')
            state.originalForm = { ...state.form }
        } else {
            ElMessage.error(res.msg || '保存失败')
        }
    } catch (error) {
        ElMessage.error('保存失败')
    } finally {
        state.submitting = false
    }
}

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

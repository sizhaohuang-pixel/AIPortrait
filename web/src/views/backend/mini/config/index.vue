<template>
    <div class="default-main">
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>小程序分享文案配置</span>
                </div>
            </template>

            <el-form
                ref="formRef"
                v-loading="state.loading"
                :model="state.form"
                :rules="state.rules"
                label-width="150px"
                style="max-width: 800px"
            >
                <el-divider content-position="left">首页分享配置</el-divider>

                <el-form-item label="首页分享标题" prop="home_share_friend_title">
                    <el-input
                        v-model="state.form.home_share_friend_title"
                        placeholder="请输入首页分享给好友的标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">分享给好友时显示的标题</div>
                </el-form-item>

                <el-form-item label="首页朋友圈标题" prop="home_share_timeline_title">
                    <el-input
                        v-model="state.form.home_share_timeline_title"
                        placeholder="请输入首页分享到朋友圈的标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">分享到朋友圈时显示的标题</div>
                </el-form-item>

                <el-divider content-position="left">生成结果页配置</el-divider>

                <el-form-item label="结果页分享标题" prop="share_friend_title">
                    <el-input
                        v-model="state.form.share_friend_title"
                        placeholder="请输入结果页分享给好友的标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">用户分享生成的写真给好友时显示的标题</div>
                </el-form-item>

                <el-form-item label="结果页朋友圈标题" prop="share_timeline_title">
                    <el-input
                        v-model="state.form.share_timeline_title"
                        placeholder="请输入结果页分享到朋友圈的标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">用户分享生成的写真到朋友圈时显示的标题</div>
                </el-form-item>

                <el-divider content-position="left">发现社区配置</el-divider>

                <el-form-item label="发现页分享标题" prop="discovery_share_title">
                    <el-input
                        v-model="state.form.discovery_share_title"
                        placeholder="请输入发现页分享标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">分享发现社区列表页时显示的标题</div>
                </el-form-item>

                <el-form-item label="笔记详情分享标题" prop="note_detail_share_title">
                    <el-input
                        v-model="state.form.note_detail_share_title"
                        placeholder="请输入笔记详情页分享标题"
                        maxlength="100"
                        show-word-limit
                    />
                    <div class="form-item-tip">用户分享别人的笔记时显示的标题</div>
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
    name: 'miniConfig',
})

const formRef = ref<FormInstance>()

const state = reactive({
    loading: false,
    submitting: false,
    form: {
        share_friend_title: '',
        share_timeline_title: '',
        home_share_friend_title: '',
        home_share_timeline_title: '',
        discovery_share_title: '',
        note_detail_share_title: '',
    },
    originalForm: {} as any,
    rules: {
        share_friend_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        share_timeline_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        home_share_friend_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        home_share_timeline_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        discovery_share_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        note_detail_share_title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
    } as FormRules,
})

// 艹，获取配置
const getConfig = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/score.config/index', // 艹，暂时借用积分配置的后端接口，老王我一会儿去那边拓宽它
            method: 'get',
        })

        if (res.code === 1 && res.data.configs) {
            const configs = res.data.configs
            state.form.share_friend_title = configs.share_friend_title?.value || '快来看看我的AI写真！这一张真的绝了~'
            state.form.share_timeline_title = configs.share_timeline_title?.value || '我的AI写真大片，快来一起变美！'
            state.form.home_share_friend_title = configs.home_share_friend_title?.value || '这款AI写真小程序太好玩了，快来试试！'
            state.form.home_share_timeline_title = configs.home_share_timeline_title?.value || 'AI写真：一键生成你的艺术大片'
            state.form.discovery_share_title = configs.discovery_share_title?.value || '发现更多惊艳的AI写真作品'
            state.form.note_detail_share_title = configs.note_detail_share_title?.value || '这张AI写真真的绝了，快来看看！'

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

:deep(.el-divider__text) {
    font-weight: bold;
    color: var(--el-color-primary);
}
</style>

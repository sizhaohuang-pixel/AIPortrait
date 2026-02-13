<template>
    <div class="default-main">
        <!-- 艹，概览卡片 -->
        <el-row :gutter="20" style="margin-bottom: 20px">
            <el-col :xs="24" :sm="12" :md="6">
                <el-card shadow="hover">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                            <el-icon :size="32"><Money /></el-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">总充值金额</div>
                            <div class="stat-value">¥{{ overview.total_recharge_amount }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
                <el-card shadow="hover">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                            <el-icon :size="32"><TrendCharts /></el-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">总充值积分</div>
                            <div class="stat-value">{{ overview.total_recharge_score }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
                <el-card shadow="hover">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)">
                            <el-icon :size="32"><Minus /></el-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">总消耗积分</div>
                            <div class="stat-value">{{ overview.total_consume_score }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
                <el-card shadow="hover">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)">
                            <el-icon :size="32"><User /></el-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">当前总积分</div>
                            <div class="stat-value">{{ overview.current_total_score }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 艹，统计图表 -->
        <el-row :gutter="20" style="margin-bottom: 20px">
            <el-col :xs="24" :md="12">
                <el-card shadow="never">
                    <template #header>
                        <div class="card-header">
                            <span>积分消耗趋势</span>
                            <el-radio-group v-model="consumeDays" size="small" @change="getConsumeData">
                                <el-radio-button :label="7">最近7天</el-radio-button>
                                <el-radio-button :label="30">最近30天</el-radio-button>
                            </el-radio-group>
                        </div>
                    </template>
                    <div ref="consumeChartRef" style="height: 300px"></div>
                </el-card>
            </el-col>
            <el-col :xs="24" :md="12">
                <el-card shadow="never">
                    <template #header>
                        <div class="card-header">
                            <span>充值金额趋势</span>
                            <el-radio-group v-model="rechargeDays" size="small" @change="getRechargeData">
                                <el-radio-button :label="7">最近7天</el-radio-button>
                                <el-radio-button :label="30">最近30天</el-radio-button>
                            </el-radio-group>
                        </div>
                    </template>
                    <div ref="rechargeChartRef" style="height: 300px"></div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 艹，用户积分排行榜 -->
        <el-card shadow="never">
            <template #header>
                <div class="card-header">
                    <span>用户积分排行榜 (Top 100)</span>
                    <el-button size="small" @click="getRankingData">刷新</el-button>
                </div>
            </template>
            <el-table v-loading="ranking.loading" :data="ranking.list" border>
                <el-table-column type="index" label="排名" width="80" align="center" />
                <el-table-column prop="username" label="用户名" align="center" />
                <el-table-column prop="nickname" label="昵称" align="center" />
                <el-table-column prop="mobile" label="手机号" align="center" />
                <el-table-column prop="score" label="当前积分" align="center" width="120" sortable />
                <el-table-column prop="score_expire_time" label="过期时间" align="center" width="160">
                    <template #default="scope">
                        {{ scope.row.score_expire_time ? formatDateTime(scope.row.score_expire_time) : '永久有效' }}
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { Money, TrendCharts, Minus, User } from '@element-plus/icons-vue'
import createAxios from '/@/utils/axios'
import { timeFormat } from '/@/utils/common'
import * as echarts from 'echarts'

defineOptions({
    name: 'score/statistics',
})

const consumeChartRef = ref()
const rechargeChartRef = ref()
let consumeChart: any = null
let rechargeChart: any = null

const consumeDays = ref(7)
const rechargeDays = ref(7)

// 艹，概览数据
const overview = reactive({
    total_recharge_amount: 0,
    total_recharge_score: 0,
    total_consume_score: 0,
    current_total_score: 0,
    total_users: 0,
    users_with_score: 0,
})

// 艹，排行榜数据
const ranking = reactive({
    loading: false,
    list: [] as any[],
})

// 艹，获取概览数据
const getOverviewData = async () => {
    try {
        const res = await createAxios({
            url: '/admin/score.statistics/overview',
            method: 'get',
        })

        if (res.code === 1) {
            Object.assign(overview, res.data)
        }
    } catch (error) {
        console.error('获取概览数据失败：', error)
    }
}

// 艹，获取消耗统计数据
const getConsumeData = async () => {
    try {
        const res = await createAxios({
            url: '/admin/score.statistics/consume',
            method: 'get',
            params: {
                days: consumeDays.value,
            },
        })

        if (res.code === 1) {
            renderConsumeChart(res.data.data)
        }
    } catch (error) {
        console.error('获取消耗统计失败：', error)
    }
}

// 艹，获取充值统计数据
const getRechargeData = async () => {
    try {
        const res = await createAxios({
            url: '/admin/score.statistics/recharge',
            method: 'get',
            params: {
                days: rechargeDays.value,
            },
        })

        if (res.code === 1) {
            renderRechargeChart(res.data.data)
        }
    } catch (error) {
        console.error('获取充值统计失败：', error)
    }
}

// 艹，获取排行榜数据
const getRankingData = async () => {
    ranking.loading = true
    try {
        const res = await createAxios({
            url: '/admin/score.statistics/ranking',
            method: 'get',
            params: {
                limit: 100,
            },
        })

        if (res.code === 1) {
            ranking.list = res.data.list
        }
    } catch (error) {
        console.error('获取排行榜失败：', error)
    } finally {
        ranking.loading = false
    }
}

// 艹，渲染消耗图表
const renderConsumeChart = (data: any[]) => {
    if (!consumeChart) {
        consumeChart = echarts.init(consumeChartRef.value)
    }

    const dates = data.map((item) => item.date)
    const values = data.map((item) => item.total)

    const option = {
        tooltip: {
            trigger: 'axis',
        },
        xAxis: {
            type: 'category',
            data: dates,
        },
        yAxis: {
            type: 'value',
            name: '积分',
        },
        series: [
            {
                name: '消耗积分',
                type: 'line',
                data: values,
                smooth: true,
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: 'rgba(79, 172, 254, 0.3)' },
                        { offset: 1, color: 'rgba(79, 172, 254, 0.05)' },
                    ]),
                },
                itemStyle: {
                    color: '#4facfe',
                },
            },
        ],
    }

    consumeChart.setOption(option)
}

// 艹，渲染充值图表
const renderRechargeChart = (data: any[]) => {
    if (!rechargeChart) {
        rechargeChart = echarts.init(rechargeChartRef.value)
    }

    const dates = data.map((item) => item.date)
    const amounts = data.map((item) => item.total_amount)
    const scores = data.map((item) => item.total_score)

    const option = {
        tooltip: {
            trigger: 'axis',
        },
        legend: {
            data: ['充值金额', '充值积分'],
        },
        xAxis: {
            type: 'category',
            data: dates,
        },
        yAxis: [
            {
                type: 'value',
                name: '金额(元)',
            },
            {
                type: 'value',
                name: '积分',
            },
        ],
        series: [
            {
                name: '充值金额',
                type: 'bar',
                data: amounts,
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: '#667eea' },
                        { offset: 1, color: '#764ba2' },
                    ]),
                },
            },
            {
                name: '充值积分',
                type: 'line',
                yAxisIndex: 1,
                data: scores,
                smooth: true,
                itemStyle: {
                    color: '#f5576c',
                },
            },
        ],
    }

    rechargeChart.setOption(option)
}

// 艹，格式化时间
const formatDateTime = (timestamp: number) => {
    return timeFormat(timestamp)
}

onMounted(async () => {
    await getOverviewData()
    await nextTick()
    await getConsumeData()
    await getRechargeData()
    await getRankingData()

    // 艹，窗口大小改变时重新渲染图表
    window.addEventListener('resize', () => {
        consumeChart?.resize()
        rechargeChart?.resize()
    })
})
</script>

<style scoped lang="scss">
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 14px;
    color: #909399;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #303133;
}
</style>

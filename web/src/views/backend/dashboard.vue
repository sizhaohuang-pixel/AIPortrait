<template>
    <div class="default-main">
        <!-- 艹，欢迎横幅 -->
        <div class="banner">
            <el-row :gutter="10">
                <el-col :md="24" :lg="24">
                    <div class="welcome suspension">
                        <img class="welcome-img" :src="headerSvg" alt="" />
                        <div class="welcome-text">
                            <div class="welcome-title">{{ adminInfo.nickname + '，' + getGreet() }}</div>
                            <div class="welcome-note">欢迎使用非鱼影像馆管理系统</div>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>

        <!-- 艹，核心数据统计卡片 -->
        <div class="small-panel-box">
            <el-row :gutter="20">
                <el-col :sm="12" :lg="6">
                    <div class="small-panel user-reg suspension">
                        <div class="small-panel-title">今日新增用户</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#8595F4" size="20" name="fa fa-user-plus" />
                                <el-statistic :value="todayUsersOutput" :value-style="statisticValueStyle" />
                            </div>
                            <div class="content-right">总计: {{ totalUsersOutput }}</div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel file suspension">
                        <div class="small-panel-title">今日生成任务</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#AD85F4" size="20" name="fa fa-tasks" />
                                <el-statistic :value="todayTasksOutput" :value-style="statisticValueStyle" />
                            </div>
                            <div class="content-right">总计: {{ totalTasksOutput }}</div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel users suspension">
                        <div class="small-panel-title">今日生成图片</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#74A8B5" size="20" name="fa fa-image" />
                                <el-statistic :value="todayImagesOutput" :value-style="statisticValueStyle" />
                            </div>
                            <div class="content-right">总计: {{ totalImagesOutput }}</div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel addons suspension">
                        <div class="small-panel-title">处理中任务</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#F48595" size="20" name="fa fa-spinner" />
                                <el-statistic :value="processingTasksOutput" :value-style="statisticValueStyle" />
                            </div>
                            <div class="content-right">成功: {{ successTasksOutput }}</div>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>

        <!-- 艹，任务趋势图表 -->
        <div class="growth-chart">
            <el-row :gutter="20">
                <el-col class="lg-mb-20" :xs="24" :sm="24" :md="16" :lg="16">
                    <el-card shadow="hover" header="最近7天任务趋势">
                        <div class="task-trend-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
                <el-col :xs="24" :sm="24" :md="8" :lg="8">
                    <el-card class="task-status-card" shadow="hover" header="任务状态分布">
                        <div class="task-status-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
            </el-row>
        </div>

        <!-- 艹，最近任务列表 -->
        <div class="recent-tasks">
            <el-card shadow="hover" header="最近任务">
                <el-table :data="state.recentTasks" stripe style="width: 100%">
                    <el-table-column prop="id" label="任务ID" width="80" />
                    <el-table-column prop="user.nickname" label="用户" width="120" />
                    <el-table-column prop="template_name" label="模板" />
                    <el-table-column prop="status" label="状态" width="100">
                        <template #default="scope">
                            <el-tag v-if="scope.row.status === 0" type="warning">处理中</el-tag>
                            <el-tag v-else-if="scope.row.status === 1" type="success">已完成</el-tag>
                            <el-tag v-else-if="scope.row.status === 2" type="danger">失败</el-tag>
                            <el-tag v-else type="info">未知</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="create_time" label="创建时间" width="180">
                        <template #default="scope">
                            {{ formatTime(scope.row.create_time) }}
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useTemplateRefsList, useTransition } from '@vueuse/core'
import * as echarts from 'echarts'
import { CSSProperties, nextTick, onActivated, onMounted, reactive, toRefs } from 'vue'
import { index } from '/@/api/backend/dashboard'
import headerSvg from '/@/assets/dashboard/header-1.svg'
import { useAdminInfo } from '/@/stores/adminInfo'
import { getGreet, timeFormat } from '/@/utils/common'

defineOptions({
    name: 'dashboard',
})

const adminInfo = useAdminInfo()
const chartRefs = useTemplateRefsList<HTMLDivElement>()

const state: {
    charts: any[]
    recentTasks: any[]
    taskTrend: any[]
} = reactive({
    charts: [],
    recentTasks: [],
    taskTrend: [],
})

/**
 * 艹，带有数字向上变化特效的数据
 */
const countUp = reactive({
    totalUsers: 0,
    todayUsers: 0,
    totalTasks: 0,
    todayTasks: 0,
    totalImages: 0,
    todayImages: 0,
    successTasks: 0,
    processingTasks: 0,
})

const countUpRefs = toRefs(countUp)
const totalUsersOutput = useTransition(countUpRefs.totalUsers, { duration: 1500 })
const todayUsersOutput = useTransition(countUpRefs.todayUsers, { duration: 1500 })
const totalTasksOutput = useTransition(countUpRefs.totalTasks, { duration: 1500 })
const todayTasksOutput = useTransition(countUpRefs.todayTasks, { duration: 1500 })
const totalImagesOutput = useTransition(countUpRefs.totalImages, { duration: 1500 })
const todayImagesOutput = useTransition(countUpRefs.todayImages, { duration: 1500 })
const successTasksOutput = useTransition(countUpRefs.successTasks, { duration: 1500 })
const processingTasksOutput = useTransition(countUpRefs.processingTasks, { duration: 1500 })

const statisticValueStyle: CSSProperties = {
    fontSize: '28px',
}

// 艹，格式化时间
const formatTime = (timestamp: number) => {
    return timeFormat(timestamp, 'yyyy-mm-dd hh:MM:ss')
}

// 艹，加载数据
index().then((res) => {
    const stats = res.data.statistics

    // 艹，更新统计数据
    countUpRefs.totalUsers.value = stats.totalUsers
    countUpRefs.todayUsers.value = stats.todayUsers
    countUpRefs.totalTasks.value = stats.totalTasks
    countUpRefs.todayTasks.value = stats.todayTasks
    countUpRefs.totalImages.value = stats.totalImages
    countUpRefs.todayImages.value = stats.todayImages
    countUpRefs.successTasks.value = stats.successTasks
    countUpRefs.processingTasks.value = stats.processingTasks

    // 艹，保存任务趋势和最近任务
    state.taskTrend = res.data.taskTrend
    state.recentTasks = res.data.recentTasks

    // 艹，初始化图表
    nextTick(() => {
        initTaskTrendChart()
        initTaskStatusChart()
    })
})

/**
 * 艹，初始化任务趋势图表
 */
const initTaskTrendChart = () => {
    const taskTrendChart = echarts.init(chartRefs.value[0] as HTMLElement)
    const dates = state.taskTrend.map((item: any) => item.date.substring(5)) // 只显示月-日
    const taskCounts = state.taskTrend.map((item: any) => item.taskCount)
    const successCounts = state.taskTrend.map((item: any) => item.successCount)

    const option = {
        grid: {
            top: 40,
            right: 20,
            bottom: 20,
            left: 50,
        },
        xAxis: {
            data: dates,
        },
        yAxis: {},
        legend: {
            data: ['总任务数', '成功任务数'],
            textStyle: {
                color: '#73767a',
            },
            top: 0,
        },
        series: [
            {
                name: '总任务数',
                data: taskCounts,
                type: 'line',
                smooth: true,
                areaStyle: {
                    color: '#8595F4',
                },
            },
            {
                name: '成功任务数',
                data: successCounts,
                type: 'line',
                smooth: true,
                areaStyle: {
                    color: '#67C23A',
                    opacity: 0.5,
                },
            },
        ],
    }
    taskTrendChart.setOption(option)
    state.charts.push(taskTrendChart)
}

/**
 * 艹，初始化任务状态分布图表
 */
const initTaskStatusChart = () => {
    const taskStatusChart = echarts.init(chartRefs.value[1] as HTMLElement)
    const option = {
        tooltip: {
            trigger: 'item',
            formatter: '{a} <br/>{b}: {c} ({d}%)',
        },
        legend: {
            orient: 'vertical',
            right: 10,
            top: 'center',
            data: ['成功', '处理中', '失败'],
            textStyle: {
                color: '#73767a',
            },
        },
        series: [
            {
                name: '任务状态',
                type: 'pie',
                radius: ['40%', '70%'],
                center: ['40%', '50%'],
                data: [
                    { value: countUp.successTasks, name: '成功', itemStyle: { color: '#67C23A' } },
                    { value: countUp.processingTasks, name: '处理中', itemStyle: { color: '#E6A23C' } },
                    {
                        value: countUp.totalTasks - countUp.successTasks - countUp.processingTasks,
                        name: '失败',
                        itemStyle: { color: '#F56C6C' }
                    },
                ],
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)',
                    },
                },
            },
        ],
    }
    taskStatusChart.setOption(option)
    state.charts.push(taskStatusChart)
}

/**
 * 艹，图表自适应
 */
const echartsResize = () => {
    nextTick(() => {
        for (const key in state.charts) {
            state.charts[key].resize()
        }
    })
}

onActivated(() => {
    echartsResize()
})

onMounted(() => {
    window.addEventListener('resize', echartsResize)
})
</script>

<style scoped lang="scss">
.welcome {
    background: #e1eaf9;
    border-radius: 6px;
    display: flex;
    align-items: center;
    padding: 15px 20px !important;
    box-shadow: 0 0 30px 0 rgba(82, 63, 105, 0.05);
    .welcome-img {
        height: 100px;
        margin-right: 10px;
        user-select: none;
    }
    .welcome-title {
        font-size: 1.5rem;
        line-height: 30px;
        color: var(--ba-color-primary-light);
    }
    .welcome-note {
        padding-top: 6px;
        font-size: 15px;
        color: var(--el-text-color-primary);
    }
}

.small-panel-box {
    margin-top: 20px;
}

.small-panel {
    background-color: #e9edf2;
    border-radius: var(--el-border-radius-base);
    padding: 25px;
    margin-bottom: 20px;
    .small-panel-title {
        color: #92969a;
        font-size: 15px;
    }
    .small-panel-content {
        display: flex;
        align-items: flex-end;
        margin-top: 20px;
        color: #2c3f5d;
        .content-left {
            display: flex;
            align-items: center;
            font-size: 24px;
            .icon {
                margin-right: 10px;
            }
        }
        .content-right {
            font-size: 14px;
            margin-left: auto;
            color: #92969a;
        }
    }
}

.growth-chart {
    margin-bottom: 20px;
}

.task-trend-chart {
    height: 300px;
}

.task-status-chart {
    height: 300px;
}

.recent-tasks {
    margin-bottom: 20px;
}

@media screen and (max-width: 425px) {
    .welcome-img {
        display: none;
    }
}

@media screen and (max-width: 1200px) {
    .lg-mb-20 {
        margin-bottom: 20px;
    }
}

html.dark {
    .welcome {
        background-color: var(--ba-bg-color-overlay);
    }
    .small-panel {
        background-color: var(--ba-bg-color-overlay);
        .small-panel-content {
            color: var(--el-text-color-regular);
        }
    }
}
</style>

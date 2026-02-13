# 会员积分功能实施规划

## 一、功能目标

为 AIPortrait 小程序端新增会员积分系统，实现积分充值、消耗、查询和管理功能。

### 核心功能
1. **积分查询与展示**：用户可在小程序查看积分余额和明细
2. **生成写真消耗积分**：生成 AI 写真时扣除相应积分
3. **积分充值购买**：用户通过充值获得积分（模拟支付）
4. **管理后台积分管理**：管理员配置规则、手动调整积分、查看统计数据

---

## 二、数据库设计

### 2.1 新增表

#### 表1：ba_score_recharge_package（积分充值档位表）
```sql
CREATE TABLE `ba_score_recharge_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '档位名称',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值金额（元）',
  `score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '获得积分',
  `bonus_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用 1=启用',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分充值档位表';
```

#### 表2：ba_score_config（积分配置表）
```sql
CREATE TABLE `ba_score_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
  `config_value` varchar(255) NOT NULL DEFAULT '' COMMENT '配置值',
  `config_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '配置说明',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分配置表';
```

**初始配置数据**：
- `recharge_ratio`：充值比例（如 1元=10积分）
- `generate_cost`：生成一张图片消耗积分数
- `score_expire_days`：积分有效期（天数，0表示永久有效）

#### 表3：ba_score_recharge_order（积分充值订单表）
```sql
CREATE TABLE `ba_score_recharge_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `package_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '充值档位ID',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '获得积分',
  `bonus_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=未支付 1=已支付 2=已取消',
  `pay_time` int(10) unsigned DEFAULT NULL COMMENT '支付时间',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `pay_status` (`pay_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分充值订单表';
```

### 2.2 修改现有表

#### ba_user 表
- 已有 `score` 字段（积分余额）
- 需要新增字段：
```sql
ALTER TABLE `ba_user` ADD COLUMN `score_expire_time` int(10) unsigned DEFAULT NULL COMMENT '积分过期时间' AFTER `score`;
```

#### ba_user_score_log 表
- 已有字段可满足需求
- 确保 `memo` 字段记录详细的积分变动原因

---

## 三、后端 API 设计

### 3.1 小程序端 API（/api/score/）

#### 1. 获取积分信息
```
GET /api/score/info
返回：{
  score: 当前积分,
  expire_time: 过期时间,
  expire_days: 剩余天数
}
```

#### 2. 获取积分明细
```
GET /api/score/log
参数：page, limit
返回：{
  list: [积分变动记录],
  total: 总数
}
```

#### 3. 获取充值档位
```
GET /api/score/packages
返回：{
  list: [充值档位列表]
}
```

#### 4. 创建充值订单
```
POST /api/score/createOrder
参数：package_id
返回：{
  order_no: 订单号,
  amount: 金额,
  score: 积分
}
```

#### 5. 模拟支付
```
POST /api/score/mockPay
参数：order_no
返回：{
  success: true/false
}
```

#### 6. 积分消耗（生成写真时调用）
```
POST /api/score/consume
参数：count（生成图片数量）
返回：{
  success: true/false,
  consumed: 消耗积分数,
  balance: 剩余积分
}
```

### 3.2 管理后台 API（/admin/score/）

#### 1. 充值档位管理
```
GET /admin/score/package/index - 列表
POST /admin/score/package/add - 添加
POST /admin/score/package/edit - 编辑
POST /admin/score/package/del - 删除
POST /admin/score/package/sortable - 排序
```

#### 2. 积分配置管理
```
GET /admin/score/config/index - 获取配置
POST /admin/score/config/save - 保存配置
```

#### 3. 用户积分管理
```
GET /admin/score/user/index - 用户积分列表
POST /admin/score/user/adjust - 手动调整积分
GET /admin/score/user/log - 用户积分明细
```

#### 4. 积分统计分析
```
GET /admin/score/statistics/overview - 积分概览
GET /admin/score/statistics/consume - 消耗统计
GET /admin/score/statistics/recharge - 充值统计
GET /admin/score/statistics/ranking - 用户积分排行
```

---

## 四、小程序前端页面设计

### 4.1 我的页面（pages/mine/index.vue）
**修改内容**：
- 顶部添加积分余额显示卡片
- 显示当前积分、过期时间
- 点击跳转到积分明细页面
- 添加"充值"按钮

### 4.2 积分明细页面（pages/score/detail.vue）
**新增页面**：
- 顶部显示当前积分余额
- 列表展示积分收支记录
- 每条记录显示：时间、类型、金额、余额、备注
- 支持下拉刷新、上拉加载更多

### 4.3 积分充值页面（pages/score/recharge.vue）
**新增页面**：
- 显示充值档位列表（卡片形式）
- 每个档位显示：金额、获得积分、赠送积分
- 选中档位后显示"立即充值"按钮
- 点击充值触发模拟支付流程

### 4.4 生成写真页面（pages/upload/index.vue）
**修改内容**：
- 提交前检查积分是否足够
- 积分不足时提示并引导充值
- 提交成功后扣除积分并显示提示

---

## 五、管理后台页面设计

### 5.1 积分配置页面（views/backend/score/config/index.vue）
**新增页面**：
- 表单配置：
  - 充值比例（1元=N积分）
  - 生成图片消耗积分
  - 积分有效期（天数）
- 保存按钮

### 5.2 充值档位管理（views/backend/score/package/index.vue）
**新增页面**：
- 表格展示充值档位列表
- 支持添加、编辑、删除、排序
- 字段：档位名称、金额、积分、赠送积分、状态

### 5.3 用户积分管理（views/backend/score/user/index.vue）
**新增页面**：
- 表格展示用户积分列表
- 显示：用户ID、昵称、当前积分、过期时间
- 操作：手动增减积分、查看明细
- 手动调整积分弹窗：输入金额、选择类型（增加/减少）、填写备注

### 5.4 积分统计页面（views/backend/score/statistics/index.vue）
**新增页面**：
- 概览卡片：总充值金额、总消耗积分、当前用户总积分
- 消耗统计图表：按日期统计积分消耗趋势
- 充值统计图表：按日期统计充值金额趋势
- 用户积分排行榜：Top 100 用户

---

## 六、实施步骤

### 阶段一：数据库与后端基础（优先级最高）

#### 步骤 1.1：创建数据库迁移文件
- 创建 `20260206_create_score_tables.php`
- 包含 3 个新表的创建
- 包含 ba_user 表的字段修改
- 插入初始配置数据

#### 步骤 1.2：创建后端模型
- `app/common/model/ScoreRechargePackage.php`
- `app/common/model/ScoreConfig.php`
- `app/common/model/ScoreRechargeOrder.php`

#### 步骤 1.3：创建积分服务类
- `app/common/library/ScoreService.php`
- 包含方法：
  - `getUserScore()` - 获取用户积分
  - `addScore()` - 增加积分
  - `consumeScore()` - 消耗积分
  - `checkExpire()` - 检查积分是否过期
  - `getConfig()` - 获取积分配置

### 阶段二：小程序端积分查询与展示

#### 步骤 2.1：创建小程序端 API 控制器
- `app/api/controller/Score.php`
- 实现 `info()`、`log()` 方法

#### 步骤 2.2：修改"我的"页面
- 添加积分余额显示卡片
- 添加"充值"按钮
- 添加跳转逻辑

#### 步骤 2.3：创建积分明细页面
- 创建 `frontend/pages/score/detail.vue`
- 实现积分记录列表展示
- 实现下拉刷新、上拉加载

### 阶段三：生成写真消耗积分

#### 步骤 3.1：修改生成写真 API
- 修改 `app/api/controller/Portrait.php` 的 `generate()` 方法
- 添加积分检查逻辑
- 添加积分扣除逻辑
- 记录积分消耗日志

#### 步骤 3.2：修改小程序上传页面
- 修改 `frontend/pages/upload/index.vue`
- 提交前检查积分
- 积分不足时提示并引导充值

### 阶段四：积分充值购买

#### 步骤 4.1：实现充值相关 API
- 实现 `packages()`、`createOrder()`、`mockPay()` 方法
- 创建订单生成逻辑
- 实现模拟支付逻辑

#### 步骤 4.2：创建充值页面
- 创建 `frontend/pages/score/recharge.vue`
- 展示充值档位列表
- 实现充值流程

### 阶段五：管理后台积分管理

#### 步骤 5.1：创建管理后台控制器
- `app/admin/controller/score/Package.php` - 充值档位管理
- `app/admin/controller/score/Config.php` - 积分配置
- `app/admin/controller/score/User.php` - 用户积分管理
- `app/admin/controller/score/Statistics.php` - 积分统计

#### 步骤 5.2：创建管理后台页面
- 积分配置页面
- 充值档位管理页面
- 用户积分管理页面
- 积分统计页面

#### 步骤 5.3：添加后台菜单权限
- 创建迁移文件添加菜单权限节点
- 配置菜单层级和权限

---

## 七、验收标准

### 7.1 功能测试

#### 积分查询与展示
- [ ] 小程序"我的"页面正确显示积分余额
- [ ] 积分明细页面正确展示收支记录
- [ ] 积分过期时间正确显示
- [ ] 下拉刷新和上拉加载正常工作

#### 生成写真消耗积分
- [ ] 生成写真前正确检查积分余额
- [ ] 积分不足时正确提示并引导充值
- [ ] 生成成功后正确扣除积分
- [ ] 积分消耗记录正确记录到日志

#### 积分充值购买
- [ ] 充值档位列表正确展示
- [ ] 创建充值订单成功
- [ ] 模拟支付流程正常
- [ ] 支付成功后积分正确到账
- [ ] 充值记录正确记录到日志

#### 管理后台积分管理
- [ ] 积分配置保存成功并生效
- [ ] 充值档位增删改查正常
- [ ] 手动调整用户积分成功
- [ ] 积分统计数据准确
- [ ] 用户积分排行榜正确

### 7.2 数据一致性验证

- [ ] 用户积分余额与日志记录一致
- [ ] 充值订单金额与积分到账一致
- [ ] 积分消耗数量与配置规则一致
- [ ] 积分过期时间计算正确

### 7.3 用户体验检查

- [ ] 页面加载速度快（< 2秒）
- [ ] 交互流畅，无卡顿
- [ ] 错误提示清晰友好
- [ ] 充值流程简单易懂
- [ ] 积分明细信息完整

### 7.4 边界情况测试

- [ ] 积分为 0 时的处理
- [ ] 积分不足时的处理
- [ ] 积分过期后的处理
- [ ] 并发充值的处理
- [ ] 并发消耗的处理

---

## 八、技术要点

### 8.1 积分过期处理
- 使用定时任务（cron）每天检查过期积分
- 过期积分自动清零并记录日志
- 用户查询时实时检查是否过期

### 8.2 积分事务处理
- 积分增减操作必须使用数据库事务
- 确保积分变动和日志记录的原子性
- 防止并发操作导致的数据不一致

### 8.3 订单号生成规则
- 格式：`SR` + 时间戳 + 随机数
- 示例：`SR202602061234567890123`
- 确保唯一性

### 8.4 模拟支付实现
- 开发期间直接标记订单为已支付
- 后续对接真实支付时替换此逻辑
- 保留支付回调接口预留

---

## 九、后续扩展建议

1. **真实支付对接**：对接微信支付 API
2. **积分任务系统**：签到、分享、邀请等获得积分
3. **积分兑换商城**：用积分兑换礼品或优惠券
4. **积分等级体系**：根据积分设置用户等级
5. **积分转赠功能**：用户之间可以转赠积分

---

**文档版本**：v1.0
**创建时间**：2026-02-06
**最后更新**：2026-02-06

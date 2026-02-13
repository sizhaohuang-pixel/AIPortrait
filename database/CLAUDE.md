# 数据库模块 (Database)

[根目录](../CLAUDE.md) > **database**

---

## 变更记录 (Changelog)

### 2026-02-03 22:09:55
- 初始化数据库模块文档
- 完成迁移文件扫描

---

## 模块职责

数据库模块负责：
- 数据库结构定义与版本管理
- 数据库迁移（Migration）
- 初始数据填充（Seeding）
- 数据库版本升级

## 入口与启动

### 迁移工具

使用 Phinx 进行数据库迁移管理，通过 ThinkPHP 命令行工具调用。

### 常用命令

```bash
# 运行所有待执行的迁移
php think migrate:run

# 回滚最后一次迁移
php think migrate:rollback

# 查看迁移状态
php think migrate:status

# 创建新的迁移文件
php think migrate:create YourMigrationName

# 回滚所有迁移
php think migrate:rollback -t 0

# 运行指定版本的迁移
php think migrate:run -t 20230620180908
```

## 对外接口

### 迁移文件结构

每个迁移文件包含两个主要方法：

```php
class YourMigration extends Migration
{
    /**
     * 执行迁移（升级）
     */
    public function change()
    {
        // 定义表结构变更
    }

    /**
     * 或者分别定义 up 和 down
     */
    public function up()
    {
        // 升级操作
    }

    public function down()
    {
        // 回滚操作
    }
}
```

## 关键依赖与配置

### Composer 依赖

```json
{
  "topthink/think-migration": "3.1.1"
}
```

### 配置文件

迁移配置在 `config/database.php` 中定义：
- 数据库连接信息
- 表前缀：`ba_`
- 迁移表名：`phinxlog`

## 数据模型

### 现有迁移文件

1. **20230620180908_install.php**
   - 初始安装迁移
   - 创建核心数据表

2. **20230620180916_install_data.php**
   - 初始数据填充
   - 插入默认配置和权限数据

3. **20230622221507_version200.php**
   - 版本 2.0.0 升级

4. **20230719211338_version201.php**
   - 版本 2.0.1 升级

5. **20230905060702_version202.php**
   - 版本 2.0.2 升级

6. **20231112093414_version205.php**
   - 版本 2.0.5 升级

7. **20231229043002_version206.php**
   - 版本 2.0.6 升级

8. **20250412134127_version222.php**
   - 版本 2.2.2 升级

### 核心数据表

**用户相关表**
- `ba_user` - 用户表
- `ba_user_group` - 用户组表
- `ba_user_rule` - 用户权限规则表
- `ba_user_score_log` - 用户积分日志
- `ba_user_money_log` - 用户余额日志

**管理员相关表**
- `ba_admin` - 管理员表
- `ba_admin_group` - 管理员组表
- `ba_admin_group_access` - 管理员组关联表
- `ba_admin_rule` - 权限规则表
- `ba_admin_log` - 管理员操作日志

**系统相关表**
- `ba_config` - 系统配置表
- `ba_attachment` - 附件表
- `ba_area` - 地区表
- `ba_token` - Token 表
- `ba_captcha` - 验证码表

**安全相关表**
- `ba_security_data_recycle` - 数据回收站表
- `ba_security_data_recycle_log` - 数据回收日志
- `ba_security_sensitive_data` - 敏感数据表
- `ba_security_sensitive_data_log` - 敏感数据日志

**CRUD 相关表**
- `ba_crud` - CRUD 配置表
- `ba_crud_log` - CRUD 日志表

## 测试与质量

### 迁移测试

**测试迁移是否可执行**
```bash
# 在测试环境运行迁移
php think migrate:run

# 测试回滚
php think migrate:rollback
```

**验证数据完整性**
- 检查表结构是否正确
- 验证索引是否创建
- 确认外键约束
- 测试默认数据是否插入

### 最佳实践

1. **迁移文件命名**
   - 使用时间戳前缀
   - 描述性的名称
   - 例如：`20230620180908_create_users_table.php`

2. **迁移内容**
   - 每个迁移只做一件事
   - 保持迁移的原子性
   - 提供回滚方法

3. **数据填充**
   - 将结构变更和数据填充分开
   - 使用独立的 Seeder 文件

## 常见问题 (FAQ)

### 如何创建新表？

```php
public function change()
{
    $table = $this->table('your_table', [
        'id' => false,
        'primary_key' => ['id'],
        'engine' => 'InnoDB',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => '你的表注释',
    ]);

    $table->addColumn('id', 'integer', [
            'limit' => 11,
            'signed' => false,
            'identity' => true,
            'comment' => 'ID',
        ])
        ->addColumn('name', 'string', [
            'limit' => 50,
            'default' => '',
            'comment' => '名称',
        ])
        ->addColumn('create_time', 'integer', [
            'limit' => 10,
            'signed' => false,
            'default' => 0,
            'comment' => '创建时间',
        ])
        ->addIndex(['name'], ['unique' => true])
        ->create();
}
```

### 如何修改现有表？

```php
public function change()
{
    $table = $this->table('your_table');

    // 添加列
    $table->addColumn('new_field', 'string', [
        'limit' => 100,
        'default' => '',
        'comment' => '新字段',
        'after' => 'existing_field',
    ]);

    // 修改列
    $table->changeColumn('old_field', 'text', [
        'comment' => '修改后的注释',
    ]);

    // 删除列
    $table->removeColumn('unused_field');

    // 添加索引
    $table->addIndex(['new_field']);

    $table->update();
}
```

### 如何插入初始数据？

```php
public function change()
{
    $data = [
        [
            'name' => 'admin',
            'email' => 'admin@example.com',
            'create_time' => time(),
        ],
    ];

    $this->table('your_table')->insert($data)->save();
}
```

### 迁移失败如何处理？

1. **查看错误信息**
   ```bash
   php think migrate:run
   ```

2. **回滚到上一个版本**
   ```bash
   php think migrate:rollback
   ```

3. **修复迁移文件后重新运行**
   ```bash
   php think migrate:run
   ```

4. **如果需要重置所有迁移**
   ```bash
   # 警告：这会删除所有数据！
   php think migrate:rollback -t 0
   php think migrate:run
   ```

### 如何在不同环境使用不同的迁移？

在迁移文件中检查环境：

```php
public function change()
{
    if (env('app_debug')) {
        // 开发环境的操作
    } else {
        // 生产环境的操作
    }
}
```

## 相关文件清单

### 迁移文件列表

```
database/migrations/
├── 20230620180908_install.php           # 初始安装
├── 20230620180916_install_data.php      # 初始数据
├── 20230622221507_version200.php        # v2.0.0
├── 20230719211338_version201.php        # v2.0.1
├── 20230905060702_version202.php        # v2.0.2
├── 20231112093414_version205.php        # v2.0.5
├── 20231229043002_version206.php        # v2.0.6
└── 20250412134127_version222.php        # v2.2.2
```

### 数据表清单

**核心表（约 30+ 张）**
- 用户系统：5 张表
- 管理员系统：5 张表
- 权限系统：3 张表
- 系统配置：5 张表
- 安全系统：4 张表
- CRUD 系统：2 张表
- 其他辅助表：若干

## 数据库设计原则

1. **表前缀统一**
   - 所有表使用 `ba_` 前缀
   - 便于识别和管理

2. **字段命名规范**
   - 使用小写字母和下划线
   - 时间字段统一使用 `_time` 后缀
   - 布尔字段使用 `is_` 前缀

3. **索引策略**
   - 主键使用自增 ID
   - 常用查询字段添加索引
   - 唯一字段添加唯一索引

4. **字符集和排序规则**
   - 使用 `utf8mb4` 字符集
   - 排序规则：`utf8mb4_unicode_ci`

5. **时间戳**
   - 使用 Unix 时间戳（整型）
   - 字段名：`create_time`、`update_time`

---

**模块路径**: `E:\AgentWorker\AIPortrait\database`
**迁移工具**: Phinx (via think-migration 3.1.1)
**数据库**: MySQL 5.7+
**表前缀**: `ba_`
**文档更新**: 2026-02-03 22:09:55

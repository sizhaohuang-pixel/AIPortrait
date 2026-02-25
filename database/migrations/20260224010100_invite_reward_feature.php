<?php

declare(strict_types=1);

use think\migration\Migrator;

class InviteRewardFeature extends Migrator
{
    public function up(): void
    {
        if (!$this->hasTable('user_invite')) {
            $this->execute("
                CREATE TABLE `ba_user_invite` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `inviter_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人用户ID',
                  `invitee_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被邀请用户ID',
                  `scene` varchar(50) NOT NULL DEFAULT '' COMMENT '邀请来源场景',
                  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0=待生效,1=已奖励',
                  `reward_score` int(11) NOT NULL DEFAULT '0' COMMENT '奖励积分',
                  `bind_time` bigint(16) unsigned DEFAULT NULL COMMENT '绑定时间',
                  `reward_time` bigint(16) unsigned DEFAULT NULL COMMENT '奖励发放时间',
                  `create_time` bigint(16) unsigned DEFAULT NULL COMMENT '创建时间',
                  `update_time` bigint(16) unsigned DEFAULT NULL COMMENT '更新时间',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_invitee_user_id` (`invitee_user_id`),
                  KEY `idx_inviter_user_id` (`inviter_user_id`),
                  KEY `idx_create_time` (`create_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='用户邀请关系表'
            ");
        }

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_reward_score','10','邀请好友奖励积分',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_reward_score')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_rule_text','1. 通过你的分享进入小程序并完成首次登录；2. 仅新用户有效；3. 每成功邀请1人可获得固定积分奖励。','邀请规则说明',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_rule_text')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_share_title','你收到一份AI肖像体验邀请，点击查看','邀请分享标题',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_share_title')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_poster_title','你收到一份AI肖像邀请','邀请海报主标题',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_poster_title')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_poster_subtitle','点击进入小程序，体验专属形象生成','邀请海报副标题',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_poster_subtitle')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_poster_highlight','首次登录即可解锁更多玩法与模板','邀请海报高亮文案',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_poster_highlight')
        ");

        $this->execute("
            INSERT INTO `ba_score_config` (`config_key`,`config_value`,`config_desc`,`create_time`,`update_time`)
            SELECT 'invite_poster_button_text','点击查看邀请','邀请海报按钮文案',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_score_config` WHERE `config_key`='invite_poster_button_text')
        ");

        $this->execute("
            INSERT INTO `ba_admin_rule` (`pid`,`type`,`title`,`name`,`path`,`icon`,`menu_type`,`url`,`component`,`keepalive`,`extend`,`remark`,`weigh`,`status`,`update_time`,`create_time`)
            SELECT 115,'menu','邀请规则','score/inviteRule','score/inviteRule','','tab','','/src/views/backend/score/inviteRule/index.vue',1,'none','邀请奖励积分与规则配置',95,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_admin_rule` WHERE `name`='score/inviteRule')
        ");

        $this->execute("
            INSERT INTO `ba_admin_rule` (`pid`,`type`,`title`,`name`,`path`,`icon`,`menu_type`,`url`,`component`,`keepalive`,`extend`,`remark`,`weigh`,`status`,`update_time`,`create_time`)
            SELECT 115,'menu','邀请记录','score/invite','score/invite','','tab','','/src/views/backend/score/invite/index.vue',1,'none','邀请记录与奖励明细',94,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_admin_rule` WHERE `name`='score/invite')
        ");

        $this->execute("
            INSERT INTO `ba_admin_rule` (`pid`,`type`,`title`,`name`,`path`,`icon`,`menu_type`,`url`,`component`,`keepalive`,`extend`,`remark`,`weigh`,`status`,`update_time`,`create_time`)
            SELECT (SELECT id FROM `ba_admin_rule` WHERE `name`='score/inviteRule' LIMIT 1),'button','查看','score/inviteRule/index','','',NULL,'','',0,'none','',0,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_admin_rule` WHERE `name`='score/inviteRule/index')
        ");

        $this->execute("
            INSERT INTO `ba_admin_rule` (`pid`,`type`,`title`,`name`,`path`,`icon`,`menu_type`,`url`,`component`,`keepalive`,`extend`,`remark`,`weigh`,`status`,`update_time`,`create_time`)
            SELECT (SELECT id FROM `ba_admin_rule` WHERE `name`='score/inviteRule' LIMIT 1),'button','保存','score/inviteRule/save','','',NULL,'','',0,'none','',0,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_admin_rule` WHERE `name`='score/inviteRule/save')
        ");

        $this->execute("
            INSERT INTO `ba_admin_rule` (`pid`,`type`,`title`,`name`,`path`,`icon`,`menu_type`,`url`,`component`,`keepalive`,`extend`,`remark`,`weigh`,`status`,`update_time`,`create_time`)
            SELECT (SELECT id FROM `ba_admin_rule` WHERE `name`='score/invite' LIMIT 1),'button','查看','score/invite/index','','',NULL,'','',0,'none','',0,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `ba_admin_rule` WHERE `name`='score/invite/index')
        ");
    }

    public function down(): void
    {
        if ($this->hasTable('user_invite')) {
            $this->table('user_invite')->drop()->save();
        }

        $this->execute("DELETE FROM `ba_score_config` WHERE `config_key` IN ('invite_reward_score','invite_rule_text','invite_share_title','invite_poster_title','invite_poster_subtitle','invite_poster_highlight','invite_poster_button_text')");
        $this->execute("DELETE FROM `ba_admin_rule` WHERE `name` IN ('score/inviteRule/index','score/inviteRule/save','score/invite/index','score/inviteRule','score/invite')");
    }
}

-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `mp_commentmeta`;
CREATE TABLE `mp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `mp_comments`;
CREATE TABLE `mp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_comments` (`comment_ID`, `comment_post_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_author_IP`, `comment_date`, `comment_date_gmt`, `comment_content`, `comment_karma`, `comment_approved`, `comment_agent`, `comment_type`, `comment_parent`, `user_id`) VALUES
(1,	1,	'A WordPress Commenter',	'wapuu@wordpress.example',	'https://wordpress.org/',	'',	'2016-11-23 14:16:53',	'2016-11-23 14:16:53',	'Hi, this is a comment.\nTo get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.\nCommenter avatars come from <a href=\"https://gravatar.com\">Gravatar</a>.',	0,	'1',	'',	'',	0,	0);

DROP TABLE IF EXISTS `mp_links`;
CREATE TABLE `mp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `mp_mailpoet_custom_fields`;
CREATE TABLE `mp_mailpoet_custom_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `params` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_dummytable`;
CREATE TABLE `mp_mailpoet_dummytable` (
  `dummycol` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `mp_mailpoet_forms`;
CREATE TABLE `mp_mailpoet_forms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_520_ci,
  `settings` longtext COLLATE utf8mb4_unicode_520_ci,
  `styles` longtext COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_forms` (`id`, `name`, `body`, `settings`, `styles`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1,	'Test Form',	'a:2:{i:0;a:7:{s:4:\"type\";s:4:\"text\";s:4:\"name\";s:5:\"Email\";s:2:\"id\";s:5:\"email\";s:6:\"unique\";s:1:\"0\";s:6:\"static\";s:1:\"1\";s:6:\"params\";a:2:{s:5:\"label\";s:5:\"Email\";s:8:\"required\";s:4:\"true\";}s:8:\"position\";s:1:\"1\";}i:1;a:7:{s:4:\"type\";s:6:\"submit\";s:4:\"name\";s:6:\"Submit\";s:2:\"id\";s:6:\"submit\";s:6:\"unique\";s:1:\"0\";s:6:\"static\";s:1:\"1\";s:6:\"params\";a:1:{s:5:\"label\";s:10:\"Subscribe!\";}s:8:\"position\";s:1:\"2\";}}',	'a:5:{s:8:\"segments\";a:1:{i:0;s:1:\"2\";}s:10:\"on_success\";s:7:\"message\";s:15:\"success_message\";s:61:\"Check your inbox or spam folder to confirm your subscription.\";s:12:\"success_page\";s:1:\"4\";s:20:\"segments_selected_by\";s:5:\"admin\";}',	'/* form */\n.mailpoet_form {\n\n}\n\n/* paragraphs (label + input) */\n.mailpoet_paragraph {\n  line-height:20px;\n}\n\n/* labels */\n.mailpoet_segment_label,\n.mailpoet_text_label,\n.mailpoet_textarea_label,\n.mailpoet_select_label,\n.mailpoet_radio_label,\n.mailpoet_checkbox_label,\n.mailpoet_list_label,\n.mailpoet_date_label {\n  display:block;\n  font-weight:bold;\n}\n\n/* inputs */\n.mailpoet_text,\n.mailpoet_textarea,\n.mailpoet_select,\n.mailpoet_date_month,\n.mailpoet_date_day,\n.mailpoet_date_year,\n.mailpoet_date {\n  display:block;\n}\n\n.mailpoet_text,\n.mailpoet_textarea {\n  width:200px;\n}\n\n.mailpoet_checkbox {\n}\n\n.mailpoet_submit input {\n}\n\n.mailpoet_divider {\n}\n\n.mailpoet_message {\n}\n\n.mailpoet_validate_success {\n  color:#468847;\n}\n\n.mailpoet_validate_error {\n  color:#B94A48;\n}',	'2017-10-30 00:58:40',	'2017-10-30 00:58:50',	NULL);

DROP TABLE IF EXISTS `mp_mailpoet_mapping_to_external_entities`;
CREATE TABLE `mp_mailpoet_mapping_to_external_entities` (
  `old_id` int(11) unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `new_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`old_id`,`type`),
  KEY `new_id` (`new_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_newsletters`;
CREATE TABLE `mp_mailpoet_newsletters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(150) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `subject` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'standard',
  `sender_address` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `sender_name` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'draft',
  `reply_to_address` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `reply_to_name` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `preheader` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `body` longtext COLLATE utf8mb4_unicode_520_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `unsubscribe_token` varchar(15) NULL,
  UNIQUE KEY unsubscribe_token (unsubscribe_token),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_newsletters` VALUES
(1,NULL,NULL,'Standard newsletter','standard','wp@example.com','test','draft','','','','','','2017-11-16 11:02:35',NULL,NULL,'randomtokennum1'),
(2,NULL,NULL,'Welcome email','welcome','wp@example.com','test','draft','','','','','','2017-11-16 11:02:35',NULL,NULL,'randomtokennum2'),
(3,NULL,NULL,'Post notification','notification','wp@example.com','test','draft','','','','','','2017-11-16 11:02:35',NULL,NULL,'randomtokennum3');

DROP TABLE IF EXISTS `mp_mailpoet_newsletter_links`;
CREATE TABLE `mp_mailpoet_newsletter_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `queue_id` int(11) unsigned NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `hash` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY queue_id (queue_id),
  KEY newsletter_id (newsletter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_newsletter_option`;
CREATE TABLE `mp_mailpoet_newsletter_option` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `option_field_id` int(11) unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_id_option_field_id` (`newsletter_id`,`option_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_newsletter_option_fields`;
CREATE TABLE `mp_mailpoet_newsletter_option_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `newsletter_type` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_newsletter_type` (`newsletter_type`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_newsletter_option_fields` (`id`, `name`, `newsletter_type`, `created_at`, `updated_at`) VALUES
(1,	'isScheduled',	'standard',	NULL,	'2017-10-30 00:57:38'),
(2,	'scheduledAt',	'standard',	NULL,	'2017-10-30 00:57:38'),
(3,	'event',	'welcome',	NULL,	'2017-10-30 00:57:38'),
(4,	'segment',	'welcome',	NULL,	'2017-10-30 00:57:38'),
(5,	'role',	'welcome',	NULL,	'2017-10-30 00:57:38'),
(6,	'afterTimeNumber',	'welcome',	NULL,	'2017-10-30 00:57:38'),
(7,	'afterTimeType',	'welcome',	NULL,	'2017-10-30 00:57:38'),
(8,	'intervalType',	'notification',	NULL,	'2017-10-30 00:57:38'),
(9,	'timeOfDay',	'notification',	NULL,	'2017-10-30 00:57:38'),
(10,	'weekDay',	'notification',	NULL,	'2017-10-30 00:57:38'),
(11,	'monthDay',	'notification',	NULL,	'2017-10-30 00:57:38'),
(12,	'nthWeekDay',	'notification',	NULL,	'2017-10-30 00:57:38'),
(13,	'schedule',	'notification',	NULL,	'2017-10-30 00:57:38');

DROP TABLE IF EXISTS `mp_mailpoet_newsletter_posts`;
CREATE TABLE `mp_mailpoet_newsletter_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `post_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY newsletter_id (newsletter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_newsletter_segment`;
CREATE TABLE `mp_mailpoet_newsletter_segment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `segment_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_segment` (`newsletter_id`,`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_newsletter_templates`;
CREATE TABLE `mp_mailpoet_newsletter_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `categories` varchar(250) NOT NULL DEFAULT "[]",
  `newsletter_id` int NULL DEFAULT 0,
  `description` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_520_ci,
  `thumbnail` longtext COLLATE utf8mb4_unicode_520_ci,
  `readonly` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `mp_mailpoet_premium_custom_table`;
CREATE TABLE `mp_mailpoet_premium_custom_table` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) NOT NULL,
  `type` varchar(90) NOT NULL DEFAULT 'default',
  `description` varchar(250) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `mp_mailpoet_premium_newsletter_extra_data`;
CREATE TABLE `mp_mailpoet_premium_newsletter_extra_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `ga_campaign` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_id` (`newsletter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_premium_newsletter_extra_data1`;
CREATE TABLE `mp_mailpoet_premium_newsletter_extra_data1` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `newsletter_id` mediumint(9) NOT NULL,
  `ga_campaign` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_id` (`newsletter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci ROW_FORMAT=COMPACT;

INSERT INTO `mp_mailpoet_premium_newsletter_extra_data1` (`id`, `newsletter_id`, `ga_campaign`, `created_at`, `updated_at`) VALUES
(1,	7,	'my_campaign',	'2017-03-02 11:20:00',	'2017-03-02 16:21:00'),
(2,	12,	'Spring email',	'2017-03-02 16:22:35',	'2017-03-02 16:26:42'),
(3,	13,	'Spring email',	'2017-03-02 16:28:49',	'2017-03-02 16:30:33'),
(4,	14,	'Spring email',	'2017-03-02 16:53:09',	'2017-03-02 16:53:09'),
(5,	15,	'Spring email',	'2017-03-02 16:56:27',	'2017-03-02 16:56:27'),
(6,	16,	'Spring email',	'2017-03-02 17:12:17',	'2017-03-02 17:12:17'),
(7,	17,	'vgg',	'2017-03-02 17:17:15',	'2017-03-02 17:17:15'),
(8,	18,	'',	'2017-03-02 17:34:47',	'2017-03-02 17:34:47');

DROP TABLE IF EXISTS `mp_mailpoet_scheduled_tasks`;
CREATE TABLE `mp_mailpoet_scheduled_tasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(90) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `priority` mediumint(9) NOT NULL DEFAULT '0',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_scheduled_tasks` (`id`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1,	'migration',	'completed',	'2017-03-02 11:20:00',	'2017-03-02 16:21:00');


DROP TABLE IF EXISTS `mp_mailpoet_scheduled_task_subscribers`;
CREATE TABLE `mp_mailpoet_scheduled_task_subscribers` (
  `task_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `processed` int(1) NOT NULL,
  `failed` smallint(1) NOT NULL DEFAULT 0,
  `error` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`,`subscriber_id`),
  KEY subscriber_id (subscriber_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_segments`;
CREATE TABLE `mp_mailpoet_segments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` varchar(90) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `description` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_segments` (`id`, `name`, `type`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1,	'WordPress Users',	'wp_users',	'This list contains all of your WordPress users.',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39',	NULL),
(2,	'WooCommerce Customers',	'woocommerce_users',	'This list contains all of your WooCommerce customers.',	'2019-01-17 00:57:39',	'2019-01-17 00:57:39',	NULL),
(3,	'My First List',	'default',	'This list is automatically created when you install MailPoet.',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39',	NULL);

DROP TABLE IF EXISTS `mp_mailpoet_sending_queues`;
CREATE TABLE `mp_mailpoet_sending_queues` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(11) unsigned NOT NULL,
  `newsletter_id` int(11) unsigned NOT NULL,
  `newsletter_rendered_body` longtext COLLATE utf8mb4_unicode_520_ci,
  `newsletter_rendered_subject` varchar(250) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `subscribers` longtext COLLATE utf8mb4_unicode_520_ci,
  `count_total` int(11) unsigned NOT NULL DEFAULT '0',
  `count_processed` int(11) unsigned NOT NULL DEFAULT '0',
  `count_to_process` int(11) unsigned NOT NULL DEFAULT '0',
  `meta` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY task_id (task_id),
  KEY newsletter_id (newsletter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_settings`;
CREATE TABLE `mp_mailpoet_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_settings` (`id`, `name`, `value`, `created_at`, `updated_at`) VALUES
(1,	'sender',	'a:2:{s:4:\"name\";s:5:\"admin\";s:7:\"address\";s:14:\"wp@example.com\";}',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39'),
(2,	'installed_at',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39'),
(3,	'mta_log',	'a:6:{s:4:\"sent\";N;s:7:\"started\";i:1509325059;s:6:\"status\";N;s:13:\"retry_attempt\";N;s:8:\"retry_at\";N;s:5:\"error\";N;}',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39'),
(4,	'subscription',	'a:1:{s:5:\"pages\";a:3:{s:6:\"manage\";s:1:\"4\";s:11:\"unsubscribe\";s:1:\"4\";s:12:\"confirmation\";s:1:\"4\";}}',	'2017-10-30 00:57:39',	'2017-10-30 00:58:13'),
(5,	'db_version',	'3.0.7',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39'),
(6,	'premium_db_version',	'3.0.0',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39'),
(7,	'version',	'3.0.8',	'2017-10-30 00:57:40',	'2017-10-30 00:57:40'),
(8,	'mailpoet_migration_complete',	'1',	'2017-10-30 00:57:45',	'2017-10-30 00:57:45'),
(9,	'mta_group',	'smtp',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(10,	'mailpoet_smtp_provider',	'manual',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(11,	'mta',	'a:13:{s:6:\"method\";s:4:\"SMTP\";s:9:\"frequency\";a:2:{s:6:\"emails\";s:2:\"25\";s:8:\"interval\";s:1:\"5\";}s:16:\"mailpoet_api_key\";s:0:\"\";s:4:\"host\";s:7:\"mailhog\";s:4:\"port\";s:4:\"1025\";s:6:\"region\";s:9:\"us-east-1\";s:10:\"access_key\";s:0:\"\";s:10:\"secret_key\";s:0:\"\";s:7:\"api_key\";s:0:\"\";s:5:\"login\";s:0:\"\";s:8:\"password\";s:0:\"\";s:10:\"encryption\";s:0:\"\";s:14:\"authentication\";s:1:\"1\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(12,	'smtp_provider',	'manual',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(13,	'web_host',	'manual',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(14,	'mailpoet_sending_frequency',	'manual',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(15,	'reply_to',	'a:2:{s:4:\"name\";s:0:\"\";s:7:\"address\";s:0:\"\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(16,	'notification',	'a:1:{s:7:\"address\";s:0:\"\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(17,	'subscribe',	'a:1:{s:10:\"on_comment\";a:1:{s:5:\"label\";s:32:\"Yes, add me to your mailing list\";}}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(18,	'signup_confirmation',	'a:5:{s:7:\"enabled\";s:1:\"1\";s:4:\"from\";a:2:{s:4:\"name\";s:0:\"\";s:7:\"address\";s:0:\"\";}s:8:\"reply_to\";a:2:{s:4:\"name\";s:0:\"\";s:7:\"address\";s:0:\"\";}s:7:\"subject\";s:29:\"Confirm your subscription to \";s:4:\"body\";s:253:\"Hello!\r\n\r\nHurray! You\'ve subscribed to our site.\r\n\r\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \r\n\r\n[activation_link]Click here to confirm your subscription.[/activation_link]\r\n\r\nThank you,\r\n\r\nThe Team\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(19,	'bounce',	'a:1:{s:7:\"address\";s:0:\"\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(20,	'cron_trigger',	'a:1:{s:6:\"method\";s:9:\"WordPress\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(21,	'tracking',	'a:1:{s:7:\"enabled\";s:1:\"1\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(22,	'analytics',	'a:1:{s:7:\"enabled\";s:0:\"\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(23,	'premium',	'a:1:{s:11:\"premium_key\";s:0:\"\";}',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(24,	'user_seen_editor_tutorial1',	'1',	'2017-10-30 00:58:13',	'2017-10-30 00:58:13'),
(25,	'display_nps_poll',	'0',	'2018-12-13 14:20:00',	'2018-12-13 14:20:00'),
(26,	'captcha',	'a:3:{s:20:"recaptcha_site_token";s:0:"";s:22:"recaptcha_secret_token";s:0:"";s:4:"type";s:0:"";}',	'2019-07-11 15:35:00',	'2019-07-11 15:35:00');

DROP TABLE IF EXISTS `mp_mailpoet_statistics_clicks`;
CREATE TABLE `mp_mailpoet_statistics_clicks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `queue_id` int(11) unsigned NOT NULL,
  `link_id` int(11) unsigned NOT NULL,
  `count` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `newsletter_id` (`newsletter_id`),
  KEY `queue_id` (`queue_id`),
  KEY `subscriber_id` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_statistics_forms`;
CREATE TABLE `mp_mailpoet_statistics_forms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_subscriber` (`form_id`,`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_statistics_newsletters`;
CREATE TABLE `mp_mailpoet_statistics_newsletters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `queue_id` int(11) unsigned NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY subscriber_id (subscriber_id),
  KEY `newsletter_id` (`newsletter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_statistics_opens`;
CREATE TABLE `mp_mailpoet_statistics_opens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `queue_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `newsletter_id` (`newsletter_id`),
  KEY `queue_id` (`queue_id`),
  KEY `subscriber_id` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_statistics_unsubscribes`;
CREATE TABLE `mp_mailpoet_statistics_unsubscribes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `subscriber_id` int(11) unsigned NOT NULL,
  `queue_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `newsletter_id` (`newsletter_id`),
  KEY `queue_id` (`queue_id`),
  KEY `subscriber_id` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_subscribers`;
CREATE TABLE `mp_mailpoet_subscribers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wp_user_id` bigint(20) DEFAULT NULL,
  `first_name` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `last_name` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'unconfirmed',
  `subscribed_ip` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `confirmed_ip` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `unconfirmed_data` longtext COLLATE utf8mb4_unicode_520_ci,
  `is_woocommerce_user` int(1) NOT NULL DEFAULT 0,
  `source` ENUM("form", "imported", "administrator", "api", "wordpress_user", "unknown") DEFAULT "unknown",
  `count_confirmations` int(11) unsigned NOT NULL DEFAULT 0,
  `unsubscribe_token` varchar(15) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY unsubscribe_token (unsubscribe_token),
  KEY `wp_user_id` (`wp_user_id`),
  KEY `status_deleted_at` (`status`, `deleted_at`),
  KEY updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_subscribers` (`id`, `wp_user_id`, `first_name`, `last_name`, `email`, `status`, `subscribed_ip`, `confirmed_ip`, `confirmed_at`, `created_at`, `updated_at`, `deleted_at`, `unconfirmed_data`, `unsubscribe_token`) VALUES
(1,	1,	'admin',	'',	'wp@example.com',	'subscribed',	NULL,	NULL,	NULL,	'2017-10-30 00:57:39',	'2017-10-30 00:57:39',	NULL,	NULL, 'randomtokennum4'),
(2,	NULL,	'first',	'last',	'subscriber@example.com',	'subscribed',	NULL,	NULL,	NULL,	'2017-11-16 10:39:00',	'2017-11-16 10:39:00',	NULL,	NULL, 'randomtokennum5');

DROP TABLE IF EXISTS `mp_mailpoet_subscriber_custom_field`;
CREATE TABLE `mp_mailpoet_subscriber_custom_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_id` int(11) unsigned NOT NULL,
  `custom_field_id` int(11) unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriber_id_custom_field_id` (`subscriber_id`,`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_subscriber_ips`;
CREATE TABLE `mp_mailpoet_subscriber_ips` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`created_at`,`ip`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_mailpoet_subscriber_segment`;
CREATE TABLE `mp_mailpoet_subscriber_segment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_id` int(11) unsigned NOT NULL,
  `segment_id` int(11) unsigned NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'subscribed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriber_segment` (`subscriber_id`,`segment_id`),
  KEY `segment_id` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_mailpoet_subscriber_segment` (`id`, `subscriber_id`, `segment_id`, `status`, `created_at`, `updated_at`) VALUES
(1,	1,	1,	'subscribed',	'2017-10-30 00:57:39',	'2017-10-30 00:57:39');

DROP TABLE IF EXISTS `mp_options`;
CREATE TABLE `mp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1,'siteurl','http://test.local','yes'),
(2,'home','http://test.local','yes'),
(3,'blogname','MP Dev','yes'),
(4,'blogdescription','Just another WordPress site','yes'),
(5,'users_can_register','1','yes'),
(6,'admin_email','test@example.com','yes'),
(7,'start_of_week','1','yes'),
(8,'use_balanceTags','0','yes'),
(9,'use_smilies','1','yes'),
(10,'require_name_email','1','yes'),
(11,'comments_notify','1','yes'),
(12,'posts_per_rss','10','yes'),
(13,'rss_use_excerpt','0','yes'),
(14,'mailserver_url','mail.example.com','yes'),
(15,'mailserver_login','login@example.com','yes'),
(16,'mailserver_pass','password','yes'),
(17,'mailserver_port','110','yes'),
(18,'default_category','1','yes'),
(19,'default_comment_status','open','yes'),
(20,'default_ping_status','open','yes'),
(21,'default_pingback_flag','0','yes'),
(22,'posts_per_page','10','yes'),
(23,'date_format','F j, Y','yes'),
(24,'time_format','g:i a','yes'),
(25,'links_updated_date_format','F j, Y g:i a','yes'),
(26,'comment_moderation','0','yes'),
(27,'moderation_notify','1','yes'),
(28,'permalink_structure','/%year%/%monthnum%/%day%/%postname%/','yes'),
(29,'rewrite_rules','a:157:{s:24:\"^wc-auth/v([1]{1})/(.*)?\";s:63:\"index.php?wc-auth-version=$matches[1]&wc-auth-route=$matches[2]\";s:22:\"^wc-api/v([1-3]{1})/?$\";s:51:\"index.php?wc-api-version=$matches[1]&wc-api-route=/\";s:24:\"^wc-api/v([1-3]{1})(.*)?\";s:61:\"index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]\";s:7:\"shop/?$\";s:27:\"index.php?post_type=product\";s:37:\"shop/feed/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:32:\"shop/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:24:\"shop/page/([0-9]{1,})/?$\";s:45:\"index.php?post_type=product&paged=$matches[1]\";s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:32:\"category/(.+?)/wc-api(/(.*))?/?$\";s:54:\"index.php?category_name=$matches[1]&wc-api=$matches[3]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:29:\"tag/([^/]+)/wc-api(/(.*))?/?$\";s:44:\"index.php?tag=$matches[1]&wc-api=$matches[3]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:55:\"product-category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:50:\"product-category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:31:\"product-category/(.+?)/embed/?$\";s:44:\"index.php?product_cat=$matches[1]&embed=true\";s:43:\"product-category/(.+?)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_cat=$matches[1]&paged=$matches[2]\";s:25:\"product-category/(.+?)/?$\";s:33:\"index.php?product_cat=$matches[1]\";s:52:\"product-tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:47:\"product-tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:28:\"product-tag/([^/]+)/embed/?$\";s:44:\"index.php?product_tag=$matches[1]&embed=true\";s:40:\"product-tag/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_tag=$matches[1]&paged=$matches[2]\";s:22:\"product-tag/([^/]+)/?$\";s:33:\"index.php?product_tag=$matches[1]\";s:35:\"product/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:45:\"product/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:65:\"product/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:41:\"product/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:24:\"product/([^/]+)/embed/?$\";s:40:\"index.php?product=$matches[1]&embed=true\";s:28:\"product/([^/]+)/trackback/?$\";s:34:\"index.php?product=$matches[1]&tb=1\";s:48:\"product/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:43:\"product/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:36:\"product/([^/]+)/page/?([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&paged=$matches[2]\";s:43:\"product/([^/]+)/comment-page-([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&cpage=$matches[2]\";s:33:\"product/([^/]+)/wc-api(/(.*))?/?$\";s:48:\"index.php?product=$matches[1]&wc-api=$matches[3]\";s:39:\"product/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:50:\"product/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:32:\"product/([^/]+)(?:/([0-9]+))?/?$\";s:46:\"index.php?product=$matches[1]&page=$matches[2]\";s:24:\"product/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:34:\"product/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:54:\"product/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:30:\"product/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:17:\"wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:26:\"comments/wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:29:\"search/(.+)/wc-api(/(.*))?/?$\";s:42:\"index.php?s=$matches[1]&wc-api=$matches[3]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:32:\"author/([^/]+)/wc-api(/(.*))?/?$\";s:52:\"index.php?author_name=$matches[1]&wc-api=$matches[3]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:54:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:82:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&wc-api=$matches[5]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:41:\"([0-9]{4})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:66:\"index.php?year=$matches[1]&monthnum=$matches[2]&wc-api=$matches[4]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:28:\"([0-9]{4})/wc-api(/(.*))?/?$\";s:45:\"index.php?year=$matches[1]&wc-api=$matches[3]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:58:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:68:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:88:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:83:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:83:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:64:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:53:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/embed/?$\";s:91:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/trackback/?$\";s:85:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&tb=1\";s:77:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]\";s:72:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]\";s:65:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/page/?([0-9]{1,})/?$\";s:98:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&paged=$matches[5]\";s:72:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/comment-page-([0-9]{1,})/?$\";s:98:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&cpage=$matches[5]\";s:62:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/wc-api(/(.*))?/?$\";s:99:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&wc-api=$matches[6]\";s:62:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:73:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:61:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)(?:/([0-9]+))?/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]\";s:47:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:57:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:77:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:53:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&cpage=$matches[4]\";s:51:\"([0-9]{4})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&cpage=$matches[3]\";s:38:\"([0-9]{4})/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&cpage=$matches[2]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:25:\"(.?.+?)/wc-api(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&wc-api=$matches[3]\";s:28:\"(.?.+?)/order-pay(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&order-pay=$matches[3]\";s:33:\"(.?.+?)/order-received(/(.*))?/?$\";s:57:\"index.php?pagename=$matches[1]&order-received=$matches[3]\";s:25:\"(.?.+?)/orders(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&orders=$matches[3]\";s:29:\"(.?.+?)/view-order(/(.*))?/?$\";s:53:\"index.php?pagename=$matches[1]&view-order=$matches[3]\";s:28:\"(.?.+?)/downloads(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&downloads=$matches[3]\";s:31:\"(.?.+?)/edit-account(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-account=$matches[3]\";s:31:\"(.?.+?)/edit-address(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-address=$matches[3]\";s:34:\"(.?.+?)/payment-methods(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&payment-methods=$matches[3]\";s:32:\"(.?.+?)/lost-password(/(.*))?/?$\";s:56:\"index.php?pagename=$matches[1]&lost-password=$matches[3]\";s:34:\"(.?.+?)/customer-logout(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&customer-logout=$matches[3]\";s:37:\"(.?.+?)/add-payment-method(/(.*))?/?$\";s:61:\"index.php?pagename=$matches[1]&add-payment-method=$matches[3]\";s:40:\"(.?.+?)/delete-payment-method(/(.*))?/?$\";s:64:\"index.php?pagename=$matches[1]&delete-payment-method=$matches[3]\";s:45:\"(.?.+?)/set-default-payment-method(/(.*))?/?$\";s:69:\"index.php?pagename=$matches[1]&set-default-payment-method=$matches[3]\";s:31:\".?.+?/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\".?.+?/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";}','yes'),
(30,'hack_file','0','yes'),
(31,'blog_charset','UTF-8','yes'),
(32,'moderation_keys','','no'),
(33,'active_plugins','a:2:{i:0;s:37:\"mailpoet-premium/mailpoet-premium.php\";i:1;s:21:\"mailpoet/mailpoet.php\";}','yes'),
(34,'category_base','','yes'),
(35,'ping_sites','http://rpc.pingomatic.com/','yes'),
(36,'comment_max_links','2','yes'),
(37,'gmt_offset','0','yes'),
(38,'default_email_category','1','yes'),
(39,'recently_edited','a:2:{i:0;s:74:\"Z:\\home\\mpdev\\www/wp-content/plugins/mailpoet-premium/mailpoet-premium.php\";i:1;s:0:\"\";}','no'),
(40,'template','twentysixteen','yes'),
(41,'stylesheet','twentysixteen','yes'),
(42,'comment_whitelist','1','yes'),
(43,'blacklist_keys','','no'),
(44,'comment_registration','0','yes'),
(45,'html_type','text/html','yes'),
(46,'use_trackback','0','yes'),
(47,'default_role','subscriber','yes'),
(48,'db_version','43764','yes'),
(49,'uploads_use_yearmonth_folders','1','yes'),
(50,'upload_path','','yes'),
(51,'blog_public','0','yes'),
(52,'default_link_category','2','yes'),
(53,'show_on_front','posts','yes'),
(54,'tag_base','','yes'),
(55,'show_avatars','1','yes'),
(56,'avatar_rating','G','yes'),
(57,'upload_url_path','','yes'),
(58,'thumbnail_size_w','150','yes'),
(59,'thumbnail_size_h','150','yes'),
(60,'thumbnail_crop','1','yes'),
(61,'medium_size_w','300','yes'),
(62,'medium_size_h','300','yes'),
(63,'avatar_default','mystery','yes'),
(64,'large_size_w','1024','yes'),
(65,'large_size_h','1024','yes'),
(66,'image_default_link_type','none','yes'),
(67,'image_default_size','','yes'),
(68,'image_default_align','','yes'),
(69,'close_comments_for_old_posts','0','yes'),
(70,'close_comments_days_old','14','yes'),
(71,'thread_comments','1','yes'),
(72,'thread_comments_depth','5','yes'),
(73,'page_comments','0','yes'),
(74,'comments_per_page','50','yes'),
(75,'default_comments_page','newest','yes'),
(76,'comment_order','asc','yes'),
(77,'sticky_posts','a:0:{}','yes'),
(78,'widget_categories','a:2:{i:2;a:4:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:12:\"hierarchical\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),
(79,'widget_text','a:0:{}','yes'),
(80,'widget_rss','a:0:{}','yes'),
(81,'uninstall_plugins','a:0:{}','no'),
(82,'timezone_string','','yes'),
(83,'page_for_posts','0','yes'),
(84,'page_on_front','0','yes'),
(85,'default_post_format','0','yes'),
(86,'link_manager_enabled','0','yes'),
(87,'finished_splitting_shared_terms','1','yes'),
(88,'site_icon','0','yes'),
(89,'medium_large_size_w','768','yes'),
(90,'medium_large_size_h','0','yes'),
(91,'initial_db_version','37965','yes'),
(92,'mp_user_roles','a:7:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:126:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:18:\"wysija_newsletters\";b:1;s:18:\"wysija_subscribers\";b:1;s:13:\"wysija_config\";b:1;s:16:\"wysija_theme_tab\";b:1;s:16:\"wysija_style_tab\";b:1;s:22:\"wysija_stats_dashboard\";b:1;s:28:\"mailpoet_access_plugin_admin\";b:1;s:24:\"mailpoet_manage_settings\";b:1;s:22:\"mailpoet_manage_emails\";b:1;s:27:\"mailpoet_manage_subscribers\";b:1;s:21:\"mailpoet_manage_forms\";b:1;s:24:\"mailpoet_manage_segments\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:36:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:28:\"mailpoet_access_plugin_admin\";b:1;s:22:\"mailpoet_manage_emails\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}s:8:\"customer\";a:2:{s:4:\"name\";s:8:\"Customer\";s:12:\"capabilities\";a:1:{s:4:\"read\";b:1;}}s:12:\"shop_manager\";a:2:{s:4:\"name\";s:12:\"Shop manager\";s:12:\"capabilities\";a:92:{s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:4:\"read\";b:1;s:18:\"read_private_pages\";b:1;s:18:\"read_private_posts\";b:1;s:10:\"edit_posts\";b:1;s:10:\"edit_pages\";b:1;s:20:\"edit_published_posts\";b:1;s:20:\"edit_published_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"edit_private_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:17:\"edit_others_pages\";b:1;s:13:\"publish_posts\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_posts\";b:1;s:12:\"delete_pages\";b:1;s:20:\"delete_private_pages\";b:1;s:20:\"delete_private_posts\";b:1;s:22:\"delete_published_pages\";b:1;s:22:\"delete_published_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:19:\"delete_others_pages\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:17:\"moderate_comments\";b:1;s:12:\"upload_files\";b:1;s:6:\"export\";b:1;s:6:\"import\";b:1;s:10:\"list_users\";b:1;s:18:\"edit_theme_options\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}}','yes'),
(93,'widget_search','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),
(94,'widget_recent-posts','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),
(95,'widget_recent-comments','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),
(96,'widget_archives','a:2:{i:2;a:3:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),
(97,'widget_meta','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),
(98,'sidebars_widgets','a:5:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:8:{i:0;s:15:\"mailpoet_form-2\";i:1;s:15:\"mailpoet_form-5\";i:2;s:8:\"search-2\";i:3;s:14:\"recent-posts-2\";i:4;s:17:\"recent-comments-2\";i:5;s:10:\"archives-2\";i:6;s:12:\"categories-2\";i:7;s:6:\"meta-2\";}s:9:\"sidebar-2\";a:1:{i:0;s:15:\"mailpoet_form-3\";}s:9:\"sidebar-3\";a:1:{i:0;s:15:\"mailpoet_form-4\";}s:13:\"array_version\";i:3;}','yes'),
(99,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(100,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(101,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(102,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(103,'cron','a:14:{i:1553008887;a:1:{s:26:\"action_scheduler_run_queue\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:12:\"every_minute\";s:4:\"args\";a:0:{}s:8:\"interval\";i:60;}}}i:1553010747;a:1:{s:32:\"woocommerce_cancel_unpaid_orders\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1553011204;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1553017947;a:1:{s:24:\"woocommerce_cleanup_logs\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553028747;a:1:{s:28:\"woocommerce_cleanup_sessions\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1553040000;a:1:{s:27:\"woocommerce_scheduled_sales\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553048213;a:3:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1553065642;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553091431;a:1:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553093543;a:1:{s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553093547;a:1:{s:33:\"woocommerce_cleanup_personal_data\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1553093557;a:1:{s:30:\"woocommerce_tracker_send_event\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1554163200;a:1:{s:25:\"woocommerce_geoip_updater\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:7:\"monthly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:2635200;}}}s:7:\"version\";i:2;}','yes'),
(499,'fresh_site','0','yes'),
(393,'theme_mods_twentysixteen','a:1:{s:18:\"custom_css_post_id\";i:-1;}','yes'),
(388,'db_upgraded','','yes'),
(1219,'_site_transient_timeout_theme_roots','1553008926','no'),
(1220,'_site_transient_theme_roots','a:3:{s:14:\"twentynineteen\";s:7:\"/themes\";s:15:\"twentyseventeen\";s:7:\"/themes\";s:13:\"twentysixteen\";s:7:\"/themes\";}','no'),
(126,'recently_activated','a:0:{}','yes'),
(352,'mailpoet_db_version','3.0.0-rc.2.0.2','yes'),
(133,'widget_mailpoet_form','a:5:{i:2;a:2:{s:5:\"title\";s:27:\"Subscribe to Our Newsletter\";s:4:\"form\";i:4;}i:3;a:2:{s:5:\"title\";s:19:\"Subscribe to Form 1\";s:4:\"form\";i:2;}i:4;a:2:{s:5:\"title\";s:19:\"Subscribe to Form 2\";s:4:\"form\";i:3;}i:5;a:2:{s:5:\"title\";s:27:\"Subscribe to Our Newsletter\";s:4:\"form\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),
(218,'WPLANG','','yes'),
(1231,'_transient_timeout_feed_d117b5738fbd35bd8c0391cda1f2b5d9','1553050337','no'),
(820,'widget_wysija','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(821,'wysija_post_type_updated','1486149886','yes'),
(822,'wysija_post_type_created','1486149886','yes'),
(823,'installation_step','16','yes'),
(824,'wysija','YToxMDQ6e3M6OToiZnJvbV9uYW1lIjtzOjQ6InRlc3QiO3M6MTI6InJlcGx5dG9fbmFtZSI7czo0OiJ0ZXN0IjtzOjE1OiJlbWFpbHNfbm90aWZpZWQiO3M6MTQ6IndwQGV4YW1wbGUuY29tIjtzOjEwOiJmcm9tX2VtYWlsIjtzOjEwOiJpbmZvQG1wZGV2IjtzOjEzOiJyZXBseXRvX2VtYWlsIjtzOjEwOiJpbmZvQG1wZGV2IjtzOjE1OiJkZWZhdWx0X2xpc3RfaWQiO2k6NTtzOjE3OiJ0b3RhbF9zdWJzY3JpYmVycyI7czoxOiIyIjtzOjE2OiJpbXBvcnR3cF9saXN0X2lkIjtpOjY7czoxODoiY29uZmlybV9lbWFpbF9saW5rIjtpOjQ1OTtzOjEyOiJ1cGxvYWRmb2xkZXIiO3M6NDQ6Ilo6XGhvbWVcbXBkZXZcd3d3L3dwLWNvbnRlbnQvdXBsb2Fkc1x3eXNpamFcIjtzOjk6InVwbG9hZHVybCI7czozOToiaHR0cDovL21wZGV2L3dwLWNvbnRlbnQvdXBsb2Fkcy93eXNpamEvIjtzOjE2OiJjb25maXJtX2VtYWlsX2lkIjtpOjY7czo5OiJpbnN0YWxsZWQiO2I6MTtzOjIwOiJtYW5hZ2Vfc3Vic2NyaXB0aW9ucyI7aToxO3M6MTQ6Imluc3RhbGxlZF90aW1lIjtpOjE0ODYxNDk4OTQ7czoxNzoid3lzaWphX2RiX3ZlcnNpb24iO3M6NToiMi43LjciO3M6MTE6ImRraW1fZG9tYWluIjtzOjU6Im1wZGV2IjtzOjE2OiJ3eXNpamFfd2hhdHNfbmV3IjtzOjU6IjIuNy44IjtzOjE1OiJjb21wYW55X2FkZHJlc3MiO3M6MDoiIjtzOjE2OiJ1bnN1YnNjcmliZV9wYWdlIjtzOjM6IjQ1OSI7czoxNzoiY29uZmlybWF0aW9uX3BhZ2UiO3M6MzoiNDU5IjtzOjk6InNtdHBfaG9zdCI7czowOiIiO3M6MTA6InNtdHBfbG9naW4iO3M6NDoidGVzdCI7czoxMzoic210cF9wYXNzd29yZCI7czoxMjoiU3U0YXJvNGthNDAwIjtzOjExOiJzbXRwX3NlY3VyZSI7czoxOiIwIjtzOjEwOiJ0ZXN0X21haWxzIjtzOjE0OiJ3cEBleGFtcGxlLmNvbSI7czoyMToic2VuZGluZ19lbWFpbHNfbnVtYmVyIjtzOjI6IjE4IjtzOjE5OiJzZW5kaW5nX2VtYWlsc19lYWNoIjtzOjExOiJmaWZ0ZWVuX21pbiI7czoxMjoiYm91bmNlX2VtYWlsIjtzOjA6IiI7czoyNzoibWFuYWdlX3N1YnNjcmlwdGlvbnNfbGlzdHNbIjtzOjE6IjUiO3M6MTg6InN1YnNjcmlwdGlvbnNfcGFnZSI7czozOiI0NTkiO3M6MTE6Imh0bWxfc291cmNlIjtzOjE6IjAiO3M6MTY6ImFyY2hpdmVfbGlua25hbWUiO3M6MTY6Ilt3eXNpamFfYXJjaGl2ZV0iO3M6MjY6InN1YnNjcmliZXJzX2NvdW50X2xpbmtuYW1lIjtzOjI2OiJbd3lzaWphX3N1YnNjcmliZXJzX2NvdW50XSI7czoyMToiY3Jvbl9wYWdlX2hpdF90cmlnZ2VyIjtpOjI7czo4OiJfd3Bub25jZSI7czoxMDoiZDdlOGRiZTcwYiI7czoxNjoiX3dwX2h0dHBfcmVmZXJlciI7czozODoiL3dwLWFkbWluL2FkbWluLnBocD9wYWdlPXd5c2lqYV9jb25maWciO3M6NjoiYWN0aW9uIjtzOjQ6InNhdmUiO3M6MTE6InJlZGlyZWN0dGFiIjtzOjA6IiI7czoxNzoic2VuZGluZ19lbWFpbHNfb2siO2I6MTtzOjExOiJwcmVtaXVtX2tleSI7czozMjoiYUhSMGNEb3ZMMjF3WkdWMk1UUTRPVFE0TWpRek1BPT0iO3M6MTE6InByZW1pdW1fdmFsIjtpOjE0ODk0ODI0MzA7czoxNzoicHJlbWl1bV9leHBpcmVfYXQiO2k6MTUyMTAxODQzMDtzOjEwOiJka2ltX3ByaXZrIjtzOjg4NzoiLS0tLS1CRUdJTiBSU0EgUFJJVkFURSBLRVktLS0tLQpNSUlDWFFJQkFBS0JnUURnK0p4R0lDTGdRcWNqcE85L2dTNkdXM1gxZiszVVZ0UVhzR2d3L3V2WVRTTi8yREVkCmgyejNuU0ZVMmRPVm9wbGdaYTZoclpsWmpwKzgyWTdRY2M3aDJFME1wY0NIRmlabGxLQVRKNEFqOWJxWWZ3RmYKRW11QzRyWWE4LzduRHFBTGVqSXNBdWZBYlUxbjQvTTkycHpvMWMyYklTeERiOHdndWFTY25mMzA0d0lEQVFBQgpBb0dCQUxDMDltTHFtUnBYb0ZzU0VZQ3dZbS9zWlRJSll6RFhaczZZcEs1ZmZiYXZtSU94dDVwL0ppczBnOXJYCklpZTF5UTE3c1BpVG1CRk5RdEVlZmR2aW1PRTVhS2JzSThJbTF1T0kyamtTUWNtSUFUZ2xNNEo3cU5XVm4zTG0KY3YyaTR2UkFGRTdOY3VNK2ZLeVRTL1p5eDRpdmovVzdnY3ZTTjRtbXhUVCtGbXpSQWtFQTlHdFdwSDZFMk53NgplWTdPc1NsaGgySVlEbHJmN1Q0ZlE2MTdIbDRobmk5TU4zZUlNL09HWUZyYzY2ZWRBR24wZ2p3eEVlc1hnekJOCnJtSDdSd0l2cXdKQkFPdWhZSnBkdU94T3diWlVKQm1XSDJ5SmFBanovN3FOZFVzWHVGWmh5VjBWQ3VWemI0bVUKdElCUVRpWXJaMmpPdm9XazFUQUxacDNjNkc2R0laZ3NkNmtDUUJpNUJzR0t2cHRFNDNGS1BhUHo0SmFXR0lMVApORlZGOUZtZklaWDN4WVMvbWdEK1NUdWdCVmFYdWtMbjZGeVRXeFVWUzQxWmJ6NW8wMkt4TEg2SlBSTUNRQjVtCklIZHAxZnl1b0hFc1k4ZmxSVUtVYTVhVUhBN3VSdzZjRGMwZktvSld2NlFnZzJoRmhnL3p6RkZDVWtJRVFqSXQKdE05UStUa3VrZElJZmZjSzdaa0NRUURqbU5NL2hiTmJzQ2pBRDVHWDlBSzdXNVRLRm9FZjFVZGI5c29uN3pXTgpMQUJOa0gySEJxSzh3V1kxVjR0U3RUSFNzNkQzMHptWjdOYS9OZVA2eGdndQotLS0tLUVORCBSU0EgUFJJVkFURSBLRVktLS0tLQoiO3M6OToiZGtpbV9wdWJrIjtzOjIxNjoiTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FEZytKeEdJQ0xnUXFjanBPOS9nUzZHVzNYMWYrM1VWdFFYc0dndy91dllUU04vMkRFZGgyejNuU0ZVMmRPVm9wbGdaYTZoclpsWmpwKzgyWTdRY2M3aDJFME1wY0NIRmlabGxLQVRKNEFqOWJxWWZ3RmZFbXVDNHJZYTgvN25EcUFMZWpJc0F1ZkFiVTFuNC9NOTJwem8xYzJiSVN4RGI4d2d1YVNjbmYzMDR3SURBUUFCIjtzOjk6ImRraW1fMTAyNCI7aToxO3M6MjQ6ImVtYWlsc19ub3RpZmllZF93aGVuX3N1YiI7YjowO3M6Mjc6ImVtYWlsc19ub3RpZmllZF93aGVuX2JvdW5jZSI7YjowO3M6MzM6ImVtYWlsc19ub3RpZmllZF93aGVuX2RhaWx5c3VtbWFyeSI7YjowO3M6MTk6ImJvdW5jZV9wcm9jZXNzX2F1dG8iO2I6MDtzOjIyOiJtc19ib3VuY2VfcHJvY2Vzc19hdXRvIjtiOjA7czo5OiJzaGFyZWRhdGEiO2I6MDtzOjExOiJka2ltX2FjdGl2ZSI7aToxO3M6OToic210cF9yZXN0IjtiOjA7czoxMjoibXNfc210cF9yZXN0IjtiOjA7czoxNDoiZGVidWdfbG9nX2Nyb24iO2I6MDtzOjIwOiJkZWJ1Z19sb2dfcG9zdF9ub3RpZiI7YjowO3M6MjI6ImRlYnVnX2xvZ19xdWVyeV9lcnJvcnMiO2I6MDtzOjIzOiJkZWJ1Z19sb2dfcXVldWVfcHJvY2VzcyI7YjowO3M6MTY6ImRlYnVnX2xvZ19tYW51YWwiO2I6MDtzOjI2OiJtYW5hZ2Vfc3Vic2NyaXB0aW9uc19saXN0cyI7YTozOntpOjA7czoxOiIxIjtpOjE7czoxOiIzIjtpOjI7czoxOiI1Ijt9czoxMToiYm91bmNlX2hvc3QiO3M6MDoiIjtzOjEyOiJib3VuY2VfbG9naW4iO3M6MDoiIjtzOjE1OiJib3VuY2VfcGFzc3dvcmQiO3M6MDoiIjtzOjI0OiJib3VuY2VfY29ubmVjdGlvbl9tZXRob2QiO3M6NDoicG9wMyI7czoyNDoiYm91bmNlX2Nvbm5lY3Rpb25fc2VjdXJlIjtzOjA6IiI7czoyNDoiYm91bmNlX3J1bGVfbWFpbGJveF9mdWxsIjtzOjA6IiI7czoyMjoiYm91bmNlX3J1bGVfbWFpbGJveF9uYSI7czowOiIiO3M6Mzc6ImJvdW5jZV9ydWxlX2FjdGlvbl9yZXF1aXJlZF9mb3J3YXJkdG8iO3M6MTQ6IndwQGV4YW1wbGUuY29tIjtzOjMyOiJib3VuY2VfcnVsZV9ibG9ja2VkX2lwX2ZvcndhcmR0byI7czoxNDoid3BAZXhhbXBsZS5jb20iO3M6MzA6ImJvdW5jZV9ydWxlX25vaGFuZGxlX2ZvcndhcmR0byI7czoxNDoid3BAZXhhbXBsZS5jb20iO3M6MTM6ImFyY2hpdmVfbGlzdHMiO2E6Mzp7aTo1O2I6MDtpOjM7YjowO2k6MTtiOjA7fXM6Mzg6InJvbGVzY2FwLS0tYWRtaW5pc3RyYXRvci0tLW5ld3NsZXR0ZXJzIjtiOjA7czozMToicm9sZXNjYXAtLS1lZGl0b3ItLS1uZXdzbGV0dGVycyI7YjowO3M6MzE6InJvbGVzY2FwLS0tYXV0aG9yLS0tbmV3c2xldHRlcnMiO2I6MDtzOjM2OiJyb2xlc2NhcC0tLWNvbnRyaWJ1dG9yLS0tbmV3c2xldHRlcnMiO2I6MDtzOjM1OiJyb2xlc2NhcC0tLXN1YnNjcmliZXItLS1uZXdzbGV0dGVycyI7YjowO3M6Mzg6InJvbGVzY2FwLS0tYWRtaW5pc3RyYXRvci0tLXN1YnNjcmliZXJzIjtiOjA7czozMToicm9sZXNjYXAtLS1lZGl0b3ItLS1zdWJzY3JpYmVycyI7YjowO3M6MzE6InJvbGVzY2FwLS0tYXV0aG9yLS0tc3Vic2NyaWJlcnMiO2I6MDtzOjM2OiJyb2xlc2NhcC0tLWNvbnRyaWJ1dG9yLS0tc3Vic2NyaWJlcnMiO2I6MDtzOjM1OiJyb2xlc2NhcC0tLXN1YnNjcmliZXItLS1zdWJzY3JpYmVycyI7YjowO3M6NDI6InJvbGVzY2FwLS0tYWRtaW5pc3RyYXRvci0tLXN0YXRzX2Rhc2hib2FyZCI7YjowO3M6MzU6InJvbGVzY2FwLS0tZWRpdG9yLS0tc3RhdHNfZGFzaGJvYXJkIjtiOjA7czozNToicm9sZXNjYXAtLS1hdXRob3ItLS1zdGF0c19kYXNoYm9hcmQiO2I6MDtzOjQwOiJyb2xlc2NhcC0tLWNvbnRyaWJ1dG9yLS0tc3RhdHNfZGFzaGJvYXJkIjtiOjA7czozOToicm9sZXNjYXAtLS1zdWJzY3JpYmVyLS0tc3RhdHNfZGFzaGJvYXJkIjtiOjA7czozMzoicm9sZXNjYXAtLS1hZG1pbmlzdHJhdG9yLS0tY29uZmlnIjtiOjA7czoyNjoicm9sZXNjYXAtLS1lZGl0b3ItLS1jb25maWciO2I6MDtzOjI2OiJyb2xlc2NhcC0tLWF1dGhvci0tLWNvbmZpZyI7YjowO3M6MzE6InJvbGVzY2FwLS0tY29udHJpYnV0b3ItLS1jb25maWciO2I6MDtzOjMwOiJyb2xlc2NhcC0tLXN1YnNjcmliZXItLS1jb25maWciO2I6MDtzOjM2OiJyb2xlc2NhcC0tLWFkbWluaXN0cmF0b3ItLS10aGVtZV90YWIiO2I6MDtzOjI5OiJyb2xlc2NhcC0tLWVkaXRvci0tLXRoZW1lX3RhYiI7YjowO3M6Mjk6InJvbGVzY2FwLS0tYXV0aG9yLS0tdGhlbWVfdGFiIjtiOjA7czozNDoicm9sZXNjYXAtLS1jb250cmlidXRvci0tLXRoZW1lX3RhYiI7YjowO3M6MzM6InJvbGVzY2FwLS0tc3Vic2NyaWJlci0tLXRoZW1lX3RhYiI7YjowO3M6MzY6InJvbGVzY2FwLS0tYWRtaW5pc3RyYXRvci0tLXN0eWxlX3RhYiI7YjowO3M6Mjk6InJvbGVzY2FwLS0tZWRpdG9yLS0tc3R5bGVfdGFiIjtiOjA7czoyOToicm9sZXNjYXAtLS1hdXRob3ItLS1zdHlsZV90YWIiO2I6MDtzOjM0OiJyb2xlc2NhcC0tLWNvbnRyaWJ1dG9yLS0tc3R5bGVfdGFiIjtiOjA7czozMzoicm9sZXNjYXAtLS1zdWJzY3JpYmVyLS0tc3R5bGVfdGFiIjtiOjA7czoyNzoiYm91bmNlX3J1bGVfbWVzc2FnZV9kZWxheWVkIjtzOjA6IiI7czoyODoiYm91bmNlX3J1bGVfZmFpbGVkX3Blcm1hbmVudCI7czowOiIiO30=','yes'),
(825,'wysija_reinstall','0','no'),
(826,'wysija_schedules','a:5:{s:5:\"queue\";a:3:{s:13:\"next_schedule\";i:1486397257;s:13:\"prev_schedule\";b:0;s:7:\"running\";b:0;}s:6:\"bounce\";a:3:{s:13:\"next_schedule\";i:1486236303;s:13:\"prev_schedule\";i:0;s:7:\"running\";b:0;}s:5:\"daily\";a:3:{s:13:\"next_schedule\";i:1486451857;s:13:\"prev_schedule\";b:0;s:7:\"running\";b:0;}s:6:\"weekly\";a:3:{s:13:\"next_schedule\";i:1486754703;s:13:\"prev_schedule\";i:0;s:7:\"running\";b:0;}s:7:\"monthly\";a:3:{s:13:\"next_schedule\";i:1488569103;s:13:\"prev_schedule\";i:0;s:7:\"running\";b:0;}}','yes'),
(827,'wysija_last_php_cron_call','1486393654','yes'),
(829,'wysija_check_pn','1486393657.22','yes'),
(830,'wysija_last_scheduled_check','1486393657','yes'),
(854,'wysija_queries','','no'),
(855,'wysija_queries_errors','','no'),
(856,'wysija_msg','','no'),
(857,'wysijey','YTozOntzOjExOiJkb21haW5fbmFtZSI7czo1OiJtcGRldiI7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9tcGRldi93cC1hZG1pbi9hZG1pbi5waHAiO3M6ODoiY3Jvbl91cmwiO3M6OTc6Imh0dHA6Ly9tcGRldi93cC1jcm9uLnBocD9lOThkMWM2NGExN2NhZTUwODEzYjY3ODUzOTI3NGZmNyZhY3Rpb249d3lzaWphX2Nyb24mcHJvY2Vzcz1hbGwmc2lsZW50PTEiO30=','yes'),
(858,'wysicheck','','no'),
(859,'mpoet_frequency_set','1','yes'),
(860,'dkim_autosetup','','no'),
(953,'mailpoet_premium_db_version','3.0.0-rc.2.0.0','yes'),
(1196,'_transient_timeout_feed_b9388c83948825c1edaef0d856b7b109','1509968787','no'),
(1029,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1030,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1031,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1079,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1226,'_site_transient_timeout_community-events-6718ef04d3f46d7f6ff6aabe77f33591','1553050331','no'),
(1221,'_site_transient_update_themes','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1553007128;s:7:\"checked\";a:3:{s:14:\"twentynineteen\";s:3:\"1.0\";s:15:\"twentyseventeen\";s:3:\"1.8\";s:13:\"twentysixteen\";s:3:\"1.6\";}s:8:\"response\";a:3:{s:14:\"twentynineteen\";a:4:{s:5:\"theme\";s:14:\"twentynineteen\";s:11:\"new_version\";s:3:\"1.3\";s:3:\"url\";s:44:\"https://wordpress.org/themes/twentynineteen/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/theme/twentynineteen.1.3.zip\";}s:15:\"twentyseventeen\";a:4:{s:5:\"theme\";s:15:\"twentyseventeen\";s:11:\"new_version\";s:3:\"2.1\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentyseventeen/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentyseventeen.2.1.zip\";}s:13:\"twentysixteen\";a:4:{s:5:\"theme\";s:13:\"twentysixteen\";s:11:\"new_version\";s:3:\"1.9\";s:3:\"url\";s:43:\"https://wordpress.org/themes/twentysixteen/\";s:7:\"package\";s:59:\"https://downloads.wordpress.org/theme/twentysixteen.1.9.zip\";}}s:12:\"translations\";a:0:{}}','no'),
(1224,'_site_transient_browser_9e6dd57593edf67f066d8c211dedffb4','a:10:{s:4:\"name\";s:6:\"Chrome\";s:7:\"version\";s:12:\"72.0.3626.81\";s:8:\"platform\";s:5:\"Linux\";s:10:\"update_url\";s:29:\"https://www.google.com/chrome\";s:7:\"img_src\";s:43:\"http://s.w.org/images/browsers/chrome.png?1\";s:11:\"img_src_ssl\";s:44:\"https://s.w.org/images/browsers/chrome.png?1\";s:15:\"current_version\";s:2:\"18\";s:7:\"upgrade\";b:0;s:8:\"insecure\";b:0;s:6:\"mobile\";b:0;}','no'),
(1222,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1553007128;s:7:\"checked\";a:4:{s:19:\"akismet/akismet.php\";s:3:\"4.1\";s:9:\"hello.php\";s:5:\"1.7.1\";s:21:\"mailpoet/mailpoet.php\";s:6:\"3.21.1\";s:27:\"woocommerce/woocommerce.php\";s:5:\"3.5.6\";}s:8:\"response\";a:1:{s:19:\"akismet/akismet.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:21:\"w.org/plugins/akismet\";s:4:\"slug\";s:7:\"akismet\";s:6:\"plugin\";s:19:\"akismet/akismet.php\";s:11:\"new_version\";s:5:\"4.1.1\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/akismet/\";s:7:\"package\";s:56:\"https://downloads.wordpress.org/plugin/akismet.4.1.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:59:\"https://ps.w.org/akismet/assets/icon-256x256.png?rev=969272\";s:2:\"1x\";s:59:\"https://ps.w.org/akismet/assets/icon-128x128.png?rev=969272\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:61:\"https://ps.w.org/akismet/assets/banner-772x250.jpg?rev=479904\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:3:\"5.1\";s:12:\"requires_php\";b:0;s:13:\"compatibility\";O:8:\"stdClass\":0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:3:{s:9:\"hello.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:25:\"w.org/plugins/hello-dolly\";s:4:\"slug\";s:11:\"hello-dolly\";s:6:\"plugin\";s:9:\"hello.php\";s:11:\"new_version\";s:3:\"1.6\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/hello-dolly/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/hello-dolly.1.6.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-256x256.jpg?rev=2052855\";s:2:\"1x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-128x128.jpg?rev=2052855\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:66:\"https://ps.w.org/hello-dolly/assets/banner-772x250.jpg?rev=2052855\";}s:11:\"banners_rtl\";a:0:{}}s:21:\"mailpoet/mailpoet.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:22:\"w.org/plugins/mailpoet\";s:4:\"slug\";s:8:\"mailpoet\";s:6:\"plugin\";s:21:\"mailpoet/mailpoet.php\";s:11:\"new_version\";s:6:\"3.21.1\";s:3:\"url\";s:39:\"https://wordpress.org/plugins/mailpoet/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/mailpoet.3.21.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:61:\"https://ps.w.org/mailpoet/assets/icon-256x256.png?rev=1895745\";s:2:\"1x\";s:61:\"https://ps.w.org/mailpoet/assets/icon-128x128.png?rev=1706492\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/mailpoet/assets/banner-1544x500.png?rev=2046588\";s:2:\"1x\";s:63:\"https://ps.w.org/mailpoet/assets/banner-772x250.png?rev=2046588\";}s:11:\"banners_rtl\";a:0:{}}s:27:\"woocommerce/woocommerce.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:25:\"w.org/plugins/woocommerce\";s:4:\"slug\";s:11:\"woocommerce\";s:6:\"plugin\";s:27:\"woocommerce/woocommerce.php\";s:11:\"new_version\";s:5:\"3.5.6\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/woocommerce/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/woocommerce.3.5.6.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-256x256.png?rev=1440831\";s:2:\"1x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-128x128.png?rev=1440831\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/woocommerce/assets/banner-1544x500.png?rev=1629184\";s:2:\"1x\";s:66:\"https://ps.w.org/woocommerce/assets/banner-772x250.png?rev=1629184\";}s:11:\"banners_rtl\";a:0:{}}}}','no'),
(1214,'wp_page_for_privacy_policy','','yes'),
(1215,'show_comments_cookies_opt_in','0','yes'),
(1211,'auto_core_update_notified','a:4:{s:4:\"type\";s:4:\"fail\";s:5:\"email\";s:16:\"test@example.com\";s:7:\"version\";s:5:\"5.0.3\";s:9:\"timestamp\";i:1548939612;}','no'),
(1203,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1225,'can_compress_scripts','0','no'),
(1227,'_site_transient_community-events-6718ef04d3f46d7f6ff6aabe77f33591','a:2:{s:8:\"location\";a:1:{s:2:\"ip\";s:10:\"172.18.0.0\";}s:6:\"events\";a:2:{i:0;a:7:{s:4:\"type\";s:8:\"wordcamp\";s:5:\"title\";s:20:\"WordCamp Vienna 2019\";s:3:\"url\";s:33:\"https://2019.vienna.wordcamp.org/\";s:6:\"meetup\";N;s:10:\"meetup_url\";N;s:4:\"date\";s:19:\"2019-04-27 00:00:00\";s:8:\"location\";a:4:{s:8:\"location\";s:15:\"Vienna, Austria\";s:7:\"country\";s:2:\"AT\";s:8:\"latitude\";d:48.2172156;s:9:\"longitude\";d:16.3532407;}}i:1;a:7:{s:4:\"type\";s:8:\"wordcamp\";s:5:\"title\";s:15:\"WordCamp Europe\";s:3:\"url\";s:32:\"https://2019.europe.wordcamp.org\";s:6:\"meetup\";N;s:10:\"meetup_url\";N;s:4:\"date\";s:19:\"2019-06-21 00:00:00\";s:8:\"location\";a:4:{s:8:\"location\";s:15:\"WordCamp Europe\";s:7:\"country\";s:2:\"DE\";s:8:\"latitude\";d:52.473107;s:9:\"longitude\";d:13.4587819;}}}}','no'),
(1217,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:3:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:7:\"upgrade\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.1.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.1.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.1.1-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.1.1-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.1.1\";s:7:\"version\";s:5:\"5.1.1\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:0:\"\";}i:1;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.1.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.1.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.1.1-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.1.1-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.1.1\";s:7:\"version\";s:5:\"5.1.1\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:0:\"\";s:9:\"new_files\";s:1:\"1\";}i:2;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.0.4.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.0.4.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.0.4-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.0.4-new-bundled.zip\";s:7:\"partial\";s:69:\"https://downloads.wordpress.org/release/wordpress-5.0.4-partial-0.zip\";s:8:\"rollback\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.0.4-rollback-0.zip\";}s:7:\"current\";s:5:\"5.0.4\";s:7:\"version\";s:5:\"5.0.4\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:3:\"5.0\";s:9:\"new_files\";s:0:\"\";}}s:12:\"last_checked\";i:1553007126;s:15:\"version_checked\";s:3:\"5.0\";s:12:\"translations\";a:0:{}}','no'),
(1223,'_site_transient_timeout_browser_9e6dd57593edf67f066d8c211dedffb4','1553611929','no'),
(1228,'_transient_timeout_feed_ac0b00fe65abe10e0c5b588f3ed8c7ca','1553050334','no'),
(1229,'_transient_timeout_feed_mod_ac0b00fe65abe10e0c5b588f3ed8c7ca','1553050334','no'),
(1230,'_transient_feed_mod_ac0b00fe65abe10e0c5b588f3ed8c7ca','1553007134','no'),
(1232,'_transient_timeout_feed_mod_d117b5738fbd35bd8c0391cda1f2b5d9','1553050337','no'),
(1233,'_transient_feed_mod_d117b5738fbd35bd8c0391cda1f2b5d9','1553007137','no'),
(1234,'_transient_timeout_dash_v2_88ae138922fe95674369b1cb3d215a2b','1553050337','no'),
(1235,'_transient_dash_v2_88ae138922fe95674369b1cb3d215a2b','<div class=\"rss-widget\"><ul><li><a class=\'rsswidget\' href=\'https://wordpress.org/news/2019/03/one-third-of-the-web/\'>One-third of the web!</a></li></ul></div><div class=\"rss-widget\"><ul><li><a class=\'rsswidget\' href=\'https://wptavern.com/new-tools-for-theme-developers-theme-sniffer-plugin-and-automated-accessibility-testing\'>WPTavern: New Tools for Theme Developers: Theme Sniffer Plugin and Automated Accessibility Testing</a></li><li><a class=\'rsswidget\' href=\'https://wptavern.com/a-quick-introduction-to-wordpress-date-time-component\'>WPTavern: A Quick Introduction to WordPress’ Date/Time Component</a></li><li><a class=\'rsswidget\' href=\'https://wptavern.com/github-is-testing-commits-on-behalf-of-organizations\'>WPTavern: GitHub Is Testing Commits on Behalf of Organizations</a></li></ul></div>','no'),
(1236,'_transient_timeout_plugin_slugs','1553093543','no'),
(1237,'_transient_plugin_slugs','a:4:{i:0;s:19:\"akismet/akismet.php\";i:1;s:9:\"hello.php\";i:2;s:21:\"mailpoet/mailpoet.php\";i:3;s:27:\"woocommerce/woocommerce.php\";}','no'),
(1241,'woocommerce_store_address','Address','yes'),
(1242,'woocommerce_store_address_2','','yes'),
(1243,'woocommerce_store_city','Paris','yes'),
(1244,'woocommerce_default_country','FR:*','yes'),
(1245,'woocommerce_store_postcode','75000','yes'),
(1246,'woocommerce_allowed_countries','all','yes'),
(1247,'woocommerce_all_except_countries','','yes'),
(1248,'woocommerce_specific_allowed_countries','','yes'),
(1249,'woocommerce_ship_to_countries','','yes'),
(1250,'woocommerce_specific_ship_to_countries','','yes'),
(1251,'woocommerce_default_customer_address','geolocation','yes'),
(1252,'woocommerce_calc_taxes','no','yes'),
(1253,'woocommerce_enable_coupons','yes','yes'),
(1254,'woocommerce_calc_discounts_sequentially','no','no'),
(1255,'woocommerce_currency','EUR','yes'),
(1256,'woocommerce_currency_pos','right','yes'),
(1257,'woocommerce_price_thousand_sep',' ','yes'),
(1258,'woocommerce_price_decimal_sep',',','yes'),
(1259,'woocommerce_price_num_decimals','2','yes'),
(1260,'woocommerce_shop_page_id','472','yes'),
(1261,'woocommerce_cart_redirect_after_add','no','yes'),
(1262,'woocommerce_enable_ajax_add_to_cart','yes','yes'),
(1263,'woocommerce_placeholder_image','','yes'),
(1264,'woocommerce_weight_unit','kg','yes'),
(1265,'woocommerce_dimension_unit','cm','yes'),
(1266,'woocommerce_enable_reviews','yes','yes'),
(1267,'woocommerce_review_rating_verification_label','yes','no'),
(1268,'woocommerce_review_rating_verification_required','no','no'),
(1269,'woocommerce_enable_review_rating','yes','yes'),
(1270,'woocommerce_review_rating_required','yes','no'),
(1271,'woocommerce_manage_stock','yes','yes'),
(1272,'woocommerce_hold_stock_minutes','60','no'),
(1273,'woocommerce_notify_low_stock','yes','no'),
(1274,'woocommerce_notify_no_stock','yes','no'),
(1275,'woocommerce_stock_email_recipient','test@example.com','no'),
(1276,'woocommerce_notify_low_stock_amount','2','no'),
(1277,'woocommerce_notify_no_stock_amount','0','yes'),
(1278,'woocommerce_hide_out_of_stock_items','no','yes'),
(1279,'woocommerce_stock_format','','yes'),
(1280,'woocommerce_file_download_method','force','no'),
(1281,'woocommerce_downloads_require_login','no','no'),
(1282,'woocommerce_downloads_grant_access_after_payment','yes','no'),
(1283,'woocommerce_prices_include_tax','no','yes'),
(1284,'woocommerce_tax_based_on','shipping','yes'),
(1285,'woocommerce_shipping_tax_class','inherit','yes'),
(1286,'woocommerce_tax_round_at_subtotal','no','yes'),
(1287,'woocommerce_tax_classes','Reduced rate\nZero rate','yes'),
(1288,'woocommerce_tax_display_shop','excl','yes'),
(1289,'woocommerce_tax_display_cart','excl','yes'),
(1290,'woocommerce_price_display_suffix','','yes'),
(1291,'woocommerce_tax_total_display','itemized','no'),
(1292,'woocommerce_enable_shipping_calc','yes','no'),
(1293,'woocommerce_shipping_cost_requires_address','no','yes'),
(1294,'woocommerce_ship_to_destination','billing','no'),
(1295,'woocommerce_shipping_debug_mode','no','yes'),
(1296,'woocommerce_enable_guest_checkout','yes','no'),
(1297,'woocommerce_enable_checkout_login_reminder','yes','no'),
(1298,'woocommerce_enable_signup_and_login_from_checkout','yes','no'),
(1299,'woocommerce_enable_myaccount_registration','yes','no'),
(1300,'woocommerce_registration_generate_username','yes','no'),
(1301,'woocommerce_registration_generate_password','yes','no'),
(1302,'woocommerce_erasure_request_removes_order_data','no','no'),
(1303,'woocommerce_erasure_request_removes_download_data','no','no'),
(1304,'woocommerce_registration_privacy_policy_text','Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].','yes'),
(1305,'woocommerce_checkout_privacy_policy_text','Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].','yes'),
(1306,'woocommerce_delete_inactive_accounts','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
(1307,'woocommerce_trash_pending_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:4:\"days\";}','no'),
(1308,'woocommerce_trash_failed_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:4:\"days\";}','no'),
(1309,'woocommerce_trash_cancelled_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:4:\"days\";}','no'),
(1310,'woocommerce_anonymize_completed_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
(1311,'woocommerce_email_from_name','MP Dev','no'),
(1312,'woocommerce_email_from_address','test@example.com','no'),
(1313,'woocommerce_email_header_image','','no'),
(1314,'woocommerce_email_footer_text','{site_title}<br/>Built with <a href=\"https://woocommerce.com/\">WooCommerce</a>','no'),
(1315,'woocommerce_email_base_color','#96588a','no'),
(1316,'woocommerce_email_background_color','#f7f7f7','no'),
(1317,'woocommerce_email_body_background_color','#ffffff','no'),
(1318,'woocommerce_email_text_color','#3c3c3c','no'),
(1319,'woocommerce_cart_page_id','473','yes'),
(1320,'woocommerce_checkout_page_id','474','yes'),
(1321,'woocommerce_myaccount_page_id','475','yes'),
(1322,'woocommerce_terms_page_id','','no'),
(1323,'woocommerce_force_ssl_checkout','no','yes'),
(1324,'woocommerce_unforce_ssl_checkout','no','yes'),
(1325,'woocommerce_checkout_pay_endpoint','order-pay','yes'),
(1326,'woocommerce_checkout_order_received_endpoint','order-received','yes'),
(1327,'woocommerce_myaccount_add_payment_method_endpoint','add-payment-method','yes'),
(1328,'woocommerce_myaccount_delete_payment_method_endpoint','delete-payment-method','yes'),
(1329,'woocommerce_myaccount_set_default_payment_method_endpoint','set-default-payment-method','yes'),
(1330,'woocommerce_myaccount_orders_endpoint','orders','yes'),
(1331,'woocommerce_myaccount_view_order_endpoint','view-order','yes'),
(1332,'woocommerce_myaccount_downloads_endpoint','downloads','yes'),
(1333,'woocommerce_myaccount_edit_account_endpoint','edit-account','yes'),
(1334,'woocommerce_myaccount_edit_address_endpoint','edit-address','yes'),
(1335,'woocommerce_myaccount_payment_methods_endpoint','payment-methods','yes'),
(1336,'woocommerce_myaccount_lost_password_endpoint','lost-password','yes'),
(1337,'woocommerce_logout_endpoint','customer-logout','yes'),
(1338,'woocommerce_api_enabled','no','yes'),
(1339,'woocommerce_single_image_width','600','yes'),
(1340,'woocommerce_thumbnail_image_width','300','yes'),
(1341,'woocommerce_checkout_highlight_required_fields','yes','yes'),
(1342,'woocommerce_demo_store','no','no'),
(1343,'woocommerce_permalinks','a:5:{s:12:\"product_base\";s:7:\"product\";s:13:\"category_base\";s:16:\"product-category\";s:8:\"tag_base\";s:11:\"product-tag\";s:14:\"attribute_base\";s:0:\"\";s:22:\"use_verbose_page_rules\";b:0;}','yes'),
(1344,'current_theme_supports_woocommerce','yes','yes'),
(1345,'woocommerce_queue_flush_rewrite_rules','no','yes'),
(1346,'_transient_wc_attribute_taxonomies','a:0:{}','yes'),
(1347,'product_cat_children','a:0:{}','yes'),
(1348,'default_product_cat','15','yes'),
(1371,'woocommerce_meta_box_errors','a:0:{}','yes'),
(1351,'woocommerce_version','3.5.6','yes'),
(1352,'woocommerce_db_version','3.5.6','yes'),
(1353,'woocommerce_admin_notices','a:2:{i:1;s:20:\"no_secure_connection\";i:2;s:10:\"wootenberg\";}','yes'),
(1397,'_transient_timeout_external_ip_address_172.18.0.3','1553612036','no'),
(1354,'_transient_woocommerce_webhook_ids','a:0:{}','yes'),
(1355,'widget_woocommerce_widget_cart','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1356,'widget_woocommerce_layered_nav_filters','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1357,'widget_woocommerce_layered_nav','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1358,'widget_woocommerce_price_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1359,'widget_woocommerce_product_categories','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1360,'widget_woocommerce_product_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1361,'widget_woocommerce_product_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1362,'widget_woocommerce_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1363,'widget_woocommerce_recently_viewed_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1364,'widget_woocommerce_top_rated_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1365,'widget_woocommerce_recent_reviews','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1366,'widget_woocommerce_rating_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(1372,'_transient_timeout_external_ip_address_172.18.0.6','1553611948','no'),
(1379,'_transient_timeout_external_ip_address_127.0.0.1','1553611991','no'),
(1380,'_transient_external_ip_address_127.0.0.1','81.0.198.186','no'),
(1381,'_transient_is_multi_author','0','yes'),
(1377,'woocommerce_product_type','both','yes'),
(1378,'woocommerce_allow_tracking','no','yes'),
(1369,'_transient_wc_count_comments','O:8:\"stdClass\":7:{s:14:\"total_comments\";i:1;s:3:\"all\";i:1;s:8:\"approved\";s:1:\"1\";s:9:\"moderated\";i:0;s:4:\"spam\";i:0;s:5:\"trash\";i:0;s:12:\"post-trashed\";i:0;}','yes'),
(1370,'_transient_as_comment_count','O:8:\"stdClass\":7:{s:8:\"approved\";s:1:\"1\";s:14:\"total_comments\";i:1;s:3:\"all\";i:1;s:9:\"moderated\";i:0;s:4:\"spam\";i:0;s:5:\"trash\";i:0;s:12:\"post-trashed\";i:0;}','yes'),
(1373,'_transient_external_ip_address_172.18.0.6','81.0.198.186','no'),
(1382,'_transient_twentysixteen_categories','1','yes'),
(1383,'woocommerce_stripe_settings','a:3:{s:7:\"enabled\";s:2:\"no\";s:14:\"create_account\";b:0;s:5:\"email\";b:0;}','yes'),
(1384,'woocommerce_ppec_paypal_settings','a:2:{s:16:\"reroute_requests\";b:0;s:5:\"email\";b:0;}','yes'),
(1385,'woocommerce_cheque_settings','a:1:{s:7:\"enabled\";s:2:\"no\";}','yes'),
(1386,'woocommerce_bacs_settings','a:1:{s:7:\"enabled\";s:3:\"yes\";}','yes'),
(1387,'woocommerce_cod_settings','a:1:{s:7:\"enabled\";s:3:\"yes\";}','yes'),
(1389,'_transient_timeout_wc_report_sales_by_date','1553093630','no'),
(1390,'_transient_wc_report_sales_by_date','a:8:{s:32:\"2d26c70c51e0a6f3052a903145911c34\";a:0:{}s:32:\"59b41f9f45e111b37c3c0c77325518c6\";a:0:{}s:32:\"3102caf3cfd147a863ce10b0bbcde47d\";a:0:{}s:32:\"edded3790574fac74ea9d0bf54f4aeb5\";N;s:32:\"f4f58cc486464d2b7bd3fd5577ecbccd\";a:0:{}s:32:\"635a261daa3d170f67769e3fd33ecd56\";a:0:{}s:32:\"6643eca27d77344ab6e674f81b2e3d0a\";a:0:{}s:32:\"85484d83af1612e0d07a4fe7960ce3c4\";a:0:{}}','no'),
(1391,'_transient_timeout_wc_admin_report','1553093630','no'),
(1392,'_transient_wc_admin_report','a:1:{s:32:\"efbce62d2b510e2162366868d8c6bda9\";a:0:{}}','no'),
(1393,'_transient_timeout_wc_low_stock_count','1555599230','no'),
(1394,'_transient_wc_low_stock_count','0','no'),
(1395,'_transient_timeout_wc_outofstock_count','1555599230','no'),
(1396,'_transient_wc_outofstock_count','0','no'),
(1398,'_transient_external_ip_address_172.18.0.3','81.0.198.186','no');

DROP TABLE IF EXISTS `mp_postmeta`;
CREATE TABLE `mp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(1,	2,	'_wp_page_template',	'default'),
(342,	345,	'_edit_lock',	'1481785577:1'),
(343,	345,	'_edit_last',	'1'),
(345,	2,	'_edit_lock',	'1482168078:1'),
(346,	2,	'_edit_last',	'1'),
(347,	351,	'_edit_lock',	'1482170595:1'),
(348,	351,	'_edit_last',	'1'),
(350,	353,	'_edit_lock',	'1482170710:1'),
(351,	353,	'_edit_last',	'1'),
(353,	355,	'_edit_lock',	'1482170910:1'),
(354,	355,	'_edit_last',	'1'),
(356,	357,	'_edit_lock',	'1482171062:1'),
(357,	357,	'_edit_last',	'1'),
(359,	359,	'_edit_lock',	'1482171789:1'),
(360,	359,	'_edit_last',	'1'),
(362,	362,	'_edit_lock',	'1482227801:1'),
(363,	362,	'_edit_last',	'1'),
(365,	364,	'_edit_lock',	'1482228535:1'),
(366,	364,	'_edit_last',	'1'),
(368,	366,	'_edit_lock',	'1482229539:1'),
(369,	366,	'_edit_last',	'1'),
(371,	370,	'_edit_lock',	'1482229606:1'),
(372,	370,	'_edit_last',	'1'),
(387,	385,	'_edit_lock',	'1482238911:1'),
(388,	385,	'_edit_last',	'1'),
(390,	387,	'_edit_lock',	'1482239097:1'),
(391,	387,	'_edit_last',	'1'),
(393,	389,	'_edit_lock',	'1482239346:1'),
(394,	389,	'_edit_last',	'1'),
(396,	391,	'_edit_lock',	'1482239590:1'),
(397,	391,	'_edit_last',	'1'),
(399,	393,	'_edit_lock',	'1482239754:1'),
(400,	393,	'_edit_last',	'1'),
(402,	395,	'_edit_lock',	'1482240836:1'),
(403,	395,	'_edit_last',	'1'),
(405,	397,	'_edit_lock',	'1482240908:1'),
(406,	397,	'_edit_last',	'1'),
(408,	399,	'_edit_lock',	'1482250387:1'),
(409,	399,	'_edit_last',	'1'),
(423,	413,	'_edit_last',	'1'),
(452,	442,	'_edit_lock',	'1482334045:1'),
(425,	413,	'_edit_lock',	'1482250582:1'),
(453,	442,	'_edit_last',	'1'),
(455,	444,	'_edit_lock',	'1482334038:1'),
(456,	444,	'_edit_last',	'1'),
(458,	447,	'_edit_lock',	'1482998363:1'),
(459,	447,	'_edit_last',	'1'),
(461,	449,	'_edit_lock',	'1483106200:1'),
(462,	449,	'_edit_last',	'1'),
(468,	465,	'_edit_lock',	'1503499779:1'),
(469,	465,	'_edit_last',	'1'),
(471,	467,	'_wp_attached_file',	'2017/10/Capture001-1.png'),
(472,	467,	'_wp_attachment_metadata',	'a:5:{s:5:\"width\";i:1920;s:6:\"height\";i:1080;s:4:\"file\";s:24:\"2017/10/Capture001-1.png\";s:5:\"sizes\";a:6:{s:9:\"thumbnail\";a:4:{s:4:\"file\";s:24:\"Capture001-1-150x150.png\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:9:\"image/png\";}s:6:\"medium\";a:4:{s:4:\"file\";s:24:\"Capture001-1-300x169.png\";s:5:\"width\";i:300;s:6:\"height\";i:169;s:9:\"mime-type\";s:9:\"image/png\";}s:12:\"medium_large\";a:4:{s:4:\"file\";s:24:\"Capture001-1-768x432.png\";s:5:\"width\";i:768;s:6:\"height\";i:432;s:9:\"mime-type\";s:9:\"image/png\";}s:5:\"large\";a:4:{s:4:\"file\";s:25:\"Capture001-1-1024x576.png\";s:5:\"width\";i:1024;s:6:\"height\";i:576;s:9:\"mime-type\";s:9:\"image/png\";}s:14:\"post-thumbnail\";a:4:{s:4:\"file\";s:25:\"Capture001-1-1200x675.png\";s:5:\"width\";i:1200;s:6:\"height\";i:675;s:9:\"mime-type\";s:9:\"image/png\";}s:23:\"mailpoet_newsletter_max\";a:4:{s:4:\"file\";s:25:\"Capture001-1-1320x743.png\";s:5:\"width\";i:1320;s:6:\"height\";i:743;s:9:\"mime-type\";s:9:\"image/png\";}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(473,	470,	'_edit_last',	'1'),
(474,	470,	'_edit_lock',	'1509925726:1');

DROP TABLE IF EXISTS `mp_posts`;
CREATE TABLE `mp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(1,	1,	'2016-11-23 14:16:53',	'2016-11-23 14:16:53',	'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!',	'Hello world!',	'',	'publish',	'open',	'open',	'',	'hello-world',	'',	'',	'2016-11-23 14:16:53',	'2016-11-23 14:16:53',	'',	0,	'http://mailpoet/?p=1',	0,	'post',	'',	1),
(4,	1,	'2016-11-23 18:32:23',	'2016-11-23 18:32:23',	'[mailpoet_page]',	'MailPoet Page',	'',	'publish',	'closed',	'closed',	'',	'subscriptions',	'',	'',	'2016-11-23 18:32:23',	'2016-11-23 18:32:23',	'',	0,	'http://mailpoet/2016/11/23/subscriptions/',	0,	'mailpoet_page',	'',	0),
(470,	1,	'2017-11-05 23:48:01',	'2017-11-05 23:48:01',	'Regular form:\r\n\r\n[mailpoet_form id=\"1\"]\r\n\r\nIframe form:\r\n\r\n<iframe class=\"mailpoet_form_iframe\" id=\"mailpoet_form_iframe\" tabindex=\"0\" src=\"http://test.local?mailpoet_form_iframe=1\" width=\"100%\" height=\"100%\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\"></iframe>',	'Form Test',	'',	'publish',	'closed',	'closed',	'',	'form-test',	'',	'',	'2017-11-05 23:58:38',	'2017-11-05 23:58:38',	'',	0,	'http://wordpress/?page_id=470',	0,	'page',	'',	0),
(471,	1,	'2019-03-19 14:52:09',	'0000-00-00 00:00:00',	'',	'Auto Draft',	'',	'auto-draft',	'open',	'open',	'',	'',	'',	'',	'2019-03-19 14:52:09',	'0000-00-00 00:00:00',	'',	0,	'http://test.local/?p=471',	0,	'post',	'',	0),
(472,	1,	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'',	'Shop',	'',	'publish',	'closed',	'closed',	'',	'shop',	'',	'',	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'',	0,	'http://test.local/shop/',	0,	'page',	'',	0),
(473,	1,	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'[woocommerce_cart]',	'Cart',	'',	'publish',	'closed',	'closed',	'',	'cart',	'',	'',	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'',	0,	'http://test.local/cart/',	0,	'page',	'',	0),
(474,	1,	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'[woocommerce_checkout]',	'Checkout',	'',	'publish',	'closed',	'closed',	'',	'checkout',	'',	'',	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'',	0,	'http://test.local/checkout/',	0,	'page',	'',	0),
(475,	1,	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'[woocommerce_my_account]',	'My account',	'',	'publish',	'closed',	'closed',	'',	'my-account',	'',	'',	'2019-03-19 14:53:02',	'2019-03-19 14:53:02',	'',	0,	'http://test.local/my-account/',	0,	'page',	'',	0);

DROP TABLE IF EXISTS `mp_signups`;
CREATE TABLE `mp_signups` (
  `signup_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `domain` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `path` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `title` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_login` varchar(60) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `activation_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `meta` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`signup_id`),
  KEY `activation_key` (`activation_key`),
  KEY `user_email` (`user_email`),
  KEY `user_login_email` (`user_login`,`user_email`),
  KEY `domain_path` (`domain`(140),`path`(51))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_social_users`;
CREATE TABLE `mp_social_users` (
  `ID` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `identifier` varchar(100) NOT NULL,
  KEY `ID` (`ID`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `mp_termmeta`;
CREATE TABLE `mp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `mp_terms`;
CREATE TABLE `mp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_terms` (`term_id`, `name`, `slug`, `term_group`) VALUES
(1,	'Uncategorized',	'uncategorized',	0),
(2,	'simple',	'simple',	0),
(3,	'grouped',	'grouped',	0),
(4,	'variable',	'variable',	0),
(5,	'external',	'external',	0),
(6,	'exclude-from-search',	'exclude-from-search',	0),
(7,	'exclude-from-catalog',	'exclude-from-catalog',	0),
(8,	'featured',	'featured',	0),
(9,	'outofstock',	'outofstock',	0),
(10,	'rated-1',	'rated-1',	0),
(11,	'rated-2',	'rated-2',	0),
(12,	'rated-3',	'rated-3',	0),
(13,	'rated-4',	'rated-4',	0),
(14,	'rated-5',	'rated-5',	0),
(15,	'Uncategorized',	'uncategorized',	0);

DROP TABLE IF EXISTS `mp_term_relationships`;
CREATE TABLE `mp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES
(1,	1,	0),
(345,	1,	0),
(351,	1,	0),
(353,	1,	0),
(355,	1,	0),
(357,	1,	0),
(359,	1,	0),
(362,	1,	0),
(364,	1,	0),
(366,	1,	0),
(370,	1,	0),
(385,	1,	0),
(387,	1,	0),
(389,	1,	0),
(391,	1,	0),
(393,	1,	0),
(395,	1,	0),
(397,	1,	0),
(399,	1,	0),
(413,	1,	0),
(442,	1,	0),
(444,	1,	0),
(447,	1,	0),
(449,	1,	0),
(465,	1,	0);

DROP TABLE IF EXISTS `mp_term_taxonomy`;
CREATE TABLE `mp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES
(1,	1,	'category',	'',	0,	25),
(2,	2,	'product_type',	'',	0,	0),
(3,	3,	'product_type',	'',	0,	0),
(4,	4,	'product_type',	'',	0,	0),
(5,	5,	'product_type',	'',	0,	0),
(6,	6,	'product_visibility',	'',	0,	0),
(7,	7,	'product_visibility',	'',	0,	0),
(8,	8,	'product_visibility',	'',	0,	0),
(9,	9,	'product_visibility',	'',	0,	0),
(10,	10,	'product_visibility',	'',	0,	0),
(11,	11,	'product_visibility',	'',	0,	0),
(12,	12,	'product_visibility',	'',	0,	0),
(13,	13,	'product_visibility',	'',	0,	0),
(14,	14,	'product_visibility',	'',	0,	0),
(15,	15,	'product_cat',	'',	0,	0);

DROP TABLE IF EXISTS `mp_usermeta`;
CREATE TABLE `mp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(1,	1,	'nickname',	'admin'),
(2,	1,	'first_name',	''),
(3,	1,	'last_name',	''),
(4,	1,	'description',	''),
(5,	1,	'rich_editing',	'true'),
(6,	1,	'comment_shortcuts',	'false'),
(7,	1,	'admin_color',	'fresh'),
(8,	1,	'use_ssl',	'0'),
(9,	1,	'show_admin_bar_front',	'true'),
(10,	1,	'mp_capabilities',	'a:1:{s:13:\"administrator\";b:1;}'),
(11,	1,	'mp_user_level',	'10'),
(12,	1,	'dismissed_wp_pointers',	''),
(13,	1,	'show_welcome_panel',	'1'),
(4843,	1,	'session_tokens',	'a:1:{s:64:\"b03a2c35276aedd79dc3bdbce369fb630800df7b6d5523b33d9f7ec5d0e888ac\";a:4:{s:10:\"expiration\";i:1510098379;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:115:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36\";s:5:\"login\";i:1509925579;}}'),
(4844,	1,	'mp_user-settings',	'editor=tinymce&editor_expand=on'),
(4845,	1,	'mp_user-settings-time',	'1509925576'),
(4846,	1,	'mp_dashboard_quick_press_last_post_id',	'469'),
(4847,	1,	'community-events-location',	'a:1:{s:2:\"ip\";s:10:\"172.18.0.0\";}'),
(4848,	1,	'_woocommerce_persistent_cart_1',	'a:1:{s:4:\"cart\";a:0:{}}');

DROP TABLE IF EXISTS `mp_users`;
CREATE TABLE `mp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `mp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_activation_key`, `user_status`, `display_name`) VALUES
(1,	'admin',	'$P$B4Y5RtifyzLVhJoU2vk82fIHsp53tL1',	'admin',	'wp@example.com',	'',	'2016-11-23 14:16:52',	'',	0,	'admin');

DROP TABLE IF EXISTS `mp_wc_download_log`;
CREATE TABLE `mp_wc_download_log` (
  `download_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_ip_address` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  PRIMARY KEY (`download_log_id`),
  KEY `permission_id` (`permission_id`),
  KEY `timestamp` (`timestamp`),
  CONSTRAINT `fk_mp_wc_download_log_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `mp_woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_wc_webhooks`;
CREATE TABLE `mp_wc_webhooks` (
  `webhook_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `delivery_url` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `secret` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `topic` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `api_version` smallint(4) NOT NULL,
  `failure_count` smallint(10) NOT NULL DEFAULT '0',
  `pending_delivery` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`webhook_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_api_keys`;
CREATE TABLE `mp_woocommerce_api_keys` (
  `key_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `permissions` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `consumer_key` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `consumer_secret` char(43) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nonces` longtext COLLATE utf8mb4_unicode_520_ci,
  `truncated_key` char(7) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`key_id`),
  KEY `consumer_key` (`consumer_key`),
  KEY `consumer_secret` (`consumer_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_attribute_taxonomies`;
CREATE TABLE `mp_woocommerce_attribute_taxonomies` (
  `attribute_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `attribute_label` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `attribute_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `attribute_orderby` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `attribute_public` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`attribute_id`),
  KEY `attribute_name` (`attribute_name`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_downloadable_product_permissions`;
CREATE TABLE `mp_woocommerce_downloadable_product_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `download_id` varchar(36) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `order_key` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_email` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `downloads_remaining` varchar(9) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `access_granted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access_expires` datetime DEFAULT NULL,
  `download_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`permission_id`),
  KEY `download_order_key_product` (`product_id`,`order_id`,`order_key`(16),`download_id`),
  KEY `download_order_product` (`download_id`,`order_id`,`product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_log`;
CREATE TABLE `mp_woocommerce_log` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `level` smallint(4) NOT NULL,
  `source` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `context` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_order_itemmeta`;
CREATE TABLE `mp_woocommerce_order_itemmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_order_items`;
CREATE TABLE `mp_woocommerce_order_items` (
  `order_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_name` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `order_item_type` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `order_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_payment_tokenmeta`;
CREATE TABLE `mp_woocommerce_payment_tokenmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_token_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `payment_token_id` (`payment_token_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_payment_tokens`;
CREATE TABLE `mp_woocommerce_payment_tokens` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gateway_id` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `token` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `type` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_sessions`;
CREATE TABLE `mp_woocommerce_sessions` (
  `session_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `session_value` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `session_expiry` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `mp_woocommerce_shipping_zones`;
CREATE TABLE `mp_woocommerce_shipping_zones` (
  `zone_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `zone_order` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_woocommerce_shipping_zones` (`zone_id`, `zone_name`, `zone_order`) VALUES
(1,	'France',	0);

DROP TABLE IF EXISTS `mp_woocommerce_shipping_zone_locations`;
CREATE TABLE `mp_woocommerce_shipping_zone_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint(20) unsigned NOT NULL,
  `location_code` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `location_type` varchar(40) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `location_id` (`location_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_woocommerce_shipping_zone_locations` (`location_id`, `zone_id`, `location_code`, `location_type`) VALUES
(1,	1,	'FR',	'country');

DROP TABLE IF EXISTS `mp_woocommerce_shipping_zone_methods`;
CREATE TABLE `mp_woocommerce_shipping_zone_methods` (
  `zone_id` bigint(20) unsigned NOT NULL,
  `instance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `method_id` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `method_order` bigint(20) unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `mp_woocommerce_shipping_zone_methods` (`zone_id`, `instance_id`, `method_id`, `method_order`, `is_enabled`) VALUES
(1,	1,	'flat_rate',	1,	1),
(0,	2,	'flat_rate',	1,	1);

DROP TABLE IF EXISTS `mp_woocommerce_tax_rates`;
CREATE TABLE `mp_woocommerce_tax_rates` (
  `tax_rate_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_rate_country` varchar(2) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `tax_rate_state` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `tax_rate` varchar(8) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `tax_rate_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `tax_rate_priority` bigint(20) unsigned NOT NULL,
  `tax_rate_compound` int(1) NOT NULL DEFAULT '0',
  `tax_rate_shipping` int(1) NOT NULL DEFAULT '1',
  `tax_rate_order` bigint(20) unsigned NOT NULL,
  `tax_rate_class` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`tax_rate_id`),
  KEY `tax_rate_country` (`tax_rate_country`),
  KEY `tax_rate_state` (`tax_rate_state`(2)),
  KEY `tax_rate_class` (`tax_rate_class`(10)),
  KEY `tax_rate_priority` (`tax_rate_priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `mp_woocommerce_tax_rate_locations`;
CREATE TABLE `mp_woocommerce_tax_rate_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_code` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `tax_rate_id` bigint(20) unsigned NOT NULL,
  `location_type` varchar(40) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `tax_rate_id` (`tax_rate_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- 2017-11-05 23:53:17

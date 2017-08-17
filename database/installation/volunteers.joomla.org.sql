# Dump of table vol_assets
# ------------------------------------------------------------

CREATE TABLE `vol_assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set parent.',
  `lft` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set lft.',
  `rgt` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set rgt.',
  `level` int(10) unsigned NOT NULL COMMENT 'The cached level in the nested tree.',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.\n',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The descriptive title for the asset.',
  `rules` varchar(5120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded access control.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_asset_name` (`name`),
  KEY `idx_lft_rgt` (`lft`,`rgt`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_assets` WRITE;
/*!40000 ALTER TABLE `vol_assets` DISABLE KEYS */;

INSERT INTO `vol_assets` (`id`, `parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`)
VALUES
	(1,0,0,103,0,'root.1','Root Asset','{\"core.login.site\":{\"2\":1},\"core.login.admin\":[],\"core.login.offline\":[],\"core.admin\":{\"8\":1},\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"core.edit.own\":[]}'),
	(2,1,1,2,1,'com_admin','com_admin','{}'),
	(3,1,3,4,1,'com_banners','com_banners','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1},\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(4,1,5,6,1,'com_cache','com_cache','{\"core.admin\":{\"7\":1},\"core.manage\":{\"7\":1}}'),
	(5,1,7,8,1,'com_checkin','com_checkin','{\"core.admin\":{\"7\":1},\"core.manage\":{\"7\":1}}'),
	(6,1,9,10,1,'com_config','com_config','{}'),
	(7,1,11,12,1,'com_contact','com_contact','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1},\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"core.edit.own\":[]}'),
	(8,1,13,20,1,'com_content','com_content','{\"core.admin\":[],\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"core.edit.own\":[]}'),
	(9,1,21,22,1,'com_cpanel','com_cpanel','{}'),
	(10,1,23,24,1,'com_installer','com_installer','{\"core.admin\":[],\"core.manage\":{\"7\":0},\"core.delete\":{\"7\":0},\"core.edit.state\":{\"7\":0}}'),
	(11,1,25,26,1,'com_languages','com_languages','{\"core.admin\":{\"7\":1},\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(12,1,27,28,1,'com_login','com_login','{}'),
	(13,1,29,30,1,'com_mailto','com_mailto','{}'),
	(14,1,31,32,1,'com_massmail','com_massmail','{}'),
	(15,1,33,34,1,'com_media','com_media','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1},\"core.create\":{\"3\":1},\"core.delete\":{\"5\":1}}'),
	(16,1,35,36,1,'com_menus','com_menus','{\"core.admin\":{\"7\":1},\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(17,1,37,38,1,'com_messages','com_messages','{\"core.admin\":{\"7\":1},\"core.manage\":{\"7\":1}}'),
	(18,1,39,72,1,'com_modules','com_modules','{\"core.admin\":{\"7\":1},\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(19,1,73,74,1,'com_newsfeeds','com_newsfeeds','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1},\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"core.edit.own\":[]}'),
	(20,1,75,76,1,'com_plugins','com_plugins','{\"core.admin\":{\"7\":1},\"core.manage\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(21,1,77,78,1,'com_redirect','com_redirect','{\"core.admin\":{\"7\":1},\"core.manage\":[]}'),
	(22,1,79,80,1,'com_search','com_search','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1}}'),
	(23,1,81,82,1,'com_templates','com_templates','{\"core.admin\":{\"7\":1},\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(24,1,83,84,1,'com_users','com_users','{\"core.admin\":[],\"core.manage\":[],\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(26,1,85,86,1,'com_wrapper','com_wrapper','{}'),
	(27,8,14,19,2,'com_content.category.2','General','{\"core.create\":[],\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"core.edit.own\":[]}'),
	(33,1,87,88,1,'com_finder','com_finder','{\"core.admin\":{\"7\":1},\"core.manage\":{\"6\":1}}'),
	(34,1,89,90,1,'com_joomlaupdate','com_joomlaupdate','{\"core.admin\":[],\"core.manage\":[],\"core.delete\":[],\"core.edit.state\":[]}'),
	(35,1,91,92,1,'com_tags','com_tags','{\"core.admin\":[],\"core.manage\":[],\"core.manage\":[],\"core.delete\":[],\"core.edit.state\":[]}'),
	(36,1,93,94,1,'com_contenthistory','com_contenthistory','{}'),
	(37,1,95,96,1,'com_ajax','com_ajax','{}'),
	(38,1,97,98,1,'com_postinstall','com_postinstall','{\"core.manage\":[],\"core.edit.state\":[]}'),
	(39,18,40,41,2,'com_modules.module.1','Main Menu','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(40,18,42,43,2,'com_modules.module.2','Login','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(41,18,44,45,2,'com_modules.module.3','Popular Articles','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(42,18,46,47,2,'com_modules.module.4','Recently Added Articles','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(43,18,48,49,2,'com_modules.module.8','Toolbar','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(44,18,50,51,2,'com_modules.module.9','Quick Icons','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(45,18,52,53,2,'com_modules.module.10','Logged-in Users','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(46,18,54,55,2,'com_modules.module.12','Admin Menu','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(47,18,56,57,2,'com_modules.module.13','Admin Submenu','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(48,18,58,59,2,'com_modules.module.14','User Status','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(49,18,60,61,2,'com_modules.module.15','Title','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(50,18,62,63,2,'com_modules.module.16','Login Form','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(51,18,64,65,2,'com_modules.module.17','Breadcrumbs','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(52,18,66,67,2,'com_modules.module.79','Multilanguage status','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(59,27,15,16,3,'com_content.article.2','Frequently Asked Questions and Answers','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(60,1,99,100,1,'com_jce','jce','{\"core.admin\":[],\"core.manage\":[],\"jce.config\":[],\"jce.profiles\":[],\"jce.preferences\":[],\"jce.installer\":[],\"jce.browser\":[],\"jce.mediabox\":[]}'),
	(61,1,101,102,1,'com_volunteers','com_volunteers','{\"core.admin\":{\"8\":1},\"core.manage\":[],\"core.create\":{\"2\":1},\"core.delete\":[],\"core.edit\":{\"2\":1},\"core.edit.state\":{\"2\":1},\"core.edit.own\":{\"2\":1}}'),
	(62,27,17,18,3,'com_content.article.3','Spread the Joomla! love','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(65,18,68,69,2,'com_modules.module.89','Joomla Version','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[]}'),
	(66,18,70,71,2,'com_modules.module.90','Search','{\"core.delete\":[],\"core.edit\":[],\"core.edit.state\":[],\"module.edit.frontend\":[]}');

/*!40000 ALTER TABLE `vol_assets` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_associations
# ------------------------------------------------------------

CREATE TABLE `vol_associations` (
  `id` int(11) NOT NULL COMMENT 'A reference to the associated item.',
  `context` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The context of the associated item.',
  `key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The key for the association computed from an md5 on associated ids.',
  PRIMARY KEY (`context`,`id`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_banner_clients
# ------------------------------------------------------------

CREATE TABLE `vol_banner_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extrainfo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `own_prefix` tinyint(4) NOT NULL DEFAULT '0',
  `metakey_prefix` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_type` tinyint(4) NOT NULL DEFAULT '-1',
  `track_clicks` tinyint(4) NOT NULL DEFAULT '-1',
  `track_impressions` tinyint(4) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `idx_own_prefix` (`own_prefix`),
  KEY `idx_metakey_prefix` (`metakey_prefix`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_banner_tracks
# ------------------------------------------------------------

CREATE TABLE `vol_banner_tracks` (
  `track_date` datetime NOT NULL,
  `track_type` int(10) unsigned NOT NULL,
  `banner_id` int(10) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`track_date`,`track_type`,`banner_id`),
  KEY `idx_track_date` (`track_date`),
  KEY `idx_track_type` (`track_type`),
  KEY `idx_banner_id` (`banner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_banners
# ------------------------------------------------------------

CREATE TABLE `vol_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `imptotal` int(11) NOT NULL DEFAULT '0',
  `impmade` int(11) NOT NULL DEFAULT '0',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `clickurl` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `custombannercode` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `own_prefix` tinyint(1) NOT NULL DEFAULT '0',
  `metakey_prefix` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purchase_type` tinyint(4) NOT NULL DEFAULT '-1',
  `track_clicks` tinyint(4) NOT NULL DEFAULT '-1',
  `track_impressions` tinyint(4) NOT NULL DEFAULT '-1',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reset` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_state` (`state`),
  KEY `idx_own_prefix` (`own_prefix`),
  KEY `idx_banner_catid` (`catid`),
  KEY `idx_language` (`language`),
  KEY `idx_metakey_prefix` (`metakey_prefix`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_categories
# ------------------------------------------------------------

CREATE TABLE `vol_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL DEFAULT '0',
  `rgt` int(11) NOT NULL DEFAULT '0',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `path` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extension` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The meta keywords for the page.',
  `metadata` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded metadata properties.',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cat_idx` (`extension`,`published`,`access`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_language` (`language`),
  KEY `idx_path` (`path`(100)),
  KEY `idx_alias` (`alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_categories` WRITE;
/*!40000 ALTER TABLE `vol_categories` DISABLE KEYS */;

INSERT INTO `vol_categories` (`id`, `asset_id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `extension`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`, `modified_user_id`, `modified_time`, `hits`, `language`, `version`)
VALUES
	(1,0,0,0,5,0,'','system','ROOT',X'726F6F74','','',1,0,'0000-00-00 00:00:00',1,'{}','','','{}',42,'2011-01-01 00:00:01',0,'0000-00-00 00:00:00',0,'*',1),
	(2,27,1,1,2,1,'general','com_content','General',X'67656E6572616C','','',1,0,'0000-00-00 00:00:00',1,'{\"category_layout\":\"\",\"image\":\"\",\"image_alt\":\"\"}','','','{\"author\":\"\",\"robots\":\"\"}',1,'2011-01-01 00:00:01',1,'2016-08-23 18:56:05',0,'*',1);

/*!40000 ALTER TABLE `vol_categories` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_contact_details
# ------------------------------------------------------------

CREATE TABLE `vol_contact_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `con_position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` mediumtext COLLATE utf8mb4_unicode_ci,
  `suburb` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `misc` longtext COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_con` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `catid` int(11) NOT NULL DEFAULT '0',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `webpage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortname1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortname2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sortname3` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `featured` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Set if article is featured.',
  `xreference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A reference to enable linkages to external data sets.',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`published`),
  KEY `idx_catid` (`catid`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`,`catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_content
# ------------------------------------------------------------

CREATE TABLE `vol_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `introtext` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `fulltext` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `images` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `urls` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribs` varchar(5120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `featured` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Set if article is featured.',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The language code for the article.',
  `xreference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A reference to enable linkages to external data sets.',
  PRIMARY KEY (`id`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_catid` (`catid`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`,`catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_content` WRITE;
/*!40000 ALTER TABLE `vol_content` DISABLE KEYS */;

INSERT INTO `vol_content` (`id`, `asset_id`, `title`, `alias`, `introtext`, `fulltext`, `state`, `catid`, `created`, `created_by`, `created_by_alias`, `modified`, `modified_by`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `images`, `urls`, `attribs`, `version`, `ordering`, `metakey`, `metadesc`, `access`, `hits`, `metadata`, `featured`, `language`, `xreference`)
VALUES
	(2,59,'Frequently Asked Questions and Answers',X'6672657175656E746C792D61736B65642D7175657374696F6E732D616E642D616E7377657273','<h3>Joomlers/Accounts</h3>\r\n<p><strong>Q: What is a \"Joomler\"?</strong></p>\r\n<p>A: Joomlers are volunteers that contribute to the Joomla project.</p>\r\n<p><strong>Q: I do see my Joomler profile, but I don\'t have an account?</strong></p>\r\n<p>A: As soon as you register on this website any existing Joomler profile will be matched. After registration you can edit your own Joomler profile.&nbsp;</p>\r\n<p><strong>Q: How can I edit my Joomler profile?</strong></p>\r\n<p>A: Make sure you have&nbsp;<a href=\"register\">registered</a>&nbsp;on the website, then log-in and go to your own Joomler profile. Click on the \"<em>Edit Profile</em>\" button at the right top of your Joomler profile to edit.</p>\r\n<p><strong>Q: Is the list of all Joomlers complete?</strong></p>\r\n<p>A: Not yet. The volunteer portal launched on November 10. More people are creating Joomler profilies daily, but the overview is not complete yet.</p>\r\n<hr />\r\n<h3>Working Groups</h3>\r\n<p><strong>Q: My team/working group is missing. Can it be added?</strong></p>\r\n<p>A: Yes, please contact&nbsp;<a href=\"joomlers/sander-potjer\">Sander Potjer</a>. Thank you for reporting a missing group in advance!</p>\r\n<p><strong>Q: How can we edit the working group information?</strong></p>\r\n<p>A: Team Leaders and Team Liaisons can edit the group information by clicking on the \"<em>Edit Group</em>\" button at the right top of the working group page.</p>\r\n<hr />\r\n<h3>Working Group Members</h3>\r\n<p><strong>Q: I am a group member of a working group, how can I be listed as group member?</strong></p>\r\n<p>A: Make sure you are <a href=\"register\">registered</a>&nbsp;on this website as a Joomler. Then ask your team leader or leadership liaison to add you to the group member page.</p>\r\n<p><strong>Q: Who can manage the group members?</strong></p>\r\n<p>A: Team leaders and leadership liaisons can manage the members of a working group.&nbsp;</p>\r\n<p><strong>Q: I want to add a group member, but he/she is not in the list of Joomlers</strong></p>\r\n<p>A: In that case the Joomler hasn\'t&nbsp;registered on this website yet. Ask them to register, after that you can add them to the group.</p>\r\n<p><strong>Q: A team member leaves the team, how can I remove the group member from the member list?</strong></p>\r\n<p>You can edit the group membership of the Joomler. If you set the \"<em>Date Ended</em>\" date the Joomler will be placed on the \"<em>Honor Roll</em>\" tab instead of the active group member list.</p>\r\n<p><strong>Q: Are all working group member overviews complete?</strong></p>\r\n<p>A: Not yet. The volunteer portal launched on November 10. Working groups are currently making the overviews complete.</p>\r\n<hr />\r\n<h3>General</h3>\r\n<p><strong>Q: Is the volunteer portal completed?</strong></p>\r\n<p>A: No. The first version of the volunteer portal is now released. On a very regular basis updates will be rolled out which will introduce new features that will support better collaboration of the working groups, group members and Joomlers and will provide more insights in our community. The exact roadmap, that is open for feedback &amp; suggestions, will be published soon.</p>\r\n<p><strong>Q: Where can I report bugs &amp; feature request?</strong></p>\r\n<p>A: The volunteer portal code will be published on Github soon, open for community contributions.&nbsp;<span style=\"line-height: 15.8079996109009px;\">Please contact <a href=\"joomlers/sander-potjer\">Sander Potjer</a>&nbsp;for bugs &amp; feedback at this moment or join the public <strong>volunteer-portal</strong> channel on <a href=\"http://glip.com\">http://glip.com</a></span></p>\r\n<p><strong>Q: I want to help to improve the volunteer portal, how can I join?</strong></p>\r\n<p>A: We\'re currently looking for team members to join the <a href=\"working-groups/volunteers-portal-team\">Volunteer Portal Team</a>. Contact <a href=\"joomlers/sander-potjer\">Sander Potjer</a>&nbsp;if you are interested. Thanks!</p>','',1,2,'2016-08-08 12:00:00',1,'','0000-00-00 00:00:00',1,0,'0000-00-00 00:00:00','2016-08-08 12:00:00','0000-00-00 00:00:00','{\"image_intro\":\"\",\"float_intro\":\"\",\"image_intro_alt\":\"\",\"image_intro_caption\":\"\",\"image_fulltext\":\"\",\"float_fulltext\":\"\",\"image_fulltext_alt\":\"\",\"image_fulltext_caption\":\"\"}','{\"urla\":false,\"urlatext\":\"\",\"targeta\":\"\",\"urlb\":false,\"urlbtext\":\"\",\"targetb\":\"\",\"urlc\":false,\"urlctext\":\"\",\"targetc\":\"\"}','{\"show_title\":\"\",\"link_titles\":\"\",\"show_tags\":\"\",\"show_intro\":\"\",\"info_block_position\":\"\",\"show_category\":\"\",\"link_category\":\"\",\"show_parent_category\":\"\",\"link_parent_category\":\"\",\"show_author\":\"\",\"link_author\":\"\",\"show_create_date\":\"\",\"show_modify_date\":\"\",\"show_publish_date\":\"\",\"show_item_navigation\":\"\",\"show_icons\":\"\",\"show_print_icon\":\"\",\"show_email_icon\":\"\",\"show_vote\":\"\",\"show_hits\":\"\",\"show_noauth\":\"\",\"urls_position\":\"\",\"alternative_readmore\":\"\",\"article_layout\":\"\",\"show_publishing_options\":\"\",\"show_article_options\":\"\",\"show_urls_images_backend\":\"\",\"show_urls_images_frontend\":\"\"}',1,1,'','',1,0,'{\"robots\":\"\",\"author\":\"\",\"rights\":\"\",\"xreference\":\"\"}',0,'*',''),
	(3,62,'Spread the Joomla! love',X'7370726561642D7468652D6A6F6F6D6C612D6C6F7665','<p>Spread your love for Joomla! Feel free to use the banners below on your own website to show that you are a proud Joomler and supporting the Joomla-project.</p>\r\n<h3>Banner 468x60:</h3>\r\n<p><a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"><img src=\"http://cdn.joomla.org/volunteers/joomla-heart-regular.gif\" alt=\"Joomla! Volunteers Portal\" width=\"468\" height=\"60\" border=\"0\" /></a></p>\r\n<p>Banner code:</p>\r\n<pre>&lt;a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"&gt;&lt;img src=\"http://cdn.joomla.org/volunteers/joomla-heart-regular.gif\" alt=\"Joomla! Volunteers Portal\" width=\"468\" height=\"60\" border=\"0\"&gt;&lt;/a&gt;</pre>\r\n<h3>Banner 160x600:</h3>\r\n<p><a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"><img src=\"http://cdn.joomla.org/volunteers/joomla-heart-tall.gif\" alt=\"Joomla! Volunteers Portal\" width=\"160\" height=\"600\" border=\"0\" /></a></p>\r\n<p>Banner code:</p>\r\n<pre>&lt;a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"&gt;&lt;img src=\"http://cdn.joomla.org/volunteers/joomla-heart-tall.gif\" alt=\"Joomla! Volunteers Portal\" width=\"160\" height=\"600\" border=\"0\"&gt;&lt;/a&gt;</pre>\r\n<h3>Banner 300x250:</h3>\r\n<p><a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"><img src=\"http://cdn.joomla.org/volunteers/joomla-heart-square.gif\" alt=\"Joomla! Volunteers Portal\" width=\"300\" height=\"250\" border=\"0\" /></a></p>\r\n<p>Banner code:</p>\r\n<pre>&lt;a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"&gt;&lt;img src=\"http://cdn.joomla.org/volunteers/joomla-heart-square.gif\" alt=\"Joomla! Volunteers Portal\" width=\"300\" height=\"250\" border=\"0\"&gt;&lt;/a&gt;</pre>\r\n<h3>Banner 728x90:</h3>\r\n<p><a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"><img src=\"http://cdn.joomla.org/volunteers/joomla-heart-wide.gif\" alt=\"Joomla! Volunteers Portal\" width=\"728\" height=\"90\" border=\"0\" /></a></p>\r\n<p>Banner code:</p>\r\n<pre>&lt;a href=\"http://volunteers.joomla.org/\" target=\"_blank\" title=\"Joomla! Volunteers Portal\"&gt;&lt;img src=\"http://cdn.joomla.org/volunteers/joomla-heart-wide.gif\" alt=\"Joomla! Volunteers Portal\" width=\"728\" height=\"90\" border=\"0\"&gt;&lt;/a&gt;</pre>','',1,2,'2016-08-08 12:00:00',1,'','0000-00-00 00:00:00',1,0,'0000-00-00 00:00:00','2016-08-08 12:00:00','0000-00-00 00:00:00','{\"image_intro\":\"\",\"float_intro\":\"\",\"image_intro_alt\":\"\",\"image_intro_caption\":\"\",\"image_fulltext\":\"\",\"float_fulltext\":\"\",\"image_fulltext_alt\":\"\",\"image_fulltext_caption\":\"\"}','{\"urla\":false,\"urlatext\":\"\",\"targeta\":\"\",\"urlb\":false,\"urlbtext\":\"\",\"targetb\":\"\",\"urlc\":false,\"urlctext\":\"\",\"targetc\":\"\"}','{\"show_title\":\"\",\"link_titles\":\"\",\"show_tags\":\"\",\"show_intro\":\"\",\"info_block_position\":\"\",\"show_category\":\"\",\"link_category\":\"\",\"show_parent_category\":\"\",\"link_parent_category\":\"\",\"show_author\":\"\",\"link_author\":\"\",\"show_create_date\":\"\",\"show_modify_date\":\"\",\"show_publish_date\":\"\",\"show_item_navigation\":\"\",\"show_icons\":\"\",\"show_print_icon\":\"\",\"show_email_icon\":\"\",\"show_vote\":\"\",\"show_hits\":\"\",\"show_noauth\":\"\",\"urls_position\":\"\",\"alternative_readmore\":\"\",\"article_layout\":\"\",\"show_publishing_options\":\"\",\"show_article_options\":\"\",\"show_urls_images_backend\":\"\",\"show_urls_images_frontend\":\"\"}',1,0,'','',1,4,'{\"robots\":\"\",\"author\":\"\",\"rights\":\"\",\"xreference\":\"\"}',0,'*','');

/*!40000 ALTER TABLE `vol_content` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_content_frontpage
# ------------------------------------------------------------

CREATE TABLE `vol_content_frontpage` (
  `content_id` int(11) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_content_rating
# ------------------------------------------------------------

CREATE TABLE `vol_content_rating` (
  `content_id` int(11) NOT NULL DEFAULT '0',
  `rating_sum` int(10) unsigned NOT NULL DEFAULT '0',
  `rating_count` int(10) unsigned NOT NULL DEFAULT '0',
  `lastip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_content_types
# ------------------------------------------------------------

CREATE TABLE `vol_content_types` (
  `type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type_alias` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `table` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rules` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_mappings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `router` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content_history_options` varchar(5120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON string for com_contenthistory options',
  PRIMARY KEY (`type_id`),
  KEY `idx_alias` (`type_alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_content_types` WRITE;
/*!40000 ALTER TABLE `vol_content_types` DISABLE KEYS */;

INSERT INTO `vol_content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`)
VALUES
	(1,'Article','com_content.article','{\"special\":{\"dbtable\":\"vol_content\",\"key\":\"id\",\"type\":\"Content\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"state\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"introtext\", \"core_hits\":\"hits\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"attribs\", \"core_featured\":\"featured\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"urls\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"xreference\", \"asset_id\":\"asset_id\"}, \"special\": {\"fulltext\":\"fulltext\"}}','ContentHelperRoute::getArticleRoute','{\"formFile\":\"administrator\\/components\\/com_content\\/models\\/forms\\/article.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\"],\"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\"],\"convertToInt\":[\"publish_up\", \"publish_down\", \"featured\", \"ordering\"],\"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"created_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"} ]}'),
	(2,'Weblink','com_weblinks.weblink','{\"special\":{\"dbtable\":\"#__weblinks\",\"key\":\"id\",\"type\":\"Weblink\",\"prefix\":\"WeblinksTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"#__ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"state\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"featured\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"urls\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"xreference\", \"asset_id\":\"null\"}, \"special\":{}}','WeblinksHelperRoute::getWeblinkRoute','{\"formFile\":\"administrator\\/components\\/com_weblinks\\/models\\/forms\\/weblink.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"featured\",\"images\"], \"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\"], \"convertToInt\":[\"publish_up\", \"publish_down\", \"featured\", \"ordering\"], \"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"created_by\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"#__viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"} ]}'),
	(3,'Contact','com_contact.contact','{\"special\":{\"dbtable\":\"vol_contact_details\",\"key\":\"id\",\"type\":\"Contact\",\"prefix\":\"ContactTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"name\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"address\", \"core_hits\":\"hits\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"featured\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"image\", \"core_urls\":\"webpage\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"xreference\", \"asset_id\":\"null\"}, \"special\": {\"con_position\":\"con_position\",\"suburb\":\"suburb\",\"state\":\"state\",\"country\":\"country\",\"postcode\":\"postcode\",\"telephone\":\"telephone\",\"fax\":\"fax\",\"misc\":\"misc\",\"email_to\":\"email_to\",\"default_con\":\"default_con\",\"user_id\":\"user_id\",\"mobile\":\"mobile\",\"sortname1\":\"sortname1\",\"sortname2\":\"sortname2\",\"sortname3\":\"sortname3\"}}','ContactHelperRoute::getContactRoute','{\"formFile\":\"administrator\\/components\\/com_contact\\/models\\/forms\\/contact.xml\",\"hideFields\":[\"default_con\",\"checked_out\",\"checked_out_time\",\"version\",\"xreference\"],\"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\"],\"convertToInt\":[\"publish_up\", \"publish_down\", \"featured\", \"ordering\"], \"displayLookup\":[ {\"sourceColumn\":\"created_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"catid\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"} ] }'),
	(4,'Newsfeed','com_newsfeeds.newsfeed','{\"special\":{\"dbtable\":\"vol_newsfeeds\",\"key\":\"id\",\"type\":\"Newsfeed\",\"prefix\":\"NewsfeedsTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"name\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"featured\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"link\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"xreference\", \"asset_id\":\"null\"}, \"special\": {\"numarticles\":\"numarticles\",\"cache_time\":\"cache_time\",\"rtl\":\"rtl\"}}','NewsfeedsHelperRoute::getNewsfeedRoute','{\"formFile\":\"administrator\\/components\\/com_newsfeeds\\/models\\/forms\\/newsfeed.xml\",\"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\"],\"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\"],\"convertToInt\":[\"publish_up\", \"publish_down\", \"featured\", \"ordering\"],\"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"created_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}]}'),
	(5,'User','com_users.user','{\"special\":{\"dbtable\":\"vol_users\",\"key\":\"id\",\"type\":\"User\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"name\",\"core_state\":\"null\",\"core_alias\":\"username\",\"core_created_time\":\"registerdate\",\"core_modified_time\":\"lastvisitDate\",\"core_body\":\"null\", \"core_hits\":\"null\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"access\":\"null\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"null\", \"core_language\":\"null\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"null\", \"core_ordering\":\"null\", \"core_metakey\":\"null\", \"core_metadesc\":\"null\", \"core_catid\":\"null\", \"core_xreference\":\"null\", \"asset_id\":\"null\"}, \"special\": {}}','UsersHelperRoute::getUserRoute',''),
	(6,'Article Category','com_content.category','{\"special\":{\"dbtable\":\"vol_categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','ContentHelperRoute::getCategoryRoute','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"],\"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(7,'Contact Category','com_contact.category','{\"special\":{\"dbtable\":\"vol_categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','ContactHelperRoute::getCategoryRoute','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"],\"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(8,'Newsfeeds Category','com_newsfeeds.category','{\"special\":{\"dbtable\":\"vol_categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','NewsfeedsHelperRoute::getCategoryRoute','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"],\"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(9,'Weblinks Category','com_weblinks.category','{\"special\":{\"dbtable\":\"#__categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"#__ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\":{\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','WeblinksHelperRoute::getCategoryRoute','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"],\"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"#__viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(10,'Tag','com_tags.tag','{\"special\":{\"dbtable\":\"vol_tags\",\"key\":\"tag_id\",\"type\":\"Tag\",\"prefix\":\"TagsTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"featured\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"urls\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"null\", \"core_xreference\":\"null\", \"asset_id\":\"null\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\"}}','TagsHelperRoute::getTagRoute','{\"formFile\":\"administrator\\/components\\/com_tags\\/models\\/forms\\/tag.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\",\"version\", \"lft\", \"rgt\", \"level\", \"path\", \"urls\", \"publish_up\", \"publish_down\"],\"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"],\"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}, {\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}]}'),
	(11,'Banner','com_banners.banner','{\"special\":{\"dbtable\":\"#__banners\",\"key\":\"id\",\"type\":\"Banner\",\"prefix\":\"BannersTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"#__ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"name\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"description\", \"core_hits\":\"null\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"link\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"null\", \"asset_id\":\"null\"}, \"special\":{\"imptotal\":\"imptotal\", \"impmade\":\"impmade\", \"clicks\":\"clicks\", \"clickurl\":\"clickurl\", \"custombannercode\":\"custombannercode\", \"cid\":\"cid\", \"purchase_type\":\"purchase_type\", \"track_impressions\":\"track_impressions\", \"track_clicks\":\"track_clicks\"}}','','{\"formFile\":\"administrator\\/components\\/com_banners\\/models\\/forms\\/banner.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\",\"version\", \"reset\"],\"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"imptotal\", \"impmade\", \"reset\"], \"convertToInt\":[\"publish_up\", \"publish_down\", \"ordering\"], \"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}, {\"sourceColumn\":\"cid\",\"targetTable\":\"#__banner_clients\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"created_by\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"} ]}'),
	(12,'Banners Category','com_banners.category','{\"special\":{\"dbtable\":\"#__categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"#__ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"], \"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"#__viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(13,'Banner Client','com_banners.client','{\"special\":{\"dbtable\":\"#__banner_clients\",\"key\":\"id\",\"type\":\"Client\",\"prefix\":\"BannersTable\"}}','','','','{\"formFile\":\"administrator\\/components\\/com_banners\\/models\\/forms\\/client.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\"], \"ignoreChanges\":[\"checked_out\", \"checked_out_time\"], \"convertToInt\":[], \"displayLookup\":[]}'),
	(14,'User Notes','com_users.note','{\"special\":{\"dbtable\":\"#__user_notes\",\"key\":\"id\",\"type\":\"Note\",\"prefix\":\"UsersTable\"}}','','','','{\"formFile\":\"administrator\\/components\\/com_users\\/models\\/forms\\/note.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\", \"publish_up\", \"publish_down\"],\"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\"], \"convertToInt\":[\"publish_up\", \"publish_down\"],\"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}, {\"sourceColumn\":\"created_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}]}'),
	(15,'User Notes Category','com_users.category','{\"special\":{\"dbtable\":\"#__categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"#__ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\":{\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"], \"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"access\",\"targetTable\":\"#__viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"#__users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"#__categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(16,'Banner','com_banners.banner','{\"special\":{\"dbtable\":\"vol_banners\",\"key\":\"id\",\"type\":\"Banner\",\"prefix\":\"BannersTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"name\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created\",\"core_modified_time\":\"modified\",\"core_body\":\"description\", \"core_hits\":\"null\",\"core_publish_up\":\"publish_up\",\"core_publish_down\":\"publish_down\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"images\", \"core_urls\":\"link\", \"core_version\":\"version\", \"core_ordering\":\"ordering\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"catid\", \"core_xreference\":\"null\", \"asset_id\":\"null\"}, \"special\":{\"imptotal\":\"imptotal\", \"impmade\":\"impmade\", \"clicks\":\"clicks\", \"clickurl\":\"clickurl\", \"custombannercode\":\"custombannercode\", \"cid\":\"cid\", \"purchase_type\":\"purchase_type\", \"track_impressions\":\"track_impressions\", \"track_clicks\":\"track_clicks\"}}','','{\"formFile\":\"administrator\\/components\\/com_banners\\/models\\/forms\\/banner.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\",\"version\", \"reset\"],\"ignoreChanges\":[\"modified_by\", \"modified\", \"checked_out\", \"checked_out_time\", \"version\", \"imptotal\", \"impmade\", \"reset\"], \"convertToInt\":[\"publish_up\", \"publish_down\", \"ordering\"], \"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}, {\"sourceColumn\":\"cid\",\"targetTable\":\"vol_banner_clients\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"created_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"modified_by\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}]}'),
	(17,'Banners Category','com_banners.category','{\"special\":{\"dbtable\":\"vol_categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\": {\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"asset_id\",\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"], \"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}'),
	(18,'Banner Client','com_banners.client','{\"special\":{\"dbtable\":\"vol_banner_clients\",\"key\":\"id\",\"type\":\"Client\",\"prefix\":\"BannersTable\"}}','','','','{\"formFile\":\"administrator\\/components\\/com_banners\\/models\\/forms\\/client.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\"], \"ignoreChanges\":[\"checked_out\", \"checked_out_time\"], \"convertToInt\":[], \"displayLookup\":[]}'),
	(19,'User Notes','com_users.note','{\"special\":{\"dbtable\":\"vol_user_notes\",\"key\":\"id\",\"type\":\"Note\",\"prefix\":\"UsersTable\"}}','','','','{\"formFile\":\"administrator\\/components\\/com_users\\/models\\/forms\\/note.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\", \"publish_up\", \"publish_down\"],\"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\"], \"convertToInt\":[\"publish_up\", \"publish_down\"],\"displayLookup\":[{\"sourceColumn\":\"catid\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}, {\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}]}'),
	(20,'User Notes Category','com_users.category','{\"special\":{\"dbtable\":\"vol_categories\",\"key\":\"id\",\"type\":\"Category\",\"prefix\":\"JTable\",\"config\":\"array()\"},\"common\":{\"dbtable\":\"vol_ucm_content\",\"key\":\"ucm_id\",\"type\":\"Corecontent\",\"prefix\":\"JTable\",\"config\":\"array()\"}}','','{\"common\":{\"core_content_item_id\":\"id\",\"core_title\":\"title\",\"core_state\":\"published\",\"core_alias\":\"alias\",\"core_created_time\":\"created_time\",\"core_modified_time\":\"modified_time\",\"core_body\":\"description\", \"core_hits\":\"hits\",\"core_publish_up\":\"null\",\"core_publish_down\":\"null\",\"core_access\":\"access\", \"core_params\":\"params\", \"core_featured\":\"null\", \"core_metadata\":\"metadata\", \"core_language\":\"language\", \"core_images\":\"null\", \"core_urls\":\"null\", \"core_version\":\"version\", \"core_ordering\":\"null\", \"core_metakey\":\"metakey\", \"core_metadesc\":\"metadesc\", \"core_catid\":\"parent_id\", \"core_xreference\":\"null\", \"asset_id\":\"asset_id\"}, \"special\":{\"parent_id\":\"parent_id\",\"lft\":\"lft\",\"rgt\":\"rgt\",\"level\":\"level\",\"path\":\"path\",\"extension\":\"extension\",\"note\":\"note\"}}','','{\"formFile\":\"administrator\\/components\\/com_categories\\/models\\/forms\\/category.xml\", \"hideFields\":[\"checked_out\",\"checked_out_time\",\"version\",\"lft\",\"rgt\",\"level\",\"path\",\"extension\"], \"ignoreChanges\":[\"modified_user_id\", \"modified_time\", \"checked_out\", \"checked_out_time\", \"version\", \"hits\", \"path\"], \"convertToInt\":[\"publish_up\", \"publish_down\"], \"displayLookup\":[{\"sourceColumn\":\"created_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"}, {\"sourceColumn\":\"access\",\"targetTable\":\"vol_viewlevels\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"},{\"sourceColumn\":\"modified_user_id\",\"targetTable\":\"vol_users\",\"targetColumn\":\"id\",\"displayColumn\":\"name\"},{\"sourceColumn\":\"parent_id\",\"targetTable\":\"vol_categories\",\"targetColumn\":\"id\",\"displayColumn\":\"title\"}]}');

/*!40000 ALTER TABLE `vol_content_types` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_contentitem_tag_map
# ------------------------------------------------------------

CREATE TABLE `vol_contentitem_tag_map` (
  `type_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `core_content_id` int(10) unsigned NOT NULL COMMENT 'PK from the core content table',
  `content_item_id` int(11) NOT NULL COMMENT 'PK from the content type table',
  `tag_id` int(10) unsigned NOT NULL COMMENT 'PK from the tag table',
  `tag_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date of most recent save for this tag-item',
  `type_id` mediumint(8) NOT NULL COMMENT 'PK from the content_type table',
  UNIQUE KEY `uc_ItemnameTagid` (`type_id`,`content_item_id`,`tag_id`),
  KEY `idx_tag_type` (`tag_id`,`type_id`),
  KEY `idx_date_id` (`tag_date`,`tag_id`),
  KEY `idx_core_content_id` (`core_content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maps items from content tables to tags';



# Dump of table vol_core_log_searches
# ------------------------------------------------------------

CREATE TABLE `vol_core_log_searches` (
  `search_term` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hits` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_extensions
# ------------------------------------------------------------

CREATE TABLE `vol_extensions` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `element` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `enabled` tinyint(3) NOT NULL DEFAULT '1',
  `access` int(10) unsigned NOT NULL DEFAULT '1',
  `protected` tinyint(3) NOT NULL DEFAULT '0',
  `manifest_cache` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `system_data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) DEFAULT '0',
  `state` int(11) DEFAULT '0',
  PRIMARY KEY (`extension_id`),
  KEY `element_clientid` (`element`,`client_id`),
  KEY `element_folder_clientid` (`element`,`folder`,`client_id`),
  KEY `extension` (`type`,`element`,`folder`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_extensions` WRITE;
/*!40000 ALTER TABLE `vol_extensions` DISABLE KEYS */;

INSERT INTO `vol_extensions` (`extension_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
VALUES
	(1,'com_mailto','component','com_mailto','',0,1,1,1,'{\"name\":\"com_mailto\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\\t\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_MAILTO_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mailto\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(2,'com_wrapper','component','com_wrapper','',0,1,1,1,'{\"name\":\"com_wrapper\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\\n\\t\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_WRAPPER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"wrapper\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(3,'com_admin','component','com_admin','',1,1,1,1,'{\"name\":\"com_admin\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_ADMIN_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(4,'com_banners','component','com_banners','',1,1,1,0,'{\"name\":\"com_banners\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_BANNERS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"banners\"}','{\"purchase_type\":\"3\",\"track_impressions\":\"0\",\"track_clicks\":\"0\",\"metakey_prefix\":\"\",\"save_history\":\"1\",\"history_limit\":10}','','',0,'0000-00-00 00:00:00',0,0),
	(5,'com_cache','component','com_cache','',1,1,1,1,'{\"name\":\"com_cache\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CACHE_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(6,'com_categories','component','com_categories','',1,1,1,1,'{\"name\":\"com_categories\",\"type\":\"component\",\"creationDate\":\"December 2007\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CATEGORIES_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(7,'com_checkin','component','com_checkin','',1,1,1,1,'{\"name\":\"com_checkin\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CHECKIN_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(8,'com_contact','component','com_contact','',1,1,1,0,'{\"name\":\"com_contact\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CONTACT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contact\"}','{\"show_contact_category\":\"hide\",\"save_history\":\"1\",\"history_limit\":10,\"show_contact_list\":\"0\",\"presentation_style\":\"sliders\",\"show_name\":\"1\",\"show_position\":\"1\",\"show_email\":\"0\",\"show_street_address\":\"1\",\"show_suburb\":\"1\",\"show_state\":\"1\",\"show_postcode\":\"1\",\"show_country\":\"1\",\"show_telephone\":\"1\",\"show_mobile\":\"1\",\"show_fax\":\"1\",\"show_webpage\":\"1\",\"show_misc\":\"1\",\"show_image\":\"1\",\"image\":\"\",\"allow_vcard\":\"0\",\"show_articles\":\"0\",\"show_profile\":\"0\",\"show_links\":\"0\",\"linka_name\":\"\",\"linkb_name\":\"\",\"linkc_name\":\"\",\"linkd_name\":\"\",\"linke_name\":\"\",\"contact_icons\":\"0\",\"icon_address\":\"\",\"icon_email\":\"\",\"icon_telephone\":\"\",\"icon_mobile\":\"\",\"icon_fax\":\"\",\"icon_misc\":\"\",\"show_headings\":\"1\",\"show_position_headings\":\"1\",\"show_email_headings\":\"0\",\"show_telephone_headings\":\"1\",\"show_mobile_headings\":\"0\",\"show_fax_headings\":\"0\",\"allow_vcard_headings\":\"0\",\"show_suburb_headings\":\"1\",\"show_state_headings\":\"1\",\"show_country_headings\":\"1\",\"show_email_form\":\"1\",\"show_email_copy\":\"1\",\"banned_email\":\"\",\"banned_subject\":\"\",\"banned_text\":\"\",\"validate_session\":\"1\",\"custom_reply\":\"0\",\"redirect\":\"\",\"show_category_crumb\":\"0\",\"metakey\":\"\",\"metadesc\":\"\",\"robots\":\"\",\"author\":\"\",\"rights\":\"\",\"xreference\":\"\"}','','',0,'0000-00-00 00:00:00',0,0),
	(9,'com_cpanel','component','com_cpanel','',1,1,1,1,'{\"name\":\"com_cpanel\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CPANEL_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(10,'com_installer','component','com_installer','',1,1,1,1,'{\"name\":\"com_installer\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_INSTALLER_XML_DESCRIPTION\",\"group\":\"\"}','{\"show_jed_info\":\"0\",\"cachetimeout\":\"6\",\"minimum_stability\":\"4\"}','','',0,'0000-00-00 00:00:00',0,0),
	(11,'com_languages','component','com_languages','',1,1,1,1,'{\"name\":\"com_languages\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_LANGUAGES_XML_DESCRIPTION\",\"group\":\"\"}','{\"administrator\":\"en-GB\",\"site\":\"en-GB\"}','','',0,'0000-00-00 00:00:00',0,0),
	(12,'com_login','component','com_login','',1,1,1,1,'{\"name\":\"com_login\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_LOGIN_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(13,'com_media','component','com_media','',1,1,0,1,'{\"name\":\"com_media\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_MEDIA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"media\"}','{\"upload_extensions\":\"bmp,csv,doc,gif,ico,jpg,jpeg,odg,odp,ods,odt,pdf,png,ppt,swf,txt,xcf,xls,BMP,CSV,DOC,GIF,ICO,JPG,JPEG,ODG,ODP,ODS,ODT,PDF,PNG,PPT,SWF,TXT,XCF,XLS\",\"upload_maxsize\":\"10\",\"file_path\":\"images\",\"image_path\":\"images\",\"restrict_uploads\":\"1\",\"allowed_media_usergroup\":\"3\",\"check_mime\":\"1\",\"image_extensions\":\"bmp,gif,jpg,png\",\"ignore_extensions\":\"\",\"upload_mime\":\"image\\/jpeg,image\\/gif,image\\/png,image\\/bmp,application\\/x-shockwave-flash,application\\/msword,application\\/excel,application\\/pdf,application\\/powerpoint,text\\/plain,application\\/x-zip\",\"upload_mime_illegal\":\"text\\/html\"}','','',0,'0000-00-00 00:00:00',0,0),
	(14,'com_menus','component','com_menus','',1,1,1,1,'{\"name\":\"com_menus\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_MENUS_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(15,'com_messages','component','com_messages','',1,1,1,1,'{\"name\":\"com_messages\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_MESSAGES_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(16,'com_modules','component','com_modules','',1,1,1,1,'{\"name\":\"com_modules\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_MODULES_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(17,'com_newsfeeds','component','com_newsfeeds','',1,1,1,0,'{\"name\":\"com_newsfeeds\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_NEWSFEEDS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"newsfeeds\"}','{\"newsfeed_layout\":\"_:default\",\"save_history\":\"1\",\"history_limit\":5,\"show_feed_image\":\"1\",\"show_feed_description\":\"1\",\"show_item_description\":\"1\",\"feed_character_count\":\"0\",\"feed_display_order\":\"des\",\"float_first\":\"right\",\"float_second\":\"right\",\"show_tags\":\"1\",\"category_layout\":\"_:default\",\"show_category_title\":\"1\",\"show_description\":\"1\",\"show_description_image\":\"1\",\"maxLevel\":\"-1\",\"show_empty_categories\":\"0\",\"show_subcat_desc\":\"1\",\"show_cat_items\":\"1\",\"show_cat_tags\":\"1\",\"show_base_description\":\"1\",\"maxLevelcat\":\"-1\",\"show_empty_categories_cat\":\"0\",\"show_subcat_desc_cat\":\"1\",\"show_cat_items_cat\":\"1\",\"filter_field\":\"1\",\"show_pagination_limit\":\"1\",\"show_headings\":\"1\",\"show_articles\":\"0\",\"show_link\":\"1\",\"show_pagination\":\"1\",\"show_pagination_results\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(18,'com_plugins','component','com_plugins','',1,1,1,1,'{\"name\":\"com_plugins\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_PLUGINS_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(19,'com_search','component','com_search','',1,1,1,0,'{\"name\":\"com_search\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_SEARCH_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"search\"}','{\"enabled\":\"0\",\"show_date\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(20,'com_templates','component','com_templates','',1,1,1,1,'{\"name\":\"com_templates\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_TEMPLATES_XML_DESCRIPTION\",\"group\":\"\"}','{\"template_positions_display\":\"0\",\"upload_limit\":\"10\",\"image_formats\":\"gif,bmp,jpg,jpeg,png\",\"source_formats\":\"txt,less,ini,xml,js,php,css\",\"font_formats\":\"woff,ttf,otf\",\"compressed_formats\":\"zip\"}','','',0,'0000-00-00 00:00:00',0,0),
	(22,'com_content','component','com_content','',1,1,0,1,'{\"name\":\"com_content\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CONTENT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"content\"}','{\"article_layout\":\"_:default\",\"show_title\":\"1\",\"link_titles\":\"1\",\"show_intro\":\"1\",\"info_block_position\":\"0\",\"info_block_show_title\":\"0\",\"show_category\":\"0\",\"link_category\":\"0\",\"show_parent_category\":\"0\",\"link_parent_category\":\"0\",\"show_author\":\"0\",\"link_author\":\"0\",\"show_create_date\":\"0\",\"show_modify_date\":\"0\",\"show_publish_date\":\"0\",\"show_item_navigation\":\"0\",\"show_vote\":\"0\",\"show_readmore\":\"0\",\"show_readmore_title\":\"1\",\"readmore_limit\":\"100\",\"show_tags\":\"0\",\"show_icons\":\"0\",\"show_print_icon\":\"0\",\"show_email_icon\":\"0\",\"show_hits\":\"0\",\"show_noauth\":\"0\",\"urls_position\":\"0\",\"show_publishing_options\":\"1\",\"show_article_options\":\"1\",\"save_history\":\"1\",\"history_limit\":10,\"show_urls_images_frontend\":\"0\",\"show_urls_images_backend\":\"1\",\"targeta\":0,\"targetb\":0,\"targetc\":0,\"float_intro\":\"left\",\"float_fulltext\":\"left\",\"category_layout\":\"_:blog\",\"show_category_heading_title_text\":\"1\",\"show_category_title\":\"0\",\"show_description\":\"0\",\"show_description_image\":\"0\",\"maxLevel\":\"1\",\"show_empty_categories\":\"0\",\"show_no_articles\":\"1\",\"show_subcat_desc\":\"1\",\"show_cat_num_articles\":\"0\",\"show_cat_tags\":\"1\",\"show_base_description\":\"1\",\"maxLevelcat\":\"-1\",\"show_empty_categories_cat\":\"0\",\"show_subcat_desc_cat\":\"1\",\"show_cat_num_articles_cat\":\"1\",\"num_leading_articles\":\"1\",\"num_intro_articles\":\"4\",\"num_columns\":\"2\",\"num_links\":\"4\",\"multi_column_order\":\"0\",\"show_subcategory_content\":\"0\",\"show_pagination_limit\":\"1\",\"filter_field\":\"hide\",\"show_headings\":\"1\",\"list_show_date\":\"0\",\"date_format\":\"\",\"list_show_hits\":\"1\",\"list_show_author\":\"1\",\"orderby_pri\":\"order\",\"orderby_sec\":\"rdate\",\"order_date\":\"published\",\"show_pagination\":\"2\",\"show_pagination_results\":\"1\",\"show_featured\":\"show\",\"show_feed_link\":\"1\",\"feed_summary\":\"0\",\"feed_show_readmore\":\"0\"}','','',0,'0000-00-00 00:00:00',0,0),
	(23,'com_config','component','com_config','',1,1,0,1,'{\"name\":\"com_config\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_CONFIG_XML_DESCRIPTION\",\"group\":\"\"}','{\"filters\":{\"1\":{\"filter_type\":\"NH\",\"filter_tags\":\"\",\"filter_attributes\":\"\"},\"9\":{\"filter_type\":\"BL\",\"filter_tags\":\"\",\"filter_attributes\":\"\"},\"8\":{\"filter_type\":\"NONE\",\"filter_tags\":\"\",\"filter_attributes\":\"\"},\"2\":{\"filter_type\":\"BL\",\"filter_tags\":\"\",\"filter_attributes\":\"\"},\"10\":{\"filter_type\":\"BL\",\"filter_tags\":\"\",\"filter_attributes\":\"\"}}}','','',0,'0000-00-00 00:00:00',0,0),
	(24,'com_redirect','component','com_redirect','',1,1,0,1,'{\"name\":\"com_redirect\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_REDIRECT_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(25,'com_users','component','com_users','',1,1,0,1,'{\"name\":\"com_users\",\"type\":\"component\",\"creationDate\":\"April 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_USERS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"users\"}','{\"allowUserRegistration\":\"0\",\"new_usertype\":\"2\",\"guest_usergroup\":\"9\",\"sendpassword\":\"1\",\"useractivation\":\"1\",\"mail_to_admin\":\"0\",\"captcha\":\"0\",\"frontend_userparams\":\"1\",\"site_language\":\"0\",\"change_login_name\":\"0\",\"reset_count\":\"10\",\"reset_time\":\"1\",\"minimum_length\":\"4\",\"minimum_integers\":\"0\",\"minimum_symbols\":\"0\",\"minimum_uppercase\":\"0\",\"save_history\":\"1\",\"history_limit\":5,\"mailSubjectPrefix\":\"\",\"mailBodySuffix\":\"\"}','','',0,'0000-00-00 00:00:00',0,0),
	(27,'com_finder','component','com_finder','',1,1,0,0,'{\"name\":\"com_finder\",\"type\":\"component\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"COM_FINDER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"finder\"}','{\"show_description\":\"1\",\"description_length\":255,\"allow_empty_query\":\"0\",\"show_url\":\"1\",\"show_advanced\":\"1\",\"expand_advanced\":\"0\",\"show_date_filters\":\"0\",\"highlight_terms\":\"1\",\"opensearch_name\":\"\",\"opensearch_description\":\"\",\"batch_size\":\"50\",\"memory_table_limit\":30000,\"title_multiplier\":\"1.7\",\"text_multiplier\":\"0.7\",\"meta_multiplier\":\"1.2\",\"path_multiplier\":\"2.0\",\"misc_multiplier\":\"0.3\",\"stemmer\":\"snowball\"}','','',0,'0000-00-00 00:00:00',0,0),
	(28,'com_joomlaupdate','component','com_joomlaupdate','',1,1,0,1,'{\"name\":\"com_joomlaupdate\",\"type\":\"component\",\"creationDate\":\"February 2012\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.2\",\"description\":\"COM_JOOMLAUPDATE_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(29,'com_tags','component','com_tags','',1,1,1,1,'{\"name\":\"com_tags\",\"type\":\"component\",\"creationDate\":\"December 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.1.0\",\"description\":\"COM_TAGS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"tags\"}','{\"tag_layout\":\"_:default\",\"save_history\":\"1\",\"history_limit\":5,\"show_tag_title\":\"0\",\"tag_list_show_tag_image\":\"0\",\"tag_list_show_tag_description\":\"0\",\"tag_list_image\":\"\",\"show_tag_num_items\":\"0\",\"tag_list_orderby\":\"title\",\"tag_list_orderby_direction\":\"ASC\",\"show_headings\":\"0\",\"tag_list_show_date\":\"0\",\"tag_list_show_item_image\":\"0\",\"tag_list_show_item_description\":\"0\",\"tag_list_item_maximum_characters\":0,\"return_any_or_all\":\"1\",\"include_children\":\"0\",\"maximum\":200,\"tag_list_language_filter\":\"all\",\"tags_layout\":\"_:default\",\"all_tags_orderby\":\"title\",\"all_tags_orderby_direction\":\"ASC\",\"all_tags_show_tag_image\":\"0\",\"all_tags_show_tag_descripion\":\"0\",\"all_tags_tag_maximum_characters\":20,\"all_tags_show_tag_hits\":\"0\",\"filter_field\":\"1\",\"show_pagination_limit\":\"1\",\"show_pagination\":\"2\",\"show_pagination_results\":\"1\",\"tag_field_ajax_mode\":\"1\",\"show_feed_link\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(30,'com_contenthistory','component','com_contenthistory','',1,1,1,0,'{\"name\":\"com_contenthistory\",\"type\":\"component\",\"creationDate\":\"May 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.0\",\"description\":\"COM_CONTENTHISTORY_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contenthistory\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(31,'com_ajax','component','com_ajax','',1,1,1,1,'{\"name\":\"com_ajax\",\"type\":\"component\",\"creationDate\":\"August 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.0\",\"description\":\"COM_AJAX_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"ajax\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(32,'com_postinstall','component','com_postinstall','',1,1,1,1,'{\"name\":\"com_postinstall\",\"type\":\"component\",\"creationDate\":\"September 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.0\",\"description\":\"COM_POSTINSTALL_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(102,'phputf8','library','phputf8','',0,1,1,1,'{\"name\":\"phputf8\",\"type\":\"library\",\"creationDate\":\"2006\",\"author\":\"Harry Fuecks\",\"copyright\":\"Copyright various authors\",\"authorEmail\":\"hfuecks@gmail.com\",\"authorUrl\":\"http:\\/\\/sourceforge.net\\/projects\\/phputf8\",\"version\":\"0.5\",\"description\":\"LIB_PHPUTF8_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"phputf8\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(103,'Joomla! Platform','library','joomla','',0,1,1,1,'{\"name\":\"Joomla! Platform\",\"type\":\"library\",\"creationDate\":\"2008\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"https:\\/\\/www.joomla.org\",\"version\":\"13.1\",\"description\":\"LIB_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomla\"}','{\"mediaversion\":\"2092e6cde719b40c6731fab11e408840\"}','','',0,'0000-00-00 00:00:00',0,0),
	(104,'IDNA Convert','library','idna_convert','',0,1,1,1,'{\"name\":\"IDNA Convert\",\"type\":\"library\",\"creationDate\":\"2004\",\"author\":\"phlyLabs\",\"copyright\":\"2004-2011 phlyLabs Berlin, http:\\/\\/phlylabs.de\",\"authorEmail\":\"phlymail@phlylabs.de\",\"authorUrl\":\"http:\\/\\/phlylabs.de\",\"version\":\"0.8.0\",\"description\":\"LIB_IDNA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"idna_convert\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(105,'FOF','library','fof','',0,1,1,1,'{\"name\":\"FOF\",\"type\":\"library\",\"creationDate\":\"2015-04-22 13:15:32\",\"author\":\"Nicholas K. Dionysopoulos \\/ Akeeba Ltd\",\"copyright\":\"(C)2011-2015 Nicholas K. Dionysopoulos\",\"authorEmail\":\"nicholas@akeebabackup.com\",\"authorUrl\":\"https:\\/\\/www.akeebabackup.com\",\"version\":\"2.4.3\",\"description\":\"LIB_FOF_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"fof\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(106,'PHPass','library','phpass','',0,1,1,1,'{\"name\":\"PHPass\",\"type\":\"library\",\"creationDate\":\"2004-2006\",\"author\":\"Solar Designer\",\"copyright\":\"\",\"authorEmail\":\"solar@openwall.com\",\"authorUrl\":\"http:\\/\\/www.openwall.com\\/phpass\\/\",\"version\":\"0.3\",\"description\":\"LIB_PHPASS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"phpass\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(200,'mod_articles_archive','module','mod_articles_archive','',0,1,1,0,'{\"name\":\"mod_articles_archive\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_ARTICLES_ARCHIVE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_archive\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(201,'mod_articles_latest','module','mod_articles_latest','',0,1,1,0,'{\"name\":\"mod_articles_latest\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_LATEST_NEWS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_latest\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(202,'mod_articles_popular','module','mod_articles_popular','',0,1,1,0,'{\"name\":\"mod_articles_popular\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_POPULAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_popular\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(203,'mod_banners','module','mod_banners','',0,1,1,0,'{\"name\":\"mod_banners\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_BANNERS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_banners\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(204,'mod_breadcrumbs','module','mod_breadcrumbs','',0,1,1,1,'{\"name\":\"mod_breadcrumbs\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_BREADCRUMBS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_breadcrumbs\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(205,'mod_custom','module','mod_custom','',0,1,1,1,'{\"name\":\"mod_custom\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_CUSTOM_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_custom\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(206,'mod_feed','module','mod_feed','',0,1,1,0,'{\"name\":\"mod_feed\",\"type\":\"module\",\"creationDate\":\"July 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_FEED_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_feed\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(207,'mod_footer','module','mod_footer','',0,1,1,0,'{\"name\":\"mod_footer\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_FOOTER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_footer\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(208,'mod_login','module','mod_login','',0,1,1,1,'{\"name\":\"mod_login\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_LOGIN_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_login\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(209,'mod_menu','module','mod_menu','',0,1,1,1,'{\"name\":\"mod_menu\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_MENU_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_menu\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(210,'mod_articles_news','module','mod_articles_news','',0,1,1,0,'{\"name\":\"mod_articles_news\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_ARTICLES_NEWS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_news\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(211,'mod_random_image','module','mod_random_image','',0,1,1,0,'{\"name\":\"mod_random_image\",\"type\":\"module\",\"creationDate\":\"July 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_RANDOM_IMAGE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_random_image\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(212,'mod_related_items','module','mod_related_items','',0,1,1,0,'{\"name\":\"mod_related_items\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_RELATED_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_related_items\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(213,'mod_search','module','mod_search','',0,1,1,0,'{\"name\":\"mod_search\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_SEARCH_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_search\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(214,'mod_stats','module','mod_stats','',0,1,1,0,'{\"name\":\"mod_stats\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_STATS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_stats\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(215,'mod_syndicate','module','mod_syndicate','',0,1,1,1,'{\"name\":\"mod_syndicate\",\"type\":\"module\",\"creationDate\":\"May 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_SYNDICATE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_syndicate\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(216,'mod_users_latest','module','mod_users_latest','',0,1,1,0,'{\"name\":\"mod_users_latest\",\"type\":\"module\",\"creationDate\":\"December 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_USERS_LATEST_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_users_latest\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(218,'mod_whosonline','module','mod_whosonline','',0,1,1,0,'{\"name\":\"mod_whosonline\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_WHOSONLINE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_whosonline\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(219,'mod_wrapper','module','mod_wrapper','',0,1,1,0,'{\"name\":\"mod_wrapper\",\"type\":\"module\",\"creationDate\":\"October 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_WRAPPER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_wrapper\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(220,'mod_articles_category','module','mod_articles_category','',0,1,1,0,'{\"name\":\"mod_articles_category\",\"type\":\"module\",\"creationDate\":\"February 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_ARTICLES_CATEGORY_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_category\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(221,'mod_articles_categories','module','mod_articles_categories','',0,1,1,0,'{\"name\":\"mod_articles_categories\",\"type\":\"module\",\"creationDate\":\"February 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_ARTICLES_CATEGORIES_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_articles_categories\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(222,'mod_languages','module','mod_languages','',0,1,1,1,'{\"name\":\"mod_languages\",\"type\":\"module\",\"creationDate\":\"February 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.5.0\",\"description\":\"MOD_LANGUAGES_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_languages\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(223,'mod_finder','module','mod_finder','',0,1,0,0,'{\"name\":\"mod_finder\",\"type\":\"module\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_FINDER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_finder\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(300,'mod_custom','module','mod_custom','',1,1,1,1,'{\"name\":\"mod_custom\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_CUSTOM_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_custom\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(301,'mod_feed','module','mod_feed','',1,1,1,0,'{\"name\":\"mod_feed\",\"type\":\"module\",\"creationDate\":\"July 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_FEED_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_feed\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(302,'mod_latest','module','mod_latest','',1,1,1,0,'{\"name\":\"mod_latest\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_LATEST_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_latest\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(303,'mod_logged','module','mod_logged','',1,1,1,0,'{\"name\":\"mod_logged\",\"type\":\"module\",\"creationDate\":\"January 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_LOGGED_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_logged\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(304,'mod_login','module','mod_login','',1,1,1,1,'{\"name\":\"mod_login\",\"type\":\"module\",\"creationDate\":\"March 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_LOGIN_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_login\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(305,'mod_menu','module','mod_menu','',1,1,1,0,'{\"name\":\"mod_menu\",\"type\":\"module\",\"creationDate\":\"March 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_MENU_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_menu\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(307,'mod_popular','module','mod_popular','',1,1,1,0,'{\"name\":\"mod_popular\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_POPULAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_popular\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(308,'mod_quickicon','module','mod_quickicon','',1,1,1,1,'{\"name\":\"mod_quickicon\",\"type\":\"module\",\"creationDate\":\"Nov 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_QUICKICON_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_quickicon\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(309,'mod_status','module','mod_status','',1,1,1,0,'{\"name\":\"mod_status\",\"type\":\"module\",\"creationDate\":\"Feb 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_STATUS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_status\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(310,'mod_submenu','module','mod_submenu','',1,1,1,0,'{\"name\":\"mod_submenu\",\"type\":\"module\",\"creationDate\":\"Feb 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_SUBMENU_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_submenu\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(311,'mod_title','module','mod_title','',1,1,1,0,'{\"name\":\"mod_title\",\"type\":\"module\",\"creationDate\":\"Nov 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_TITLE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_title\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(312,'mod_toolbar','module','mod_toolbar','',1,1,1,1,'{\"name\":\"mod_toolbar\",\"type\":\"module\",\"creationDate\":\"Nov 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_TOOLBAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_toolbar\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(313,'mod_multilangstatus','module','mod_multilangstatus','',1,1,1,0,'{\"name\":\"mod_multilangstatus\",\"type\":\"module\",\"creationDate\":\"September 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_MULTILANGSTATUS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_multilangstatus\"}','{\"cache\":\"0\"}','','',0,'0000-00-00 00:00:00',0,0),
	(314,'mod_version','module','mod_version','',1,1,1,0,'{\"name\":\"mod_version\",\"type\":\"module\",\"creationDate\":\"January 2012\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_VERSION_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_version\"}','{\"format\":\"short\",\"product\":\"1\",\"cache\":\"0\"}','','',0,'0000-00-00 00:00:00',0,0),
	(315,'mod_stats_admin','module','mod_stats_admin','',1,1,1,0,'{\"name\":\"mod_stats_admin\",\"type\":\"module\",\"creationDate\":\"July 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"MOD_STATS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_stats_admin\"}','{\"serverinfo\":\"0\",\"siteinfo\":\"0\",\"counter\":\"0\",\"increase\":\"0\",\"cache\":\"1\",\"cache_time\":\"900\",\"cachemode\":\"static\"}','','',0,'0000-00-00 00:00:00',0,0),
	(316,'mod_tags_popular','module','mod_tags_popular','',0,1,1,0,'{\"name\":\"mod_tags_popular\",\"type\":\"module\",\"creationDate\":\"January 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.1.0\",\"description\":\"MOD_TAGS_POPULAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_tags_popular\"}','{\"maximum\":\"5\",\"timeframe\":\"alltime\",\"owncache\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(317,'mod_tags_similar','module','mod_tags_similar','',0,1,1,0,'{\"name\":\"mod_tags_similar\",\"type\":\"module\",\"creationDate\":\"January 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.1.0\",\"description\":\"MOD_TAGS_SIMILAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"mod_tags_similar\"}','{\"maximum\":\"5\",\"matchtype\":\"any\",\"owncache\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(400,'plg_authentication_gmail','plugin','gmail','authentication',0,0,1,0,'{\"name\":\"plg_authentication_gmail\",\"type\":\"plugin\",\"creationDate\":\"February 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_GMAIL_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"gmail\"}','{\"applysuffix\":\"0\",\"suffix\":\"\",\"verifypeer\":\"1\",\"user_blacklist\":\"\"}','','',0,'0000-00-00 00:00:00',1,0),
	(401,'plg_authentication_joomla','plugin','joomla','authentication',0,1,1,1,'{\"name\":\"plg_authentication_joomla\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_AUTH_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomla\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(402,'plg_authentication_ldap','plugin','ldap','authentication',0,0,1,0,'{\"name\":\"plg_authentication_ldap\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_LDAP_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"ldap\"}','{\"host\":\"\",\"port\":\"389\",\"use_ldapV3\":\"0\",\"negotiate_tls\":\"0\",\"no_referrals\":\"0\",\"auth_method\":\"bind\",\"base_dn\":\"\",\"search_string\":\"\",\"users_dn\":\"\",\"username\":\"admin\",\"password\":\"bobby7\",\"ldap_fullname\":\"fullName\",\"ldap_email\":\"mail\",\"ldap_uid\":\"uid\"}','','',0,'0000-00-00 00:00:00',3,0),
	(403,'plg_content_contact','plugin','contact','content',0,1,1,0,'{\"name\":\"plg_content_contact\",\"type\":\"plugin\",\"creationDate\":\"January 2014\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.2\",\"description\":\"PLG_CONTENT_CONTACT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contact\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(404,'plg_content_emailcloak','plugin','emailcloak','content',0,1,1,0,'{\"name\":\"plg_content_emailcloak\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CONTENT_EMAILCLOAK_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"emailcloak\"}','{\"mode\":\"1\"}','','',0,'0000-00-00 00:00:00',1,0),
	(406,'plg_content_loadmodule','plugin','loadmodule','content',0,1,1,0,'{\"name\":\"plg_content_loadmodule\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_LOADMODULE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"loadmodule\"}','{\"style\":\"xhtml\"}','','',0,'2011-09-18 15:22:50',0,0),
	(407,'plg_content_pagebreak','plugin','pagebreak','content',0,1,1,0,'{\"name\":\"plg_content_pagebreak\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CONTENT_PAGEBREAK_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"pagebreak\"}','{\"title\":\"1\",\"multipage_toc\":\"1\",\"showall\":\"1\"}','','',0,'0000-00-00 00:00:00',4,0),
	(408,'plg_content_pagenavigation','plugin','pagenavigation','content',0,1,1,0,'{\"name\":\"plg_content_pagenavigation\",\"type\":\"plugin\",\"creationDate\":\"January 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_PAGENAVIGATION_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"pagenavigation\"}','{\"position\":\"1\"}','','',0,'0000-00-00 00:00:00',5,0),
	(409,'plg_content_vote','plugin','vote','content',0,1,1,0,'{\"name\":\"plg_content_vote\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_VOTE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"vote\"}','','','',0,'0000-00-00 00:00:00',6,0),
	(410,'plg_editors_codemirror','plugin','codemirror','editors',0,1,1,1,'{\"name\":\"plg_editors_codemirror\",\"type\":\"plugin\",\"creationDate\":\"28 March 2011\",\"author\":\"Marijn Haverbeke\",\"copyright\":\"Copyright (C) 2014 by Marijn Haverbeke <marijnh@gmail.com> and others\",\"authorEmail\":\"marijnh@gmail.com\",\"authorUrl\":\"http:\\/\\/codemirror.net\\/\",\"version\":\"5.17.0\",\"description\":\"PLG_CODEMIRROR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"codemirror\"}','{\"lineNumbers\":\"1\",\"lineWrapping\":\"1\",\"matchTags\":\"1\",\"matchBrackets\":\"1\",\"marker-gutter\":\"1\",\"autoCloseTags\":\"1\",\"autoCloseBrackets\":\"1\",\"autoFocus\":\"1\",\"theme\":\"default\",\"tabmode\":\"indent\"}','','',0,'0000-00-00 00:00:00',1,0),
	(411,'plg_editors_none','plugin','none','editors',0,1,1,1,'{\"name\":\"plg_editors_none\",\"type\":\"plugin\",\"creationDate\":\"September 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_NONE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"none\"}','','','',0,'0000-00-00 00:00:00',2,0),
	(412,'plg_editors_tinymce','plugin','tinymce','editors',0,1,1,0,'{\"name\":\"plg_editors_tinymce\",\"type\":\"plugin\",\"creationDate\":\"2005-2016\",\"author\":\"Ephox Corporation\",\"copyright\":\"Ephox Corporation\",\"authorEmail\":\"N\\/A\",\"authorUrl\":\"http:\\/\\/www.tinymce.com\",\"version\":\"4.4.0\",\"description\":\"PLG_TINY_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"tinymce\"}','{\"mode\":\"1\",\"skin\":\"0\",\"mobile\":\"0\",\"entity_encoding\":\"raw\",\"lang_mode\":\"1\",\"text_direction\":\"ltr\",\"content_css\":\"1\",\"content_css_custom\":\"\",\"relative_urls\":\"1\",\"newlines\":\"0\",\"invalid_elements\":\"script,applet,iframe\",\"extended_elements\":\"\",\"html_height\":\"550\",\"html_width\":\"750\",\"resizing\":\"1\",\"element_path\":\"1\",\"fonts\":\"1\",\"paste\":\"1\",\"searchreplace\":\"1\",\"insertdate\":\"1\",\"colors\":\"1\",\"table\":\"1\",\"smilies\":\"1\",\"hr\":\"1\",\"link\":\"1\",\"media\":\"1\",\"print\":\"1\",\"directionality\":\"1\",\"fullscreen\":\"1\",\"alignment\":\"1\",\"visualchars\":\"1\",\"visualblocks\":\"1\",\"nonbreaking\":\"1\",\"template\":\"1\",\"blockquote\":\"1\",\"wordcount\":\"1\",\"advlist\":\"1\",\"autosave\":\"1\",\"contextmenu\":\"1\",\"inlinepopups\":\"1\",\"custom_plugin\":\"\",\"custom_button\":\"\"}','','',0,'0000-00-00 00:00:00',3,0),
	(413,'plg_editors-xtd_article','plugin','article','editors-xtd',0,1,1,0,'{\"name\":\"plg_editors-xtd_article\",\"type\":\"plugin\",\"creationDate\":\"October 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_ARTICLE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"article\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(414,'plg_editors-xtd_image','plugin','image','editors-xtd',0,1,1,0,'{\"name\":\"plg_editors-xtd_image\",\"type\":\"plugin\",\"creationDate\":\"August 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_IMAGE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"image\"}','','','',0,'0000-00-00 00:00:00',2,0),
	(415,'plg_editors-xtd_pagebreak','plugin','pagebreak','editors-xtd',0,1,1,0,'{\"name\":\"plg_editors-xtd_pagebreak\",\"type\":\"plugin\",\"creationDate\":\"August 2004\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_EDITORSXTD_PAGEBREAK_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"pagebreak\"}','','','',0,'0000-00-00 00:00:00',3,0),
	(416,'plg_editors-xtd_readmore','plugin','readmore','editors-xtd',0,1,1,0,'{\"name\":\"plg_editors-xtd_readmore\",\"type\":\"plugin\",\"creationDate\":\"March 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_READMORE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"readmore\"}','','','',0,'0000-00-00 00:00:00',4,0),
	(417,'plg_search_categories','plugin','categories','search',0,0,1,0,'{\"name\":\"plg_search_categories\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEARCH_CATEGORIES_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"categories\"}','{\"search_limit\":\"50\",\"search_content\":\"1\",\"search_archived\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(418,'plg_search_contacts','plugin','contacts','search',0,0,1,0,'{\"name\":\"plg_search_contacts\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEARCH_CONTACTS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contacts\"}','{\"search_limit\":\"50\",\"search_content\":\"1\",\"search_archived\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(419,'plg_search_content','plugin','content','search',0,1,1,0,'{\"name\":\"plg_search_content\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEARCH_CONTENT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"content\"}','{\"search_limit\":\"50\",\"search_content\":\"1\",\"search_archived\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(420,'plg_search_newsfeeds','plugin','newsfeeds','search',0,0,1,0,'{\"name\":\"plg_search_newsfeeds\",\"type\":\"plugin\",\"creationDate\":\"November 2005\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEARCH_NEWSFEEDS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"newsfeeds\"}','{\"search_limit\":\"50\",\"search_content\":\"1\",\"search_archived\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(422,'plg_system_languagefilter','plugin','languagefilter','system',0,0,1,1,'{\"name\":\"plg_system_languagefilter\",\"type\":\"plugin\",\"creationDate\":\"July 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SYSTEM_LANGUAGEFILTER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"languagefilter\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(423,'plg_system_p3p','plugin','p3p','system',0,0,1,0,'{\"name\":\"plg_system_p3p\",\"type\":\"plugin\",\"creationDate\":\"September 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_P3P_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"p3p\"}','{\"headers\":\"NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM\"}','','',0,'0000-00-00 00:00:00',2,0),
	(424,'plg_system_cache','plugin','cache','system',0,0,1,1,'{\"name\":\"plg_system_cache\",\"type\":\"plugin\",\"creationDate\":\"February 2007\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CACHE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"cache\"}','{\"browsercache\":\"0\",\"cachetime\":\"15\"}','','',0,'0000-00-00 00:00:00',9,0),
	(425,'plg_system_debug','plugin','debug','system',0,1,1,0,'{\"name\":\"plg_system_debug\",\"type\":\"plugin\",\"creationDate\":\"December 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_DEBUG_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"debug\"}','{\"profile\":\"1\",\"queries\":\"1\",\"memory\":\"1\",\"language_files\":\"1\",\"language_strings\":\"1\",\"strip-first\":\"1\",\"strip-prefix\":\"\",\"strip-suffix\":\"\"}','','',0,'0000-00-00 00:00:00',4,0),
	(426,'plg_system_log','plugin','log','system',0,1,1,1,'{\"name\":\"plg_system_log\",\"type\":\"plugin\",\"creationDate\":\"April 2007\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_LOG_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"log\"}','','','',0,'0000-00-00 00:00:00',5,0),
	(427,'plg_system_redirect','plugin','redirect','system',0,0,1,1,'{\"name\":\"plg_system_redirect\",\"type\":\"plugin\",\"creationDate\":\"April 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SYSTEM_REDIRECT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"redirect\"}','{\"collect_urls\":\"1\"}','','',0,'0000-00-00 00:00:00',6,0),
	(428,'plg_system_remember','plugin','remember','system',0,1,1,1,'{\"name\":\"plg_system_remember\",\"type\":\"plugin\",\"creationDate\":\"April 2007\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_REMEMBER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"remember\"}','','','',0,'0000-00-00 00:00:00',7,0),
	(429,'plg_system_sef','plugin','sef','system',0,1,1,0,'{\"name\":\"plg_system_sef\",\"type\":\"plugin\",\"creationDate\":\"December 2007\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEF_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"sef\"}','','','',0,'0000-00-00 00:00:00',8,0),
	(430,'plg_system_logout','plugin','logout','system',0,1,1,1,'{\"name\":\"plg_system_logout\",\"type\":\"plugin\",\"creationDate\":\"April 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SYSTEM_LOGOUT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"logout\"}','','','',0,'0000-00-00 00:00:00',3,0),
	(431,'plg_user_contactcreator','plugin','contactcreator','user',0,0,1,0,'{\"name\":\"plg_user_contactcreator\",\"type\":\"plugin\",\"creationDate\":\"August 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CONTACTCREATOR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contactcreator\"}','{\"autowebpage\":\"\",\"category\":\"34\",\"autopublish\":\"0\"}','','',0,'0000-00-00 00:00:00',1,0),
	(432,'plg_user_joomla','plugin','joomla','user',0,1,1,0,'{\"name\":\"plg_user_joomla\",\"type\":\"plugin\",\"creationDate\":\"December 2006\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_USER_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomla\"}','{\"autoregister\":\"1\",\"mail_to_user\":\"1\",\"forceLogout\":\"1\"}','','',0,'0000-00-00 00:00:00',2,0),
	(433,'plg_user_profile','plugin','profile','user',0,0,1,0,'{\"name\":\"plg_user_profile\",\"type\":\"plugin\",\"creationDate\":\"January 2008\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_USER_PROFILE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"profile\"}','{\"register-require_address1\":\"1\",\"register-require_address2\":\"1\",\"register-require_city\":\"1\",\"register-require_region\":\"1\",\"register-require_country\":\"1\",\"register-require_postal_code\":\"1\",\"register-require_phone\":\"1\",\"register-require_website\":\"1\",\"register-require_favoritebook\":\"1\",\"register-require_aboutme\":\"1\",\"register-require_tos\":\"1\",\"register-require_dob\":\"1\",\"profile-require_address1\":\"1\",\"profile-require_address2\":\"1\",\"profile-require_city\":\"1\",\"profile-require_region\":\"1\",\"profile-require_country\":\"1\",\"profile-require_postal_code\":\"1\",\"profile-require_phone\":\"1\",\"profile-require_website\":\"1\",\"profile-require_favoritebook\":\"1\",\"profile-require_aboutme\":\"1\",\"profile-require_tos\":\"1\",\"profile-require_dob\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(434,'plg_extension_joomla','plugin','joomla','extension',0,1,1,1,'{\"name\":\"plg_extension_joomla\",\"type\":\"plugin\",\"creationDate\":\"May 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_EXTENSION_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomla\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(435,'plg_content_joomla','plugin','joomla','content',0,1,1,0,'{\"name\":\"plg_content_joomla\",\"type\":\"plugin\",\"creationDate\":\"November 2010\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CONTENT_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomla\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(436,'plg_system_languagecode','plugin','languagecode','system',0,0,1,0,'{\"name\":\"plg_system_languagecode\",\"type\":\"plugin\",\"creationDate\":\"November 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SYSTEM_LANGUAGECODE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"languagecode\"}','','','',0,'0000-00-00 00:00:00',10,0),
	(437,'plg_quickicon_joomlaupdate','plugin','joomlaupdate','quickicon',0,1,1,1,'{\"name\":\"plg_quickicon_joomlaupdate\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_QUICKICON_JOOMLAUPDATE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"joomlaupdate\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(438,'plg_quickicon_extensionupdate','plugin','extensionupdate','quickicon',0,1,1,1,'{\"name\":\"plg_quickicon_extensionupdate\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_QUICKICON_EXTENSIONUPDATE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"extensionupdate\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(439,'plg_captcha_recaptcha','plugin','recaptcha','captcha',0,0,1,0,'{\"name\":\"plg_captcha_recaptcha\",\"type\":\"plugin\",\"creationDate\":\"December 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.4.0\",\"description\":\"PLG_CAPTCHA_RECAPTCHA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"recaptcha\"}','{\"public_key\":\"\",\"private_key\":\"\",\"theme\":\"clean\"}','','',0,'0000-00-00 00:00:00',0,0),
	(440,'plg_system_highlight','plugin','highlight','system',0,1,1,0,'{\"name\":\"plg_system_highlight\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SYSTEM_HIGHLIGHT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"highlight\"}','','','',0,'0000-00-00 00:00:00',7,0),
	(441,'plg_content_finder','plugin','finder','content',0,0,1,0,'{\"name\":\"plg_content_finder\",\"type\":\"plugin\",\"creationDate\":\"December 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_CONTENT_FINDER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"finder\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(442,'plg_finder_categories','plugin','categories','finder',0,1,1,0,'{\"name\":\"plg_finder_categories\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_FINDER_CATEGORIES_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"categories\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(443,'plg_finder_contacts','plugin','contacts','finder',0,1,1,0,'{\"name\":\"plg_finder_contacts\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_FINDER_CONTACTS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"contacts\"}','','','',0,'0000-00-00 00:00:00',2,0),
	(444,'plg_finder_content','plugin','content','finder',0,1,1,0,'{\"name\":\"plg_finder_content\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_FINDER_CONTENT_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"content\"}','','','',0,'0000-00-00 00:00:00',3,0),
	(445,'plg_finder_newsfeeds','plugin','newsfeeds','finder',0,1,1,0,'{\"name\":\"plg_finder_newsfeeds\",\"type\":\"plugin\",\"creationDate\":\"August 2011\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_FINDER_NEWSFEEDS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"newsfeeds\"}','','','',0,'0000-00-00 00:00:00',4,0),
	(447,'plg_finder_tags','plugin','tags','finder',0,1,1,0,'{\"name\":\"plg_finder_tags\",\"type\":\"plugin\",\"creationDate\":\"February 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_FINDER_TAGS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"tags\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(448,'plg_twofactorauth_totp','plugin','totp','twofactorauth',0,0,1,0,'{\"name\":\"plg_twofactorauth_totp\",\"type\":\"plugin\",\"creationDate\":\"August 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.0\",\"description\":\"PLG_TWOFACTORAUTH_TOTP_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"totp\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(449,'plg_authentication_cookie','plugin','cookie','authentication',0,1,1,0,'{\"name\":\"plg_authentication_cookie\",\"type\":\"plugin\",\"creationDate\":\"July 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_AUTH_COOKIE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"cookie\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(450,'plg_twofactorauth_yubikey','plugin','yubikey','twofactorauth',0,0,1,0,'{\"name\":\"plg_twofactorauth_yubikey\",\"type\":\"plugin\",\"creationDate\":\"September 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.2.0\",\"description\":\"PLG_TWOFACTORAUTH_YUBIKEY_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"yubikey\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(451,'plg_search_tags','plugin','tags','search',0,0,1,0,'{\"name\":\"plg_search_tags\",\"type\":\"plugin\",\"creationDate\":\"March 2014\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"PLG_SEARCH_TAGS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"tags\"}','{\"search_limit\":\"50\",\"show_tagged_items\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(452,'plg_system_updatenotification','plugin','updatenotification','system',0,1,1,0,'{\"name\":\"plg_system_updatenotification\",\"type\":\"plugin\",\"creationDate\":\"May 2015\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.5.0\",\"description\":\"PLG_SYSTEM_UPDATENOTIFICATION_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"updatenotification\"}','{\"lastrun\":1471975659}','','',0,'0000-00-00 00:00:00',0,0),
	(453,'plg_editors-xtd_module','plugin','module','editors-xtd',0,1,1,0,'{\"name\":\"plg_editors-xtd_module\",\"type\":\"plugin\",\"creationDate\":\"October 2015\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.5.0\",\"description\":\"PLG_MODULE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"module\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(454,'plg_system_stats','plugin','stats','system',0,1,1,0,'{\"name\":\"plg_system_stats\",\"type\":\"plugin\",\"creationDate\":\"November 2013\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.5.0\",\"description\":\"PLG_SYSTEM_STATS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"stats\"}','{\"mode\":1,\"lastrun\":1471949874,\"unique_id\":\"ea39e60780cafb2cee82c7df5ed5cf685d304403\",\"interval\":12}','','',0,'0000-00-00 00:00:00',0,0),
	(455,'plg_installer_packageinstaller','plugin','packageinstaller','installer',0,1,1,1,'{\"name\":\"plg_installer_packageinstaller\",\"type\":\"plugin\",\"creationDate\":\"May 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.0\",\"description\":\"PLG_INSTALLER_PACKAGEINSTALLER_PLUGIN_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"packageinstaller\"}','','','',0,'0000-00-00 00:00:00',1,0),
	(456,'PLG_INSTALLER_FOLDERINSTALLER','plugin','folderinstaller','installer',0,1,1,1,'{\"name\":\"PLG_INSTALLER_FOLDERINSTALLER\",\"type\":\"plugin\",\"creationDate\":\"May 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.0\",\"description\":\"PLG_INSTALLER_FOLDERINSTALLER_PLUGIN_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"folderinstaller\"}','','','',0,'0000-00-00 00:00:00',2,0),
	(457,'PLG_INSTALLER_URLINSTALLER','plugin','urlinstaller','installer',0,1,1,1,'{\"name\":\"PLG_INSTALLER_URLINSTALLER\",\"type\":\"plugin\",\"creationDate\":\"May 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.0\",\"description\":\"PLG_INSTALLER_URLINSTALLER_PLUGIN_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"urlinstaller\"}','','','',0,'0000-00-00 00:00:00',3,0),
	(503,'beez3','template','beez3','',0,1,1,0,'{\"name\":\"beez3\",\"type\":\"template\",\"creationDate\":\"25 November 2009\",\"author\":\"Angie Radtke\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"a.radtke@derauftritt.de\",\"authorUrl\":\"http:\\/\\/www.der-auftritt.de\",\"version\":\"3.1.0\",\"description\":\"TPL_BEEZ3_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"templateDetails\"}','{\"wrapperSmall\":\"53\",\"wrapperLarge\":\"72\",\"sitetitle\":\"\",\"sitedescription\":\"\",\"navposition\":\"center\",\"templatecolor\":\"nature\"}','','',0,'0000-00-00 00:00:00',0,0),
	(504,'hathor','template','hathor','',1,1,1,0,'{\"name\":\"hathor\",\"type\":\"template\",\"creationDate\":\"May 2010\",\"author\":\"Andrea Tarr\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"\",\"version\":\"3.0.0\",\"description\":\"TPL_HATHOR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"templateDetails\"}','{\"showSiteName\":\"0\",\"colourChoice\":\"0\",\"boldText\":\"0\"}','','',0,'0000-00-00 00:00:00',0,0),
	(506,'protostar','template','protostar','',0,1,1,0,'{\"name\":\"protostar\",\"type\":\"template\",\"creationDate\":\"4\\/30\\/2012\",\"author\":\"Kyle Ledbetter\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"\",\"version\":\"1.0\",\"description\":\"TPL_PROTOSTAR_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"templateDetails\"}','{\"templateColor\":\"\",\"logoFile\":\"\",\"googleFont\":\"1\",\"googleFontName\":\"Open+Sans\",\"fluidContainer\":\"0\"}','','',0,'0000-00-00 00:00:00',0,0),
	(507,'isis','template','isis','',1,1,1,0,'{\"name\":\"isis\",\"type\":\"template\",\"creationDate\":\"3\\/30\\/2012\",\"author\":\"Kyle Ledbetter\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"\",\"version\":\"1.0\",\"description\":\"TPL_ISIS_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"templateDetails\"}','{\"templateColor\":\"\",\"logoFile\":\"\"}','','',0,'0000-00-00 00:00:00',0,0),
	(600,'English (en-GB)','language','en-GB','',0,1,1,1,'{\"name\":\"English (en-GB)\",\"type\":\"language\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.2\",\"description\":\"en-GB site language\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(601,'English (en-GB)','language','en-GB','',1,1,1,1,'{\"name\":\"English (en-GB)\",\"type\":\"language\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.2\",\"description\":\"en-GB administrator language\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(700,'files_joomla','file','joomla','',0,1,1,1,'{\"name\":\"files_joomla\",\"type\":\"file\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2016 Open Source Matters. All rights reserved\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.2\",\"description\":\"FILES_JOOMLA_XML_DESCRIPTION\",\"group\":\"\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(802,'English (en-GB) Language Pack','package','pkg_en-GB','',0,1,1,1,'{\"name\":\"English (en-GB) Language Pack\",\"type\":\"package\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.6.2.1\",\"description\":\"en-GB language pack\",\"group\":\"\",\"filename\":\"pkg_en-GB\"}','','','',0,'0000-00-00 00:00:00',0,0),
	(803,'joomla','template','joomla','',0,1,1,0,'{\"name\":\"joomla\",\"type\":\"template\",\"creationDate\":\"6\\/14\\/2013\",\"author\":\"Kyle Ledbetter\",\"copyright\":\"Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.\",\"authorEmail\":\"kyle@kyleledbetter.com\",\"authorUrl\":\"\",\"version\":\"2.0.0\",\"description\":\"TPL_JOOMLA_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"templateDetails\"}','{\"googleFont\":\"1\",\"googleFontName\":\"Open+Sans\",\"fluidContainer\":\"0\",\"leftColumnWidth\":\"3\",\"rightColumnWidth\":\"3\",\"twitterCardTitle\":\"Joomla.org\",\"twitterCardDescription\":\"The Platform Millions of Websites Are Built On\",\"searchModule\":\"search\"}','','',0,'0000-00-00 00:00:00',0,0),
	(805,'com_volunteers','component','com_volunteers','',1,1,1,0,'{\"name\":\"com_volunteers\",\"type\":\"component\",\"creationDate\":\"February 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"2.0.0\",\"description\":\"COM_VOLUNTEERS_XML_DESCRIPTION\",\"group\":\"\"}','{\"yourlsapikey\":\"123456789\",\"start_transition\":\"1\",\"new_structure\":\"1\"}','','',0,'0000-00-00 00:00:00',0,0),
	(806,'plg_user_volunteer','plugin','volunteer','user',0,1,1,0,'{\"name\":\"plg_user_volunteer\",\"type\":\"plugin\",\"creationDate\":\"August 2009\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2014 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"3.0.0\",\"description\":\"Delete Volunteer Profile on User Delete\",\"group\":\"\",\"filename\":\"volunteer\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(807,'Perfect Image Form Field','plugin','perfectimage','content',0,1,1,0,'{\"name\":\"Perfect Image Form Field\",\"type\":\"plugin\",\"creationDate\":\"March 2016\",\"author\":\"Perfect Web Team - Sander Potjer\",\"copyright\":\"Copyright (C) 2015 Perfect Web Team\",\"authorEmail\":\"info@perfectwebteam.nl\",\"authorUrl\":\"www.perfectwebteam.nl\",\"version\":\"1.0\",\"description\":\"Perfect Image Form Field\",\"group\":\"\",\"filename\":\"perfectimage\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(808,'JCE','component','com_jce','',1,1,0,0,'{\"name\":\"JCE\",\"type\":\"component\",\"creationDate\":\"18 August 2016\",\"author\":\"Ryan Demmer\",\"copyright\":\"Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved\",\"authorEmail\":\"info@joomlacontenteditor.net\",\"authorUrl\":\"www.joomlacontenteditor.net\",\"version\":\"2.5.24\",\"description\":\"WF_ADMIN_DESC\",\"group\":\"\",\"filename\":\"jce\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(809,'plg_editors_jce','plugin','jce','editors',0,1,1,0,'{\"name\":\"plg_editors_jce\",\"type\":\"plugin\",\"creationDate\":\"18 August 2016\",\"author\":\"Ryan Demmer\",\"copyright\":\"Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved\",\"authorEmail\":\"info@joomlacontenteditor.net\",\"authorUrl\":\"http:\\/\\/www.joomlacontenteditor.net\",\"version\":\"2.5.24\",\"description\":\"WF_EDITOR_PLUGIN_DESC\",\"group\":\"\",\"filename\":\"jce\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(810,'plg_system_jce','plugin','jce','system',0,1,1,0,'{\"name\":\"plg_system_jce\",\"type\":\"plugin\",\"creationDate\":\"18 August 2016\",\"author\":\"Ryan Demmer\",\"copyright\":\"Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved\",\"authorEmail\":\"info@joomlacontenteditor.net\",\"authorUrl\":\"http:\\/\\/www.joomlacontenteditor.net\",\"version\":\"2.5.24\",\"description\":\"PLG_SYSTEM_JCE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"jce\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(811,'plg_content_jce','plugin','jce','content',0,1,1,0,'{\"name\":\"plg_content_jce\",\"type\":\"plugin\",\"creationDate\":\"18 August 2016\",\"author\":\"Ryan Demmer\",\"copyright\":\"Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved\",\"authorEmail\":\"info@joomlacontenteditor.net\",\"authorUrl\":\"http:\\/\\/www.joomlacontenteditor.net\",\"version\":\"2.5.24\",\"description\":\"PLG_CONTENT_JCE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"jce\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(812,'plg_quickicon_jcefilebrowser','plugin','jcefilebrowser','quickicon',0,1,1,0,'{\"name\":\"plg_quickicon_jcefilebrowser\",\"type\":\"plugin\",\"creationDate\":\"18 August 2016\",\"author\":\"Ryan Demmer\",\"copyright\":\"Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved\",\"authorEmail\":\"@@email@@\",\"authorUrl\":\"www.joomalcontenteditor.net\",\"version\":\"2.5.24\",\"description\":\"PLG_QUICKICON_JCEFILEBROWSER_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"jcefilebrowser\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(813,'Authentication - Awo Email Login','plugin','awoelogin','authentication',0,1,1,0,'{\"name\":\"Authentication - Awo Email Login\",\"type\":\"plugin\",\"creationDate\":\"2013-12-23\",\"author\":\"Seyi Awofadeju\",\"copyright\":\"Copyright (C) 2010 Seyi Awofadeju - All rights reserved.\",\"authorEmail\":\"dev@awofadeju.com\",\"authorUrl\":\"http:\\/\\/dev.awofadeju.com\",\"version\":\"3.2.1\",\"description\":\"A user authentication through email\",\"group\":\"\",\"filename\":\"awoelogin\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(814,'PLG_NONSEFTOSEF','plugin','nonseftosef','system',0,1,1,0,'{\"name\":\"PLG_NONSEFTOSEF\",\"type\":\"plugin\",\"creationDate\":\"2016-06-30\",\"author\":\"Viktor Vogel\",\"copyright\":\"Copyright 2016 Viktor Vogel. All rights reserved.\",\"authorEmail\":\"\",\"authorUrl\":\"\",\"version\":\"3.1.2\",\"description\":\"PLG_NONSEFTOSEF_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"nonseftosef\"}','{\"exclude_components\":\"\",\"only_menu_items\":\"0\",\"donation_code\":\"\"}','','',0,'0000-00-00 00:00:00',0,0),
	(815,'plg_system_volunteers','plugin','volunteers','system',0,1,1,0,'{\"name\":\"plg_system_volunteers\",\"type\":\"plugin\",\"creationDate\":\"July 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Volunteers System Plugin\",\"group\":\"\",\"filename\":\"volunteers\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(817,'Report Spam Ajax Plugin','plugin','reportspam','ajax',0,1,1,0,'{\"name\":\"Report Spam Ajax Plugin\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Report Spam Ajax Plugin\",\"group\":\"\",\"filename\":\"reportspam\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(818,'Search - Volunteers - Volunteers','plugin','volunteers_volunteers','search',0,1,1,0,'{\"name\":\"Search - Volunteers - Volunteers\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Volunteers search for com_volunteers\",\"group\":\"\",\"filename\":\"volunteers_volunteers\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(819,'Search - Volunteers - Teams','plugin','volunteers_teams','search',0,1,1,0,'{\"name\":\"Search - Volunteers - Teams\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Teams search for com_volunteers\",\"group\":\"\",\"filename\":\"volunteers_teams\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(822,'Search - Volunteers - Departments','plugin','volunteers_departments','search',0,1,1,0,'{\"name\":\"Search - Volunteers - Departments\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Departments search for com_volunteers\",\"group\":\"\",\"filename\":\"volunteers_departments\"}','{}','','',0,'0000-00-00 00:00:00',0,0),
	(823,'Search - Volunteers - Reports','plugin','volunteers_reports','search',0,1,1,0,'{\"name\":\"Search - Volunteers - Reports\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Joomla! Project\",\"copyright\":\"(C) 2005 - 2015 Open Source Matters. All rights reserved.\",\"authorEmail\":\"admin@joomla.org\",\"authorUrl\":\"www.joomla.org\",\"version\":\"1.0.0\",\"description\":\"Reports search for com_volunteers\",\"group\":\"\",\"filename\":\"volunteers_reports\"}','{}','','',0,'0000-00-00 00:00:00',0,0);

/*!40000 ALTER TABLE `vol_extensions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_finder_filters
# ------------------------------------------------------------

CREATE TABLE `vol_finder_filters` (
  `filter_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL,
  `created_by_alias` varchar(255) NOT NULL,
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `map_count` int(10) unsigned NOT NULL DEFAULT '0',
  `data` mediumtext NOT NULL,
  `params` longtext,
  PRIMARY KEY (`filter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links` (
  `link_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `route` varchar(255) NOT NULL,
  `title` varchar(400) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `indexdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `md5sum` varchar(32) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '1',
  `state` int(5) DEFAULT '1',
  `access` int(5) DEFAULT '0',
  `language` varchar(8) NOT NULL,
  `publish_start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `list_price` double unsigned NOT NULL DEFAULT '0',
  `sale_price` double unsigned NOT NULL DEFAULT '0',
  `type_id` int(11) NOT NULL,
  `object` mediumblob NOT NULL,
  PRIMARY KEY (`link_id`),
  KEY `idx_type` (`type_id`),
  KEY `idx_md5` (`md5sum`),
  KEY `idx_url` (`url`(75)),
  KEY `idx_published_list` (`published`,`state`,`access`,`publish_start_date`,`publish_end_date`,`list_price`),
  KEY `idx_published_sale` (`published`,`state`,`access`,`publish_start_date`,`publish_end_date`,`sale_price`),
  KEY `idx_title` (`title`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms0
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms0` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms1
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms1` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms2
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms2` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms3
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms3` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms4
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms4` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms5
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms5` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms6
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms6` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms7
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms7` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms8
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms8` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_terms9
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_terms9` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termsa
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termsa` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termsb
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termsb` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termsc
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termsc` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termsd
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termsd` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termse
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termse` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_links_termsf
# ------------------------------------------------------------

CREATE TABLE `vol_finder_links_termsf` (
  `link_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`term_id`),
  KEY `idx_term_weight` (`term_id`,`weight`),
  KEY `idx_link_term_weight` (`link_id`,`term_id`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_taxonomy
# ------------------------------------------------------------

CREATE TABLE `vol_finder_taxonomy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `state` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `access` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ordering` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `state` (`state`),
  KEY `ordering` (`ordering`),
  KEY `access` (`access`),
  KEY `idx_parent_published` (`parent_id`,`state`,`access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `vol_finder_taxonomy` WRITE;
/*!40000 ALTER TABLE `vol_finder_taxonomy` DISABLE KEYS */;

INSERT INTO `vol_finder_taxonomy` (`id`, `parent_id`, `title`, `state`, `access`, `ordering`)
VALUES
	(1,0,'ROOT',0,0,0);

/*!40000 ALTER TABLE `vol_finder_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_finder_taxonomy_map
# ------------------------------------------------------------

CREATE TABLE `vol_finder_taxonomy_map` (
  `link_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`link_id`,`node_id`),
  KEY `link_id` (`link_id`),
  KEY `node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_terms
# ------------------------------------------------------------

CREATE TABLE `vol_finder_terms` (
  `term_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(75) NOT NULL,
  `stem` varchar(75) NOT NULL,
  `common` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `phrase` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `weight` float unsigned NOT NULL DEFAULT '0',
  `soundex` varchar(75) NOT NULL,
  `links` int(10) NOT NULL DEFAULT '0',
  `language` char(3) NOT NULL DEFAULT '',
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `idx_term` (`term`),
  KEY `idx_term_phrase` (`term`,`phrase`),
  KEY `idx_stem_phrase` (`stem`,`phrase`),
  KEY `idx_soundex_phrase` (`soundex`,`phrase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_terms_common
# ------------------------------------------------------------

CREATE TABLE `vol_finder_terms_common` (
  `term` varchar(75) NOT NULL,
  `language` varchar(3) NOT NULL,
  KEY `idx_word_lang` (`term`,`language`),
  KEY `idx_lang` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `vol_finder_terms_common` WRITE;
/*!40000 ALTER TABLE `vol_finder_terms_common` DISABLE KEYS */;

INSERT INTO `vol_finder_terms_common` (`term`, `language`)
VALUES
	('a','en'),
	('a','en'),
	('about','en'),
	('about','en'),
	('after','en'),
	('after','en'),
	('ago','en'),
	('ago','en'),
	('all','en'),
	('all','en'),
	('am','en'),
	('am','en'),
	('an','en'),
	('an','en'),
	('and','en'),
	('and','en'),
	('ani','en'),
	('ani','en'),
	('any','en'),
	('any','en'),
	('are','en'),
	('are','en'),
	('aren\'t','en'),
	('aren\'t','en'),
	('as','en'),
	('as','en'),
	('at','en'),
	('at','en'),
	('be','en'),
	('be','en'),
	('but','en'),
	('but','en'),
	('by','en'),
	('by','en'),
	('for','en'),
	('for','en'),
	('from','en'),
	('from','en'),
	('get','en'),
	('get','en'),
	('go','en'),
	('go','en'),
	('how','en'),
	('how','en'),
	('if','en'),
	('if','en'),
	('in','en'),
	('in','en'),
	('into','en'),
	('into','en'),
	('is','en'),
	('is','en'),
	('isn\'t','en'),
	('isn\'t','en'),
	('it','en'),
	('it','en'),
	('its','en'),
	('its','en'),
	('me','en'),
	('me','en'),
	('more','en'),
	('more','en'),
	('most','en'),
	('most','en'),
	('must','en'),
	('must','en'),
	('my','en'),
	('my','en'),
	('new','en'),
	('new','en'),
	('no','en'),
	('no','en'),
	('none','en'),
	('none','en'),
	('not','en'),
	('not','en'),
	('noth','en'),
	('noth','en'),
	('nothing','en'),
	('nothing','en'),
	('of','en'),
	('of','en'),
	('off','en'),
	('off','en'),
	('often','en'),
	('often','en'),
	('old','en'),
	('old','en'),
	('on','en'),
	('on','en'),
	('onc','en'),
	('onc','en'),
	('once','en'),
	('once','en'),
	('onli','en'),
	('onli','en'),
	('only','en'),
	('only','en'),
	('or','en'),
	('or','en'),
	('other','en'),
	('other','en'),
	('our','en'),
	('our','en'),
	('ours','en'),
	('ours','en'),
	('out','en'),
	('out','en'),
	('over','en'),
	('over','en'),
	('page','en'),
	('page','en'),
	('she','en'),
	('she','en'),
	('should','en'),
	('should','en'),
	('small','en'),
	('small','en'),
	('so','en'),
	('so','en'),
	('some','en'),
	('some','en'),
	('than','en'),
	('than','en'),
	('thank','en'),
	('thank','en'),
	('that','en'),
	('that','en'),
	('the','en'),
	('the','en'),
	('their','en'),
	('their','en'),
	('theirs','en'),
	('theirs','en'),
	('them','en'),
	('them','en'),
	('then','en'),
	('then','en'),
	('there','en'),
	('there','en'),
	('these','en'),
	('these','en'),
	('they','en'),
	('they','en'),
	('this','en'),
	('this','en'),
	('those','en'),
	('those','en'),
	('thus','en'),
	('thus','en'),
	('time','en'),
	('time','en'),
	('times','en'),
	('times','en'),
	('to','en'),
	('to','en'),
	('too','en'),
	('too','en'),
	('true','en'),
	('true','en'),
	('under','en'),
	('under','en'),
	('until','en'),
	('until','en'),
	('up','en'),
	('up','en'),
	('upon','en'),
	('upon','en'),
	('use','en'),
	('use','en'),
	('user','en'),
	('user','en'),
	('users','en'),
	('users','en'),
	('veri','en'),
	('veri','en'),
	('version','en'),
	('version','en'),
	('very','en'),
	('very','en'),
	('via','en'),
	('via','en'),
	('want','en'),
	('want','en'),
	('was','en'),
	('was','en'),
	('way','en'),
	('way','en'),
	('were','en'),
	('were','en'),
	('what','en'),
	('what','en'),
	('when','en'),
	('when','en'),
	('where','en'),
	('where','en'),
	('whi','en'),
	('whi','en'),
	('which','en'),
	('which','en'),
	('who','en'),
	('who','en'),
	('whom','en'),
	('whom','en'),
	('whose','en'),
	('whose','en'),
	('why','en'),
	('why','en'),
	('wide','en'),
	('wide','en'),
	('will','en'),
	('will','en'),
	('with','en'),
	('with','en'),
	('within','en'),
	('within','en'),
	('without','en'),
	('without','en'),
	('would','en'),
	('would','en'),
	('yes','en'),
	('yes','en'),
	('yet','en'),
	('yet','en'),
	('you','en'),
	('you','en'),
	('your','en'),
	('your','en'),
	('yours','en'),
	('yours','en');

/*!40000 ALTER TABLE `vol_finder_terms_common` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_finder_tokens
# ------------------------------------------------------------

CREATE TABLE `vol_finder_tokens` (
  `term` varchar(75) NOT NULL,
  `stem` varchar(75) NOT NULL,
  `common` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `phrase` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `weight` float unsigned NOT NULL DEFAULT '1',
  `context` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `language` char(3) NOT NULL DEFAULT '',
  KEY `idx_word` (`term`),
  KEY `idx_context` (`context`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_tokens_aggregate
# ------------------------------------------------------------

CREATE TABLE `vol_finder_tokens_aggregate` (
  `term_id` int(10) unsigned NOT NULL,
  `map_suffix` char(1) NOT NULL,
  `term` varchar(75) NOT NULL,
  `stem` varchar(75) NOT NULL,
  `common` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `phrase` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `term_weight` float unsigned NOT NULL,
  `context` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `context_weight` float unsigned NOT NULL,
  `total_weight` float unsigned NOT NULL,
  `language` char(3) NOT NULL DEFAULT '',
  KEY `token` (`term`),
  KEY `keyword_id` (`term_id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;



# Dump of table vol_finder_types
# ------------------------------------------------------------

CREATE TABLE `vol_finder_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `mime` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table vol_languages
# ------------------------------------------------------------

CREATE TABLE `vol_languages` (
  `lang_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `lang_code` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_native` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sef` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sitename` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` int(11) NOT NULL DEFAULT '0',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lang_id`),
  UNIQUE KEY `idx_sef` (`sef`),
  UNIQUE KEY `idx_image` (`image`),
  UNIQUE KEY `idx_langcode` (`lang_code`),
  KEY `idx_access` (`access`),
  KEY `idx_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_languages` WRITE;
/*!40000 ALTER TABLE `vol_languages` DISABLE KEYS */;

INSERT INTO `vol_languages` (`lang_id`, `asset_id`, `lang_code`, `title`, `title_native`, `sef`, `image`, `description`, `metakey`, `metadesc`, `sitename`, `published`, `access`, `ordering`)
VALUES
	(1,0,X'656E2D4742','English (UK)','English (UK)','en','en','','','','',1,1,1);

/*!40000 ALTER TABLE `vol_languages` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_menu
# ------------------------------------------------------------

CREATE TABLE `vol_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menutype` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The type of menu this item belongs to. FK to #__menu_types.menutype',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The display title of the menu item.',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'The SEF alias of the menu item.',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `path` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The computed path of the menu item based on the alias field.',
  `link` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The actually link the menu item refers to.',
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The type of link: Component, URL, Alias, Separator',
  `published` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'The published state of the menu link.',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'The parent menu item in the menu tree.',
  `level` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The relative level in the tree.',
  `component_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to #__extensions.id',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to #__users.id',
  `checked_out_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The time the menu item was checked out.',
  `browserNav` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'The click behaviour of the link.',
  `access` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The access level required to view the menu item.',
  `img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The image of the menu item.',
  `template_style_id` int(10) unsigned NOT NULL DEFAULT '0',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded data for the menu item.',
  `lft` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set lft.',
  `rgt` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set rgt.',
  `home` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Indicates if this menu item is the home or default page.',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `client_id` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_client_id_parent_id_alias_language` (`client_id`,`parent_id`,`alias`(100),`language`),
  KEY `idx_componentid` (`component_id`,`menutype`,`published`,`access`),
  KEY `idx_menutype` (`menutype`),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_language` (`language`),
  KEY `idx_alias` (`alias`(100)),
  KEY `idx_path` (`path`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_menu` WRITE;
/*!40000 ALTER TABLE `vol_menu` DISABLE KEYS */;

INSERT INTO `vol_menu` (`id`, `menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `level`, `component_id`, `checked_out`, `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `lft`, `rgt`, `home`, `language`, `client_id`)
VALUES
	(1,'','Menu_Item_Root',X'726F6F74','','','','',1,0,0,0,0,'0000-00-00 00:00:00',0,0,'',0,'',0,89,0,'*',0),
	(2,'menu','com_banners',X'42616E6E657273','','Banners','index.php?option=com_banners','component',0,1,1,4,0,'0000-00-00 00:00:00',0,0,'class:banners',0,'',1,10,0,'*',1),
	(3,'menu','com_banners',X'42616E6E657273','','Banners/Banners','index.php?option=com_banners','component',0,2,2,4,0,'0000-00-00 00:00:00',0,0,'class:banners',0,'',2,3,0,'*',1),
	(4,'menu','com_banners_categories',X'43617465676F72696573','','Banners/Categories','index.php?option=com_categories&extension=com_banners','component',0,2,2,6,0,'0000-00-00 00:00:00',0,0,'class:banners-cat',0,'',4,5,0,'*',1),
	(5,'menu','com_banners_clients',X'436C69656E7473','','Banners/Clients','index.php?option=com_banners&view=clients','component',0,2,2,4,0,'0000-00-00 00:00:00',0,0,'class:banners-clients',0,'',6,7,0,'*',1),
	(6,'menu','com_banners_tracks',X'547261636B73','','Banners/Tracks','index.php?option=com_banners&view=tracks','component',0,2,2,4,0,'0000-00-00 00:00:00',0,0,'class:banners-tracks',0,'',8,9,0,'*',1),
	(7,'menu','com_contact',X'436F6E7461637473','','Contacts','index.php?option=com_contact','component',0,1,1,8,0,'0000-00-00 00:00:00',0,0,'class:contact',0,'',27,32,0,'*',1),
	(8,'menu','com_contact_contacts',X'436F6E7461637473','','Contacts/Contacts','index.php?option=com_contact','component',0,7,2,8,0,'0000-00-00 00:00:00',0,0,'class:contact',0,'',28,29,0,'*',1),
	(9,'menu','com_contact_categories',X'43617465676F72696573','','Contacts/Categories','index.php?option=com_categories&extension=com_contact','component',0,7,2,6,0,'0000-00-00 00:00:00',0,0,'class:contact-cat',0,'',30,31,0,'*',1),
	(10,'menu','com_messages',X'4D6573736167696E67','','Messaging','index.php?option=com_messages','component',0,1,1,15,0,'0000-00-00 00:00:00',0,0,'class:messages',0,'',33,36,0,'*',1),
	(11,'menu','com_messages_add',X'4E65772050726976617465204D657373616765','','Messaging/New Private Message','index.php?option=com_messages&task=message.add','component',0,10,2,15,0,'0000-00-00 00:00:00',0,0,'class:messages-add',0,'',34,35,0,'*',1),
	(13,'menu','com_newsfeeds',X'4E657773204665656473','','News Feeds','index.php?option=com_newsfeeds','component',0,1,1,17,0,'0000-00-00 00:00:00',0,0,'class:newsfeeds',0,'',37,42,0,'*',1),
	(14,'menu','com_newsfeeds_feeds',X'4665656473','','News Feeds/Feeds','index.php?option=com_newsfeeds','component',0,13,2,17,0,'0000-00-00 00:00:00',0,0,'class:newsfeeds',0,'',38,39,0,'*',1),
	(15,'menu','com_newsfeeds_categories',X'43617465676F72696573','','News Feeds/Categories','index.php?option=com_categories&extension=com_newsfeeds','component',0,13,2,6,0,'0000-00-00 00:00:00',0,0,'class:newsfeeds-cat',0,'',40,41,0,'*',1),
	(16,'menu','com_redirect',X'5265646972656374','','Redirect','index.php?option=com_redirect','component',0,1,1,24,0,'0000-00-00 00:00:00',0,0,'class:redirect',0,'',43,44,0,'*',1),
	(17,'menu','com_search',X'426173696320536561726368','','Basic Search','index.php?option=com_search','component',0,1,1,19,0,'0000-00-00 00:00:00',0,0,'class:search',0,'',45,46,0,'*',1),
	(21,'menu','com_finder',X'536D61727420536561726368','','Smart Search','index.php?option=com_finder','component',0,1,1,27,0,'0000-00-00 00:00:00',0,0,'class:finder',0,'',47,48,0,'*',1),
	(22,'menu','com_joomlaupdate',X'4A6F6F6D6C612120557064617465','','Joomla! Update','index.php?option=com_joomlaupdate','component',1,1,1,28,0,'0000-00-00 00:00:00',0,0,'class:joomlaupdate',0,'',49,50,0,'*',1),
	(23,'main','com_tags',X'54616773','','Tags','index.php?option=com_tags','component',0,1,1,29,0,'0000-00-00 00:00:00',0,1,'class:tags',0,'',51,52,0,'',1),
	(24,'main','com_postinstall',X'506F73742D696E7374616C6C6174696F6E206D65737361676573','','Post-installation messages','index.php?option=com_postinstall','component',0,1,1,32,0,'0000-00-00 00:00:00',0,1,'class:postinstall',0,'',53,54,0,'*',1),
	(101,'mainmenu','Home',X'686F6D65','','home','index.php?option=com_volunteers&view=home','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"0\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',11,12,1,'*',0),
	(106,'mainmenu','Teams',X'7465616D73','','teams','index.php?option=com_volunteers&view=teams','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"0\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',15,16,0,'*',0),
	(107,'mainmenu','Joomlers',X'6A6F6F6D6C657273','','joomlers','index.php?option=com_volunteers&view=volunteers','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,'',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"page_title\":\"\",\"show_page_heading\":0,\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',17,18,0,'*',0),
	(108,'mainmenu','Reports',X'7265706F727473','','reports','index.php?option=com_volunteers&view=reports','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,'',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"page_title\":\"\",\"show_page_heading\":0,\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',19,20,0,'*',0),
	(109,'mainmenu','My Profile',X'6D792D70726F66696C65','','my-profile','index.php?option=com_volunteers&view=my','component',1,1,1,805,0,'0000-00-00 00:00:00',0,2,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"0\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',25,26,0,'*',0),
	(110,'hiddenmenu','Register',X'7265676973746572','','register','index.php?option=com_volunteers&view=registration','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"0\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',55,56,0,'*',0),
	(113,'mainmenu','FAQ',X'666171','','faq','index.php?option=com_content&view=article&id=2','component',1,1,1,22,0,'0000-00-00 00:00:00',0,1,'',0,'{\"show_title\":\"\",\"link_titles\":\"\",\"show_intro\":\"\",\"info_block_position\":\"\",\"show_category\":\"\",\"link_category\":\"\",\"show_parent_category\":\"\",\"link_parent_category\":\"\",\"show_author\":\"\",\"link_author\":\"\",\"show_create_date\":\"\",\"show_modify_date\":\"\",\"show_publish_date\":\"\",\"show_item_navigation\":\"\",\"show_vote\":\"\",\"show_icons\":\"\",\"show_print_icon\":\"\",\"show_email_icon\":\"\",\"show_hits\":\"\",\"show_tags\":\"\",\"show_noauth\":\"\",\"urls_position\":\"\",\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"page_title\":\"\",\"show_page_heading\":0,\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',23,24,0,'*',0),
	(119,'hiddenmenu','Login',X'6C6F67696E','','login','index.php?option=com_users&view=login','component',1,1,1,25,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"loginredirectchoice\":\"1\",\"login_redirect_url\":\"\",\"login_redirect_menuitem\":\"109\",\"logindescription_show\":\"1\",\"login_description\":\"\",\"login_image\":\"\",\"logoutredirectchoice\":\"1\",\"logout_redirect_url\":\"\",\"logout_redirect_menuitem\":\"101\",\"logoutdescription_show\":\"1\",\"logout_description\":\"\",\"logout_image\":\"\",\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"0\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',57,58,0,'*',0),
	(130,'mainmenu','Banners',X'62616E6E657273','','banners','index.php?option=com_content&view=article&id=3','component',1,1,1,22,0,'0000-00-00 00:00:00',0,1,'',0,'{\"show_title\":\"\",\"link_titles\":\"\",\"show_intro\":\"\",\"info_block_position\":\"\",\"show_category\":\"\",\"link_category\":\"\",\"show_parent_category\":\"\",\"link_parent_category\":\"\",\"show_author\":\"\",\"link_author\":\"\",\"show_create_date\":\"\",\"show_modify_date\":\"\",\"show_publish_date\":\"\",\"show_item_navigation\":\"\",\"show_vote\":\"\",\"show_icons\":\"\",\"show_print_icon\":\"\",\"show_email_icon\":\"\",\"show_hits\":\"\",\"show_tags\":\"\",\"show_noauth\":\"\",\"urls_position\":\"\",\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"page_title\":\"\",\"show_page_heading\":0,\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',21,22,0,'*',0),
	(175,'hiddenmenu','Password Reset',X'70617373776F72642D7265736574','','password-reset','index.php?option=com_users&view=reset','component',1,1,1,25,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"1\",\"page_heading\":\"Password Reset\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',59,60,0,'*',0),
	(176,'mainmenu','Departments',X'6465706172746D656E7473','','departments','index.php?option=com_volunteers&view=departments','component',1,1,1,805,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',13,14,0,'*',0),
	(183,'main','com_volunteers',X'636F6D2D766F6C756E7465657273','','com-volunteers','index.php?option=com_volunteers','component',0,1,1,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',61,76,0,'',1),
	(184,'main','JCE',X'6A6365','','jce','index.php?option=com_jce','component',0,1,1,808,0,'0000-00-00 00:00:00',0,1,'components/com_jce/media/img/menu/logo.png',0,'{}',77,86,0,'',1),
	(185,'main','WF_MENU_CPANEL',X'77662D6D656E752D6370616E656C','','jce/wf-menu-cpanel','index.php?option=com_jce','component',0,184,2,808,0,'0000-00-00 00:00:00',0,1,'components/com_jce/media/img/menu/jce-cpanel.png',0,'{}',78,79,0,'',1),
	(186,'main','WF_MENU_CONFIG',X'77662D6D656E752D636F6E666967','','jce/wf-menu-config','index.php?option=com_jce&view=config','component',0,184,2,808,0,'0000-00-00 00:00:00',0,1,'components/com_jce/media/img/menu/jce-config.png',0,'{}',80,81,0,'',1),
	(187,'main','WF_MENU_PROFILES',X'77662D6D656E752D70726F66696C6573','','jce/wf-menu-profiles','index.php?option=com_jce&view=profiles','component',0,184,2,808,0,'0000-00-00 00:00:00',0,1,'components/com_jce/media/img/menu/jce-profiles.png',0,'{}',82,83,0,'',1),
	(188,'main','WF_MENU_INSTALL',X'77662D6D656E752D696E7374616C6C','','jce/wf-menu-install','index.php?option=com_jce&view=installer','component',0,184,2,808,0,'0000-00-00 00:00:00',0,1,'components/com_jce/media/img/menu/jce-install.png',0,'{}',84,85,0,'',1),
	(190,'main','COM_VOLUNTEERS_TITLE_VOLUNTEERS',X'636F6D2D766F6C756E74656572732D766F6C756E7465657273','','com-volunteers/com-volunteers-volunteers','index.php?option=com_volunteers&view=volunteers','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',62,63,0,'',1),
	(191,'main','COM_VOLUNTEERS_TITLE_TEAMS',X'636F6D2D766F6C756E74656572732D7465616D73','','com-volunteers/com-volunteers-teams','index.php?option=com_volunteers&view=teams','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',64,65,0,'',1),
	(192,'main','COM_VOLUNTEERS_TITLE_ROLES',X'636F6D2D766F6C756E74656572732D726F6C6573','','com-volunteers/com-volunteers-roles','index.php?option=com_volunteers&view=roles','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',66,67,0,'',1),
	(193,'main','COM_VOLUNTEERS_TITLE_MEMBERS',X'636F6D2D766F6C756E74656572732D6D656D62657273','','com-volunteers/com-volunteers-members','index.php?option=com_volunteers&view=members','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',68,69,0,'',1),
	(194,'main','COM_VOLUNTEERS_TITLE_REPORTS',X'636F6D2D766F6C756E74656572732D7265706F727473','','com-volunteers/com-volunteers-reports','index.php?option=com_volunteers&view=reports','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',70,71,0,'',1),
	(195,'main','COM_VOLUNTEERS_TITLE_DEPARTMENTS',X'636F6D2D766F6C756E74656572732D6465706172746D656E7473','','com-volunteers/com-volunteers-departments','index.php?option=com_volunteers&view=departments','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',72,73,0,'',1),
	(196,'main','COM_VOLUNTEERS_TITLE_POSITIONS',X'636F6D2D766F6C756E74656572732D706F736974696F6E73','','com-volunteers/com-volunteers-positions','index.php?option=com_volunteers&view=positions','component',0,183,2,805,0,'0000-00-00 00:00:00',0,1,'class:volunteers',0,'{}',74,75,0,'',1),
	(197,'hiddenmenu','Search',X'736561726368','','search','index.php?option=com_search&view=search','component',1,1,1,19,0,'0000-00-00 00:00:00',0,1,' ',0,'{\"search_phrases\":\"0\",\"search_areas\":\"\",\"show_date\":\"\",\"searchphrase\":\"0\",\"ordering\":\"newest\",\"menu-anchor_title\":\"\",\"menu-anchor_css\":\"\",\"menu_image\":\"\",\"menu_text\":1,\"menu_show\":1,\"page_title\":\"\",\"show_page_heading\":\"\",\"page_heading\":\"\",\"pageclass_sfx\":\"\",\"menu-meta_description\":\"\",\"menu-meta_keywords\":\"\",\"robots\":\"\",\"secure\":0}',87,88,0,'*',0);

/*!40000 ALTER TABLE `vol_menu` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_menu_types
# ------------------------------------------------------------

CREATE TABLE `vol_menu_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `menutype` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_menutype` (`menutype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_menu_types` WRITE;
/*!40000 ALTER TABLE `vol_menu_types` DISABLE KEYS */;

INSERT INTO `vol_menu_types` (`id`, `asset_id`, `menutype`, `title`, `description`)
VALUES
	(1,0,'mainmenu','Main Menu','The main menu for the site'),
	(2,0,'hiddenmenu','Hidden Menu','');

/*!40000 ALTER TABLE `vol_menu_types` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_messages
# ------------------------------------------------------------

CREATE TABLE `vol_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id_from` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id_to` int(10) unsigned NOT NULL DEFAULT '0',
  `folder_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `useridto_state` (`user_id_to`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_messages_cfg
# ------------------------------------------------------------

CREATE TABLE `vol_messages_cfg` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cfg_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cfg_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY `idx_user_var_name` (`user_id`,`cfg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_modules
# ------------------------------------------------------------

CREATE TABLE `vol_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `showtitle` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` tinyint(4) NOT NULL DEFAULT '0',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `published` (`published`,`access`),
  KEY `newsfeeds` (`module`,`published`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_modules` WRITE;
/*!40000 ALTER TABLE `vol_modules` DISABLE KEYS */;

INSERT INTO `vol_modules` (`id`, `asset_id`, `title`, `note`, `content`, `ordering`, `position`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `published`, `module`, `access`, `showtitle`, `params`, `client_id`, `language`)
VALUES
	(1,39,'Main Menu','','',1,'position-1',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_menu',1,0,'{\"menutype\":\"mainmenu\",\"base\":\"\",\"startLevel\":\"1\",\"endLevel\":\"0\",\"showAllChildren\":\"0\",\"tag_id\":\"\",\"class_sfx\":\" nav-pills\",\"window_open\":\"\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"1\",\"cache_time\":\"900\",\"cachemode\":\"itemid\",\"module_tag\":\"div\",\"bootstrap_size\":\"0\",\"header_tag\":\"h3\",\"header_class\":\"\",\"style\":\"0\"}',0,'*'),
	(2,40,'Login','','',1,'login',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_login',1,1,'',1,'*'),
	(3,41,'Popular Articles','','',3,'cpanel',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_popular',3,1,'{\"count\":\"5\",\"catid\":\"\",\"user_id\":\"0\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\",\"automatic_title\":\"1\"}',1,'*'),
	(4,42,'Recently Added Articles','','',4,'cpanel',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_latest',3,1,'{\"count\":\"5\",\"ordering\":\"c_dsc\",\"catid\":\"\",\"user_id\":\"0\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\",\"automatic_title\":\"1\"}',1,'*'),
	(8,43,'Toolbar','','',1,'toolbar',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_toolbar',3,1,'',1,'*'),
	(9,44,'Quick Icons','','',1,'icon',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_quickicon',3,1,'',1,'*'),
	(10,45,'Logged-in Users','','',2,'cpanel',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_logged',3,1,'{\"count\":\"5\",\"name\":\"1\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\",\"automatic_title\":\"1\"}',1,'*'),
	(12,46,'Admin Menu','','',1,'menu',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_menu',3,1,'{\"layout\":\"\",\"moduleclass_sfx\":\"\",\"shownew\":\"1\",\"showhelp\":\"1\",\"cache\":\"0\"}',1,'*'),
	(13,47,'Admin Submenu','','',1,'submenu',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_submenu',3,1,'',1,'*'),
	(14,48,'User Status','','',2,'status',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_status',3,1,'',1,'*'),
	(15,49,'Title','','',1,'title',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_title',3,1,'',1,'*'),
	(16,50,'Login Form','','',1,'position-7',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_login',1,1,'{\"pretext\":\"\",\"posttext\":\"\",\"login\":\"109\",\"logout\":\"101\",\"greeting\":\"1\",\"name\":\"0\",\"usesecure\":\"0\",\"usetext\":\"0\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\",\"module_tag\":\"div\",\"bootstrap_size\":\"0\",\"header_tag\":\"h3\",\"header_class\":\"\",\"style\":\"0\"}',0,'*'),
	(17,51,'Breadcrumbs','','',1,'position-2',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_breadcrumbs',1,1,'{\"moduleclass_sfx\":\"\",\"showHome\":\"1\",\"homeText\":\"\",\"showComponent\":\"1\",\"separator\":\"\",\"cache\":\"1\",\"cache_time\":\"900\",\"cachemode\":\"itemid\"}',0,'*'),
	(79,52,'Multilanguage status','','',1,'status',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',0,'mod_multilangstatus',3,1,'{\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\"}',1,'*'),
	(89,65,'Joomla Version','','',1,'footer',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_version',3,1,'{\"format\":\"short\",\"product\":\"1\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"0\"}',1,'*'),
	(90,66,'Search','','',0,'position-0',0,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'mod_search',1,1,'{\"label\":\"\",\"width\":\"\",\"text\":\"\",\"button\":\"0\",\"button_pos\":\"left\",\"imagebutton\":\"0\",\"button_text\":\"\",\"opensearch\":\"1\",\"opensearch_title\":\"\",\"set_itemid\":\"0\",\"layout\":\"_:default\",\"moduleclass_sfx\":\"\",\"cache\":\"1\",\"cache_time\":\"900\",\"cachemode\":\"itemid\",\"module_tag\":\"div\",\"bootstrap_size\":\"0\",\"header_tag\":\"h3\",\"header_class\":\"\",\"style\":\"0\"}',0,'*');

/*!40000 ALTER TABLE `vol_modules` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_modules_menu
# ------------------------------------------------------------

CREATE TABLE `vol_modules_menu` (
  `moduleid` int(11) NOT NULL DEFAULT '0',
  `menuid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`moduleid`,`menuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_modules_menu` WRITE;
/*!40000 ALTER TABLE `vol_modules_menu` DISABLE KEYS */;

INSERT INTO `vol_modules_menu` (`moduleid`, `menuid`)
VALUES
	(1,0),
	(2,0),
	(3,0),
	(4,0),
	(8,0),
	(9,0),
	(10,0),
	(12,0),
	(13,0),
	(14,0),
	(15,0),
	(16,0),
	(17,0),
	(79,0),
	(89,0),
	(90,0);

/*!40000 ALTER TABLE `vol_modules_menu` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_newsfeeds
# ------------------------------------------------------------

CREATE TABLE `vol_newsfeeds` (
  `catid` int(11) NOT NULL DEFAULT '0',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `link` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `numarticles` int(10) unsigned NOT NULL DEFAULT '1',
  `cache_time` int(10) unsigned NOT NULL DEFAULT '3600',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `rtl` tinyint(4) NOT NULL DEFAULT '0',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `xreference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A reference to enable linkages to external data sets.',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `images` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`published`),
  KEY `idx_catid` (`catid`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_overrider
# ------------------------------------------------------------

CREATE TABLE `vol_overrider` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `constant` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `string` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_postinstall_messages
# ------------------------------------------------------------

CREATE TABLE `vol_postinstall_messages` (
  `postinstall_message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `extension_id` bigint(20) NOT NULL DEFAULT '700' COMMENT 'FK to #__extensions',
  `title_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Lang key for the title',
  `description_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Lang key for description',
  `action_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `language_extension` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'com_postinstall' COMMENT 'Extension holding lang keys',
  `language_client_id` tinyint(3) NOT NULL DEFAULT '1',
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link' COMMENT 'Message type - message, link, action',
  `action_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'RAD URI to the PHP file containing action method',
  `action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Action method name or URL',
  `condition_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RAD URI to file holding display condition method',
  `condition_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Display condition method, must return boolean',
  `version_introduced` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '3.2.0' COMMENT 'Version when this message was introduced',
  `enabled` tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`postinstall_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_postinstall_messages` WRITE;
/*!40000 ALTER TABLE `vol_postinstall_messages` DISABLE KEYS */;

INSERT INTO `vol_postinstall_messages` (`postinstall_message_id`, `extension_id`, `title_key`, `description_key`, `action_key`, `language_extension`, `language_client_id`, `type`, `action_file`, `action`, `condition_file`, `condition_method`, `version_introduced`, `enabled`)
VALUES
	(1,700,'PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_TITLE','PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_BODY','PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_ACTION','plg_twofactorauth_totp',1,'action','site://plugins/twofactorauth/totp/postinstall/actions.php','twofactorauth_postinstall_action','site://plugins/twofactorauth/totp/postinstall/actions.php','twofactorauth_postinstall_condition','3.2.0',0),
	(2,700,'COM_CPANEL_MSG_EACCELERATOR_TITLE','COM_CPANEL_MSG_EACCELERATOR_BODY','COM_CPANEL_MSG_EACCELERATOR_BUTTON','com_cpanel',1,'action','admin://components/com_admin/postinstall/eaccelerator.php','admin_postinstall_eaccelerator_action','admin://components/com_admin/postinstall/eaccelerator.php','admin_postinstall_eaccelerator_condition','3.2.0',1),
	(3,700,'COM_CPANEL_WELCOME_BEGINNERS_TITLE','COM_CPANEL_WELCOME_BEGINNERS_MESSAGE','','com_cpanel',1,'message','','','','','3.2.0',0),
	(4,700,'COM_CPANEL_MSG_PHPVERSION_TITLE','COM_CPANEL_MSG_PHPVERSION_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/phpversion.php','admin_postinstall_phpversion_condition','3.2.2',1),
	(10,700,'COM_CPANEL_MSG_HTACCESS_TITLE','COM_CPANEL_MSG_HTACCESS_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/htaccess.php','admin_postinstall_htaccess_condition','3.4.0',0),
	(11,700,'COM_CPANEL_MSG_ROBOTS_TITLE','COM_CPANEL_MSG_ROBOTS_BODY','','com_cpanel',1,'message','','','','','3.3.0',0),
	(12,700,'COM_CPANEL_MSG_LANGUAGEACCESS340_TITLE','COM_CPANEL_MSG_LANGUAGEACCESS340_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/languageaccess340.php','admin_postinstall_languageaccess340_condition','3.4.1',1),
	(13,700,'COM_CPANEL_MSG_STATS_COLLECTION_TITLE','COM_CPANEL_MSG_STATS_COLLECTION_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/statscollection.php','admin_postinstall_statscollection_condition','3.5.0',0),
	(14,700,'PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_TITLE','PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_BODY','PLG_TWOFACTORAUTH_TOTP_POSTINSTALL_ACTION','plg_twofactorauth_totp',1,'action','site://plugins/twofactorauth/totp/postinstall/actions.php','twofactorauth_postinstall_action','site://plugins/twofactorauth/totp/postinstall/actions.php','twofactorauth_postinstall_condition','3.2.0',0),
	(15,700,'COM_CPANEL_MSG_EACCELERATOR_TITLE','COM_CPANEL_MSG_EACCELERATOR_BODY','COM_CPANEL_MSG_EACCELERATOR_BUTTON','com_cpanel',1,'action','admin://components/com_admin/postinstall/eaccelerator.php','admin_postinstall_eaccelerator_action','admin://components/com_admin/postinstall/eaccelerator.php','admin_postinstall_eaccelerator_condition','3.2.0',1),
	(16,700,'COM_CPANEL_MSG_PHPVERSION_TITLE','COM_CPANEL_MSG_PHPVERSION_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/phpversion.php','admin_postinstall_phpversion_condition','3.2.2',1),
	(17,700,'COM_CPANEL_MSG_HTACCESS_TITLE','COM_CPANEL_MSG_HTACCESS_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/htaccess.php','admin_postinstall_htaccess_condition','3.4.0',0),
	(18,700,'COM_CPANEL_MSG_ROBOTS_TITLE','COM_CPANEL_MSG_ROBOTS_BODY','','com_cpanel',1,'message','','','','','3.3.0',0),
	(19,700,'COM_CPANEL_MSG_LANGUAGEACCESS340_TITLE','COM_CPANEL_MSG_LANGUAGEACCESS340_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/languageaccess340.php','admin_postinstall_languageaccess340_condition','3.4.1',1),
	(20,700,'COM_CPANEL_MSG_STATS_COLLECTION_TITLE','COM_CPANEL_MSG_STATS_COLLECTION_BODY','','com_cpanel',1,'message','','','admin://components/com_admin/postinstall/statscollection.php','admin_postinstall_statscollection_condition','3.5.0',0);

/*!40000 ALTER TABLE `vol_postinstall_messages` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_redirect_links
# ------------------------------------------------------------

CREATE TABLE `vol_redirect_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_url` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `published` tinyint(4) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `header` smallint(3) NOT NULL DEFAULT '301',
  PRIMARY KEY (`id`),
  KEY `idx_link_modifed` (`modified_date`),
  KEY `idx_old_url` (`old_url`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_schemas
# ------------------------------------------------------------

CREATE TABLE `vol_schemas` (
  `extension_id` int(11) NOT NULL,
  `version_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`extension_id`,`version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_schemas` WRITE;
/*!40000 ALTER TABLE `vol_schemas` DISABLE KEYS */;

INSERT INTO `vol_schemas` (`extension_id`, `version_id`)
VALUES
	(700,'3.6.0-2016-06-05');

/*!40000 ALTER TABLE `vol_schemas` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_session
# ------------------------------------------------------------

CREATE TABLE `vol_session` (
  `session_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `client_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `guest` tinyint(4) unsigned DEFAULT '1',
  `time` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `data` longtext COLLATE utf8mb4_unicode_ci,
  `userid` int(11) DEFAULT '0',
  `username` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`session_id`),
  KEY `userid` (`userid`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_session` WRITE;
/*!40000 ALTER TABLE `vol_session` DISABLE KEYS */;

INSERT INTO `vol_session` (`session_id`, `client_id`, `guest`, `time`, `data`, `userid`, `username`)
VALUES
	('4334365d445081c67780e6908e9a129f',0,0,'1471977541','joomla|s:1172:\"TzoyNDoiSm9vbWxhXFJlZ2lzdHJ5XFJlZ2lzdHJ5IjoyOntzOjc6IgAqAGRhdGEiO086ODoic3RkQ2xhc3MiOjE6e3M6OToiX19kZWZhdWx0IjtPOjg6InN0ZENsYXNzIjo0OntzOjc6InNlc3Npb24iO086ODoic3RkQ2xhc3MiOjM6e3M6NzoiY291bnRlciI7aTozNztzOjU6InRpbWVyIjtPOjg6InN0ZENsYXNzIjozOntzOjU6InN0YXJ0IjtpOjE0NzE5NzU2NTk7czo0OiJsYXN0IjtpOjE0NzE5Nzc1Mzg7czozOiJub3ciO2k6MTQ3MTk3NzUzOTt9czo1OiJ0b2tlbiI7czozMjoiSEVCZHhiU2g3U3psUlE0ZnZmcGZhWUtWcmNDMGxxN2YiO31zOjg6InJlZ2lzdHJ5IjtPOjI0OiJKb29tbGFcUmVnaXN0cnlcUmVnaXN0cnkiOjI6e3M6NzoiACoAZGF0YSI7Tzo4OiJzdGRDbGFzcyI6Mjp7czo1OiJ1c2VycyI7Tzo4OiJzdGRDbGFzcyI6MTp7czo1OiJsb2dpbiI7Tzo4OiJzdGRDbGFzcyI6MTp7czo0OiJmb3JtIjtPOjg6InN0ZENsYXNzIjoyOntzOjY6InJldHVybiI7czoyMDoiaW5kZXgucGhwP0l0ZW1pZD0xMDkiO3M6NDoiZGF0YSI7YTowOnt9fX19czoxNDoiY29tX3ZvbHVudGVlcnMiO086ODoic3RkQ2xhc3MiOjI6e3M6NDoiZWRpdCI7Tzo4OiJzdGRDbGFzcyI6MTp7czo5OiJ2b2x1bnRlZXIiO086ODoic3RkQ2xhc3MiOjI6e3M6MjoiaWQiO2E6MDp7fXM6NDoiZGF0YSI7Tjt9fXM6MTI6InJlZ2lzdHJhdGlvbiI7Tzo4OiJzdGRDbGFzcyI6MTp7czoyOiJpZCI7aToxMDt9fX1zOjk6InNlcGFyYXRvciI7czoxOiIuIjt9czo0OiJ1c2VyIjtPOjU6IkpVc2VyIjoxOntzOjI6ImlkIjtzOjI6IjEwIjt9czoxMToiYXBwbGljYXRpb24iO086ODoic3RkQ2xhc3MiOjE6e3M6NToicXVldWUiO047fX19czo5OiJzZXBhcmF0b3IiO3M6MToiLiI7fQ==\";',10,'extensions.member@volunteers.joomla.org'),
	('d64afe3f9a27b5deab0fc73051af5d24',1,0,'1471978748','joomla|s:10416:\"TzoyNDoiSm9vbWxhXFJlZ2lzdHJ5XFJlZ2lzdHJ5IjoyOntzOjc6IgAqAGRhdGEiO086ODoic3RkQ2xhc3MiOjE6e3M6OToiX19kZWZhdWx0IjtPOjg6InN0ZENsYXNzIjo0OntzOjc6InNlc3Npb24iO086ODoic3RkQ2xhc3MiOjM6e3M6NzoiY291bnRlciI7aTo5MDtzOjU6InRpbWVyIjtPOjg6InN0ZENsYXNzIjozOntzOjU6InN0YXJ0IjtpOjE0NzE5NzY2NTc7czo0OiJsYXN0IjtpOjE0NzE5Nzg3NDE7czozOiJub3ciO2k6MTQ3MTk3ODc0Nzt9czo1OiJ0b2tlbiI7czozMjoiYzdLeTY5OVhuYk1FNDN0aUc0ODVFSjBDOFFmOHBwWTQiO31zOjg6InJlZ2lzdHJ5IjtPOjI0OiJKb29tbGFcUmVnaXN0cnlcUmVnaXN0cnkiOjI6e3M6NzoiACoAZGF0YSI7Tzo4OiJzdGRDbGFzcyI6OTp7czoxMzoiY29tX2luc3RhbGxlciI7Tzo4OiJzdGRDbGFzcyI6NDp7czo3OiJtZXNzYWdlIjtzOjA6IiI7czoxNzoiZXh0ZW5zaW9uX21lc3NhZ2UiO3M6MDoiIjtzOjEyOiJyZWRpcmVjdF91cmwiO047czo2OiJtYW5hZ2UiO086ODoic3RkQ2xhc3MiOjM6e3M6NjoiZmlsdGVyIjthOjU6e3M6Njoic2VhcmNoIjtzOjA6IiI7czo2OiJzdGF0dXMiO3M6MToiMCI7czo5OiJjbGllbnRfaWQiO3M6MDoiIjtzOjQ6InR5cGUiO3M6OToiY29tcG9uZW50IjtzOjY6ImZvbGRlciI7czowOiIiO31zOjQ6Imxpc3QiO2E6Mjp7czoxMjoiZnVsbG9yZGVyaW5nIjtzOjg6Im5hbWUgQVNDIjtzOjU6ImxpbWl0IjtzOjI6IjUwIjt9czoxMDoibGltaXRzdGFydCI7aTowO319czoxMDoiY29tX2NvbmZpZyI7Tzo4OiJzdGRDbGFzcyI6MTp7czo2OiJjb25maWciO086ODoic3RkQ2xhc3MiOjE6e3M6NjoiZ2xvYmFsIjtPOjg6InN0ZENsYXNzIjoxOntzOjQ6ImRhdGEiO2E6OTE6e3M6Nzoib2ZmbGluZSI7czoxOiIwIjtzOjE1OiJvZmZsaW5lX21lc3NhZ2UiO3M6Njk6IlRoaXMgc2l0ZSBpcyBkb3duIGZvciBtYWludGVuYW5jZS48YnIgLz5QbGVhc2UgY2hlY2sgYmFjayBhZ2FpbiBzb29uLiI7czoyMzoiZGlzcGxheV9vZmZsaW5lX21lc3NhZ2UiO3M6MToiMSI7czoxMzoib2ZmbGluZV9pbWFnZSI7czowOiIiO3M6ODoic2l0ZW5hbWUiO3M6MjU6Ikpvb21sYSEgVm9sdW50ZWVycyBQb3J0YWwiO3M6NjoiZWRpdG9yIjtzOjM6ImpjZSI7czo3OiJjYXB0Y2hhIjtzOjE6IjAiO3M6MTA6Imxpc3RfbGltaXQiO3M6MjoiNTAiO3M6NjoiYWNjZXNzIjtzOjE6IjEiO3M6NToiZGVidWciO3M6MToiMCI7czoxMDoiZGVidWdfbGFuZyI7czoxOiIwIjtzOjY6ImRidHlwZSI7czo2OiJteXNxbGkiO3M6NDoiaG9zdCI7czo5OiJsb2NhbGhvc3QiO3M6NDoidXNlciI7czo0OiJyb290IjtzOjg6InBhc3N3b3JkIjtzOjQ6InJvb3QiO3M6MjoiZGIiO3M6MjE6InZvbHVudGVlcnNfam9vbWxhX29yZyI7czo4OiJkYnByZWZpeCI7czo2OiJwYTc2M18iO3M6OToibGl2ZV9zaXRlIjtzOjA6IiI7czo2OiJzZWNyZXQiO3M6MTY6Ilg0a1E1UTVPTnU3Y1NyclEiO3M6NDoiZ3ppcCI7czoxOiIwIjtzOjE1OiJlcnJvcl9yZXBvcnRpbmciO3M6NzoiZGVmYXVsdCI7czo3OiJoZWxwdXJsIjtzOjc0OiJodHRwczovL2hlbHAuam9vbWxhLm9yZy9wcm94eS9pbmRleC5waHA/a2V5cmVmPUhlbHB7bWFqb3J9e21pbm9yfTp7a2V5cmVmfSI7czo4OiJmdHBfaG9zdCI7czowOiIiO3M6ODoiZnRwX3BvcnQiO3M6MDoiIjtzOjg6ImZ0cF91c2VyIjtzOjA6IiI7czo4OiJmdHBfcGFzcyI7czowOiIiO3M6ODoiZnRwX3Jvb3QiO3M6MDoiIjtzOjEwOiJmdHBfZW5hYmxlIjtzOjE6IjAiO3M6Njoib2Zmc2V0IjtzOjM6IlVUQyI7czoxMDoibWFpbG9ubGluZSI7czoxOiIxIjtzOjY6Im1haWxlciI7czo0OiJtYWlsIjtzOjg6Im1haWxmcm9tIjtzOjI1OiJhZG1pbl9ub19yZXBseUBqb29tbGEub3JnIjtzOjg6ImZyb21uYW1lIjtzOjI1OiJKb29tbGEhIFZvbHVudGVlcnMgUG9ydGFsIjtzOjg6InNlbmRtYWlsIjtzOjE4OiIvdXNyL3NiaW4vc2VuZG1haWwiO3M6ODoic210cGF1dGgiO3M6MToiMCI7czo4OiJzbXRwdXNlciI7czowOiIiO3M6ODoic210cHBhc3MiO3M6MDoiIjtzOjg6InNtdHBob3N0IjtzOjk6ImxvY2FsaG9zdCI7czoxMDoic210cHNlY3VyZSI7czo0OiJub25lIjtzOjg6InNtdHBwb3J0IjtzOjI6IjI1IjtzOjc6ImNhY2hpbmciO3M6MToiMCI7czoxMzoiY2FjaGVfaGFuZGxlciI7czo0OiJmaWxlIjtzOjk6ImNhY2hldGltZSI7czoyOiIxNSI7czoyMDoiY2FjaGVfcGxhdGZvcm1wcmVmaXgiO3M6MToiMCI7czo4OiJNZXRhRGVzYyI7czowOiIiO3M6ODoiTWV0YUtleXMiO3M6MDoiIjtzOjk6Ik1ldGFUaXRsZSI7czoxOiIxIjtzOjEwOiJNZXRhQXV0aG9yIjtzOjE6IjAiO3M6MTE6Ik1ldGFWZXJzaW9uIjtzOjE6IjAiO3M6Njoicm9ib3RzIjtzOjA6IiI7czozOiJzZWYiO3M6MToiMSI7czoxMToic2VmX3Jld3JpdGUiO3M6MToiMSI7czoxMDoic2VmX3N1ZmZpeCI7czoxOiIwIjtzOjEyOiJ1bmljb2Rlc2x1Z3MiO3M6MToiMCI7czoxMDoiZmVlZF9saW1pdCI7czoyOiIxMCI7czoxMDoiZmVlZF9lbWFpbCI7czo0OiJub25lIjtzOjg6ImxvZ19wYXRoIjtzOjcwOiIvVXNlcnMvc2FuZGVycG90amVyL1NpdGVzL3ZvbHVudGVlcnMuam9vbWxhLm9yZy93d3cvYWRtaW5pc3RyYXRvci9sb2dzIjtzOjg6InRtcF9wYXRoIjtzOjU1OiIvVXNlcnMvc2FuZGVycG90amVyL1NpdGVzL3ZvbHVudGVlcnMuam9vbWxhLm9yZy93d3cvdG1wIjtzOjg6ImxpZmV0aW1lIjtzOjI6IjYwIjtzOjE1OiJzZXNzaW9uX2hhbmRsZXIiO3M6ODoiZGF0YWJhc2UiO3M6MTY6Im1lbWNhY2hlX3BlcnNpc3QiO3M6MToiMSI7czoxNzoibWVtY2FjaGVfY29tcHJlc3MiO3M6MToiMCI7czoyMDoibWVtY2FjaGVfc2VydmVyX2hvc3QiO3M6OToibG9jYWxob3N0IjtzOjIwOiJtZW1jYWNoZV9zZXJ2ZXJfcG9ydCI7czo1OiIxMTIxMSI7czoxNzoibWVtY2FjaGVkX3BlcnNpc3QiO3M6MToiMSI7czoxODoibWVtY2FjaGVkX2NvbXByZXNzIjtzOjE6IjAiO3M6MjE6Im1lbWNhY2hlZF9zZXJ2ZXJfaG9zdCI7czo5OiJsb2NhbGhvc3QiO3M6MjE6Im1lbWNhY2hlZF9zZXJ2ZXJfcG9ydCI7czo1OiIxMTIxMSI7czoxMzoicmVkaXNfcGVyc2lzdCI7czoxOiIxIjtzOjE3OiJyZWRpc19zZXJ2ZXJfaG9zdCI7czo5OiJsb2NhbGhvc3QiO3M6MTc6InJlZGlzX3NlcnZlcl9wb3J0IjtzOjQ6IjYzNzkiO3M6MTc6InJlZGlzX3NlcnZlcl9hdXRoIjtzOjA6IiI7czoxNToicmVkaXNfc2VydmVyX2RiIjtzOjE6IjAiO3M6MTI6InByb3h5X2VuYWJsZSI7czoxOiIwIjtzOjEwOiJwcm94eV9ob3N0IjtzOjA6IiI7czoxMDoicHJveHlfcG9ydCI7czowOiIiO3M6MTA6InByb3h5X3VzZXIiO3M6MDoiIjtzOjEwOiJwcm94eV9wYXNzIjtzOjA6IiI7czoxMToibWFzc21haWxvZmYiO3M6MToiMCI7czoxMDoiTWV0YVJpZ2h0cyI7czowOiIiO3M6MTk6InNpdGVuYW1lX3BhZ2V0aXRsZXMiO3M6MToiMCI7czo5OiJmb3JjZV9zc2wiO3M6MToiMCI7czoyODoic2Vzc2lvbl9tZW1jYWNoZV9zZXJ2ZXJfaG9zdCI7czo5OiJsb2NhbGhvc3QiO3M6Mjg6InNlc3Npb25fbWVtY2FjaGVfc2VydmVyX3BvcnQiO3M6NToiMTEyMTEiO3M6Mjk6InNlc3Npb25fbWVtY2FjaGVkX3NlcnZlcl9ob3N0IjtzOjk6ImxvY2FsaG9zdCI7czoyOToic2Vzc2lvbl9tZW1jYWNoZWRfc2VydmVyX3BvcnQiO3M6NToiMTEyMTEiO3M6MTI6ImZyb250ZWRpdGluZyI7czoxOiIwIjtzOjEzOiJjb29raWVfZG9tYWluIjtzOjA6IiI7czoxMToiY29va2llX3BhdGgiO3M6MDoiIjtzOjg6ImFzc2V0X2lkIjtpOjE7czo3OiJmaWx0ZXJzIjthOjU6e2k6MTthOjM6e3M6MTE6ImZpbHRlcl90eXBlIjtzOjI6Ik5IIjtzOjExOiJmaWx0ZXJfdGFncyI7czowOiIiO3M6MTc6ImZpbHRlcl9hdHRyaWJ1dGVzIjtzOjA6IiI7fWk6OTthOjM6e3M6MTE6ImZpbHRlcl90eXBlIjtzOjI6IkJMIjtzOjExOiJmaWx0ZXJfdGFncyI7czowOiIiO3M6MTc6ImZpbHRlcl9hdHRyaWJ1dGVzIjtzOjA6IiI7fWk6ODthOjM6e3M6MTE6ImZpbHRlcl90eXBlIjtzOjQ6Ik5PTkUiO3M6MTE6ImZpbHRlcl90YWdzIjtzOjA6IiI7czoxNzoiZmlsdGVyX2F0dHJpYnV0ZXMiO3M6MDoiIjt9aToyO2E6Mzp7czoxMToiZmlsdGVyX3R5cGUiO3M6MjoiQkwiO3M6MTE6ImZpbHRlcl90YWdzIjtzOjA6IiI7czoxNzoiZmlsdGVyX2F0dHJpYnV0ZXMiO3M6MDoiIjt9aToxMDthOjM6e3M6MTE6ImZpbHRlcl90eXBlIjtzOjI6IkJMIjtzOjExOiJmaWx0ZXJfdGFncyI7czowOiIiO3M6MTc6ImZpbHRlcl9hdHRyaWJ1dGVzIjtzOjA6IiI7fX19fX19czoxNDoiY29tX3ZvbHVudGVlcnMiO086ODoic3RkQ2xhc3MiOjI6e3M6NDoiZWRpdCI7Tzo4OiJzdGRDbGFzcyI6MTp7czo5OiJ2b2x1bnRlZXIiO086ODoic3RkQ2xhc3MiOjI6e3M6MjoiaWQiO2E6MDp7fXM6NDoiZGF0YSI7Tjt9fXM6MTI6InJlZ2lzdHJhdGlvbiI7Tzo4OiJzdGRDbGFzcyI6MTp7czoyOiJpZCI7aTo0O319czoxMToiY29tX2NvbnRlbnQiO086ODoic3RkQ2xhc3MiOjE6e3M6ODoiYXJ0aWNsZXMiO086ODoic3RkQ2xhc3MiOjM6e3M6NjoiZmlsdGVyIjthOjg6e3M6Njoic2VhcmNoIjtzOjA6IiI7czo5OiJwdWJsaXNoZWQiO3M6MDoiIjtzOjExOiJjYXRlZ29yeV9pZCI7czowOiIiO3M6NjoiYWNjZXNzIjtzOjA6IiI7czo5OiJhdXRob3JfaWQiO3M6MDoiIjtzOjg6Imxhbmd1YWdlIjtzOjA6IiI7czozOiJ0YWciO3M6MDoiIjtzOjU6ImxldmVsIjtzOjA6IiI7fXM6NDoibGlzdCI7YToyOntzOjEyOiJmdWxsb3JkZXJpbmciO3M6OToiYS5pZCBERVNDIjtzOjU6ImxpbWl0IjtzOjI6IjUwIjt9czoxMDoibGltaXRzdGFydCI7aTowO319czoxMToiY29tX21vZHVsZXMiO086ODoic3RkQ2xhc3MiOjE6e3M6NzoibW9kdWxlcyI7Tzo4OiJzdGRDbGFzcyI6NDp7czo2OiJmaWx0ZXIiO2E6Nzp7czo2OiJzZWFyY2giO3M6MDoiIjtzOjU6InN0YXRlIjtzOjI6Ii0yIjtzOjg6InBvc2l0aW9uIjtzOjA6IiI7czo2OiJtb2R1bGUiO3M6MDoiIjtzOjg6Im1lbnVpdGVtIjtzOjA6IiI7czo2OiJhY2Nlc3MiO3M6MDoiIjtzOjg6Imxhbmd1YWdlIjtzOjA6IiI7fXM6OToiY2xpZW50X2lkIjtpOjA7czo0OiJsaXN0IjthOjI6e3M6MTI6ImZ1bGxvcmRlcmluZyI7czoxNDoiYS5wb3NpdGlvbiBBU0MiO3M6NToibGltaXQiO3M6MjoiNTAiO31zOjEwOiJsaW1pdHN0YXJ0IjtpOjA7fX1zOjE0OiJjb21fY2F0ZWdvcmllcyI7Tzo4OiJzdGRDbGFzcyI6Mjp7czoxMDoiY2F0ZWdvcmllcyI7Tzo4OiJzdGRDbGFzcyI6NTp7czo3OiJiYW5uZXJzIjtPOjg6InN0ZENsYXNzIjo0OntzOjY6ImZpbHRlciI7YTo3OntzOjY6InNlYXJjaCI7czowOiIiO3M6OToicHVibGlzaGVkIjtzOjI6Ii0yIjtzOjY6ImFjY2VzcyI7czowOiIiO3M6ODoibGFuZ3VhZ2UiO3M6MDoiIjtzOjM6InRhZyI7czowOiIiO3M6NToibGV2ZWwiO3M6MDoiIjtzOjk6ImV4dGVuc2lvbiI7czoxMToiY29tX2Jhbm5lcnMiO31zOjQ6Imxpc3QiO2E6Mjp7czoxMjoiZnVsbG9yZGVyaW5nIjtzOjk6ImEubGZ0IEFTQyI7czo1OiJsaW1pdCI7czoyOiI1MCI7fXM6Njoic2VhcmNoIjtzOjA6IiI7czoxMDoibGltaXRzdGFydCI7aTowO31zOjc6ImNvbnRhY3QiO086ODoic3RkQ2xhc3MiOjQ6e3M6NjoiZmlsdGVyIjthOjc6e3M6Njoic2VhcmNoIjtzOjA6IiI7czo5OiJwdWJsaXNoZWQiO3M6MjoiLTIiO3M6NjoiYWNjZXNzIjtzOjA6IiI7czo4OiJsYW5ndWFnZSI7czowOiIiO3M6MzoidGFnIjtzOjA6IiI7czo1OiJsZXZlbCI7czowOiIiO3M6OToiZXh0ZW5zaW9uIjtzOjExOiJjb21fY29udGFjdCI7fXM6NDoibGlzdCI7YToyOntzOjEyOiJmdWxsb3JkZXJpbmciO3M6OToiYS5sZnQgQVNDIjtzOjU6ImxpbWl0IjtzOjI6IjUwIjt9czo2OiJzZWFyY2giO3M6MDoiIjtzOjEwOiJsaW1pdHN0YXJ0IjtpOjA7fXM6OToibmV3c2ZlZWRzIjtPOjg6InN0ZENsYXNzIjo0OntzOjY6ImZpbHRlciI7YTo3OntzOjY6InNlYXJjaCI7czowOiIiO3M6OToicHVibGlzaGVkIjtzOjI6Ii0yIjtzOjY6ImFjY2VzcyI7czowOiIiO3M6ODoibGFuZ3VhZ2UiO3M6MDoiIjtzOjM6InRhZyI7czowOiIiO3M6NToibGV2ZWwiO3M6MDoiIjtzOjk6ImV4dGVuc2lvbiI7czoxMzoiY29tX25ld3NmZWVkcyI7fXM6NDoibGlzdCI7YToyOntzOjEyOiJmdWxsb3JkZXJpbmciO3M6OToiYS5sZnQgQVNDIjtzOjU6ImxpbWl0IjtzOjI6IjUwIjt9czo2OiJzZWFyY2giO3M6MDoiIjtzOjEwOiJsaW1pdHN0YXJ0IjtpOjA7fXM6NToidXNlcnMiO086ODoic3RkQ2xhc3MiOjQ6e3M6NjoiZmlsdGVyIjthOjc6e3M6Njoic2VhcmNoIjtzOjA6IiI7czo5OiJwdWJsaXNoZWQiO3M6MjoiLTIiO3M6NjoiYWNjZXNzIjtzOjA6IiI7czo4OiJsYW5ndWFnZSI7czowOiIiO3M6MzoidGFnIjtzOjA6IiI7czo1OiJsZXZlbCI7czowOiIiO3M6OToiZXh0ZW5zaW9uIjtzOjk6ImNvbV91c2VycyI7fXM6NDoibGlzdCI7YToyOntzOjEyOiJmdWxsb3JkZXJpbmciO3M6OToiYS5sZnQgQVNDIjtzOjU6ImxpbWl0IjtzOjI6IjUwIjt9czo2OiJzZWFyY2giO3M6MDoiIjtzOjEwOiJsaW1pdHN0YXJ0IjtpOjA7fXM6NzoiY29udGVudCI7Tzo4OiJzdGRDbGFzcyI6Mjp7czo2OiJmaWx0ZXIiO086ODoic3RkQ2xhc3MiOjE6e3M6OToiZXh0ZW5zaW9uIjtzOjExOiJjb21fY29udGVudCI7fXM6NDoibGlzdCI7YTo0OntzOjk6ImRpcmVjdGlvbiI7czozOiJhc2MiO3M6NToibGltaXQiO3M6MjoiNTAiO3M6ODoib3JkZXJpbmciO3M6NToiYS5sZnQiO3M6NToic3RhcnQiO2Q6MDt9fX1zOjQ6ImVkaXQiO086ODoic3RkQ2xhc3MiOjE6e3M6ODoiY2F0ZWdvcnkiO086ODoic3RkQ2xhc3MiOjI6e3M6MjoiaWQiO2E6MDp7fXM6NDoiZGF0YSI7Tjt9fX1zOjk6ImNvbV9tZW51cyI7Tzo4OiJzdGRDbGFzcyI6MTp7czo1OiJpdGVtcyI7Tzo4OiJzdGRDbGFzcyI6Mjp7czo4OiJtZW51dHlwZSI7czowOiIiO3M6NDoibGlzdCI7YTo0OntzOjk6ImRpcmVjdGlvbiI7czozOiJhc2MiO3M6NToibGltaXQiO3M6MjoiNTAiO3M6ODoib3JkZXJpbmciO3M6NToiYS5sZnQiO3M6NToic3RhcnQiO2Q6MDt9fX1zOjEyOiJjb21fcmVkaXJlY3QiO086ODoic3RkQ2xhc3MiOjE6e3M6NToibGlua3MiO086ODoic3RkQ2xhc3MiOjM6e3M6NjoiZmlsdGVyIjthOjM6e3M6Njoic2VhcmNoIjtzOjA6IiI7czo1OiJzdGF0ZSI7czoyOiItMiI7czoxMToiaHR0cF9zdGF0dXMiO3M6MDoiIjt9czo0OiJsaXN0IjthOjI6e3M6MTI6ImZ1bGxvcmRlcmluZyI7czoxMzoiYS5vbGRfdXJsIEFTQyI7czo1OiJsaW1pdCI7czoyOiI1MCI7fXM6MTA6ImxpbWl0c3RhcnQiO2k6MDt9fXM6MTE6ImNvbV9wbHVnaW5zIjtPOjg6InN0ZENsYXNzIjoxOntzOjc6InBsdWdpbnMiO086ODoic3RkQ2xhc3MiOjM6e3M6NjoiZmlsdGVyIjthOjQ6e3M6Njoic2VhcmNoIjtzOjQ6InJlZGkiO3M6NzoiZW5hYmxlZCI7czowOiIiO3M6NjoiZm9sZGVyIjtzOjA6IiI7czo2OiJhY2Nlc3MiO3M6MDoiIjt9czo0OiJsaXN0IjthOjQ6e3M6MTI6ImZ1bGxvcmRlcmluZyI7czoxMDoiZm9sZGVyIEFTQyI7czo1OiJsaW1pdCI7czoyOiI1MCI7czo5OiJzb3J0VGFibGUiO3M6NjoiZm9sZGVyIjtzOjE0OiJkaXJlY3Rpb25UYWJsZSI7czozOiJBU0MiO31zOjEwOiJsaW1pdHN0YXJ0IjtpOjA7fX19czo5OiJzZXBhcmF0b3IiO3M6MToiLiI7fXM6NDoidXNlciI7Tzo1OiJKVXNlciI6MTp7czoyOiJpZCI7czoxOiIxIjt9czoxMToiYXBwbGljYXRpb24iO086ODoic3RkQ2xhc3MiOjE6e3M6NToicXVldWUiO047fX19czo5OiJzZXBhcmF0b3IiO3M6MToiLiI7fQ==\";',1,'admin@volunteers.joomla.org');

/*!40000 ALTER TABLE `vol_session` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_tags
# ------------------------------------------------------------

CREATE TABLE `vol_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL DEFAULT '0',
  `rgt` int(11) NOT NULL DEFAULT '0',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `path` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadesc` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The meta keywords for the page.',
  `metadata` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded metadata properties.',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modified_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `images` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `urls` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tag_idx` (`published`,`access`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_language` (`language`),
  KEY `idx_path` (`path`(100)),
  KEY `idx_alias` (`alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_tags` WRITE;
/*!40000 ALTER TABLE `vol_tags` DISABLE KEYS */;

INSERT INTO `vol_tags` (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`, `created_by_alias`, `modified_user_id`, `modified_time`, `images`, `urls`, `hits`, `language`, `version`, `publish_up`, `publish_down`)
VALUES
	(1,0,0,1,0,'','ROOT',X'726F6F74','','',1,0,'0000-00-00 00:00:00',1,'','','','',0,'2011-01-01 00:00:01','',0,'0000-00-00 00:00:00','','',0,'*',1,'0000-00-00 00:00:00','0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_tags` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_template_styles
# ------------------------------------------------------------

CREATE TABLE `vol_template_styles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `client_id` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `home` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template`),
  KEY `idx_home` (`home`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_template_styles` WRITE;
/*!40000 ALTER TABLE `vol_template_styles` DISABLE KEYS */;

INSERT INTO `vol_template_styles` (`id`, `template`, `client_id`, `home`, `title`, `params`)
VALUES
	(4,'beez3',0,'0','Beez3 - Default','{\"wrapperSmall\":\"53\",\"wrapperLarge\":\"72\",\"logo\":\"images\\/joomla_black.gif\",\"sitetitle\":\"Joomla!\",\"sitedescription\":\"Open Source Content Management\",\"navposition\":\"left\",\"templatecolor\":\"personal\",\"html5\":\"0\"}'),
	(5,'hathor',1,'0','Hathor - Default','{\"showSiteName\":\"0\",\"colourChoice\":\"\",\"boldText\":\"0\"}'),
	(7,'protostar',0,'0','protostar - Default','{\"templateColor\":\"\",\"logoFile\":\"\",\"googleFont\":\"1\",\"googleFontName\":\"Open+Sans\",\"fluidContainer\":\"0\"}'),
	(8,'isis',1,'1','isis - Default','{\"templateColor\":\"\",\"logoFile\":\"\"}'),
	(9,'joomla',0,'1','joomla - Default','{\"templateColor\":\"#0088cc\",\"templateBackgroundColor\":\"#f4f6f7\",\"logoFile\":\"\",\"sitetitle\":\"\",\"sitedescription\":\"\",\"googleFont\":\"1\",\"googleFontName\":\"Open+Sans\",\"fluidContainer\":\"0\"}'),
	(10,'protostar',0,'0','protostar - Default','{\"templateColor\":\"\",\"logoFile\":\"\",\"googleFont\":\"1\",\"googleFontName\":\"Open+Sans\",\"fluidContainer\":\"0\"}'),
	(11,'isis',1,'1','isis - Default','{\"templateColor\":\"\",\"logoFile\":\"\"}'),
	(12,'beez3',0,'0','beez3 - Default','{\"wrapperSmall\":53,\"wrapperLarge\":72,\"logo\":\"\",\"sitetitle\":\"\",\"sitedescription\":\"\",\"navposition\":\"center\",\"bootstrap\":\"\",\"templatecolor\":\"nature\",\"headerImage\":\"\",\"backgroundcolor\":\"#eee\"}');

/*!40000 ALTER TABLE `vol_template_styles` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_ucm_base
# ------------------------------------------------------------

CREATE TABLE `vol_ucm_base` (
  `ucm_id` int(10) unsigned NOT NULL,
  `ucm_item_id` int(10) NOT NULL,
  `ucm_type_id` int(11) NOT NULL,
  `ucm_language_id` int(11) NOT NULL,
  PRIMARY KEY (`ucm_id`),
  KEY `idx_ucm_item_id` (`ucm_item_id`),
  KEY `idx_ucm_type_id` (`ucm_type_id`),
  KEY `idx_ucm_language_id` (`ucm_language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_ucm_content
# ------------------------------------------------------------

CREATE TABLE `vol_ucm_content` (
  `core_content_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `core_type_alias` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'FK to the content types table',
  `core_title` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `core_body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_state` tinyint(1) NOT NULL DEFAULT '0',
  `core_checked_out_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `core_checked_out_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `core_access` int(10) unsigned NOT NULL DEFAULT '0',
  `core_params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_featured` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `core_metadata` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded metadata properties.',
  `core_created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `core_created_by_alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `core_created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_modified_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Most recent user that modified',
  `core_modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_language` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_publish_up` datetime NOT NULL,
  `core_publish_down` datetime NOT NULL,
  `core_content_item_id` int(10) unsigned DEFAULT NULL COMMENT 'ID from the individual type table',
  `asset_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to the #__assets table.',
  `core_images` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_urls` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `core_version` int(10) unsigned NOT NULL DEFAULT '1',
  `core_ordering` int(11) NOT NULL DEFAULT '0',
  `core_metakey` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_metadesc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `core_catid` int(10) unsigned NOT NULL DEFAULT '0',
  `core_xreference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A reference to enable linkages to external data sets.',
  `core_type_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`core_content_id`),
  KEY `tag_idx` (`core_state`,`core_access`),
  KEY `idx_access` (`core_access`),
  KEY `idx_language` (`core_language`),
  KEY `idx_modified_time` (`core_modified_time`),
  KEY `idx_created_time` (`core_created_time`),
  KEY `idx_core_modified_user_id` (`core_modified_user_id`),
  KEY `idx_core_checked_out_user_id` (`core_checked_out_user_id`),
  KEY `idx_core_created_user_id` (`core_created_user_id`),
  KEY `idx_core_type_id` (`core_type_id`),
  KEY `idx_alias` (`core_alias`(100)),
  KEY `idx_title` (`core_title`(100)),
  KEY `idx_content_type` (`core_type_alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contains core content data in name spaced fields';



# Dump of table vol_ucm_history
# ------------------------------------------------------------

CREATE TABLE `vol_ucm_history` (
  `version_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ucm_item_id` int(10) unsigned NOT NULL,
  `ucm_type_id` int(10) unsigned NOT NULL,
  `version_note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Optional version name',
  `save_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editor_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `character_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of characters in this version.',
  `sha1_hash` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SHA1 hash of the version_data column.',
  `version_data` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'json-encoded string of version data',
  `keep_forever` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=auto delete; 1=keep',
  PRIMARY KEY (`version_id`),
  KEY `idx_ucm_item_id` (`ucm_type_id`,`ucm_item_id`),
  KEY `idx_save_date` (`save_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_update_sites
# ------------------------------------------------------------

CREATE TABLE `vol_update_sites` (
  `update_site_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `location` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` int(11) DEFAULT '0',
  `last_check_timestamp` bigint(20) DEFAULT '0',
  `extra_query` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`update_site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Update Sites';

LOCK TABLES `vol_update_sites` WRITE;
/*!40000 ALTER TABLE `vol_update_sites` DISABLE KEYS */;

INSERT INTO `vol_update_sites` (`update_site_id`, `name`, `type`, `location`, `enabled`, `last_check_timestamp`, `extra_query`)
VALUES
	(1,'Joomla! Core','collection','https://update.joomla.org/core/list.xml',1,0,''),
	(2,'Joomla! Extension Directory','collection','https://update.joomla.org/jed/list.xml',0,0,''),
	(3,'Accredited Joomla! Translations','collection','https://update.joomla.org/language/translationlist_3.xml',0,0,''),
	(4,'Joomla! Update Component Update Site','extension','https://update.joomla.org/core/extensions/com_joomlaupdate.xml',0,0,''),
	(15,'Accredited Joomla! Translations','collection','https://update.joomla.org/language/translationlist_3.xml',1,0,''),
	(18,'JCE Editor Updates','extension','https://www.joomlacontenteditor.net/index.php?option=com_updates&view=update&format=xml&id=1&file=extension.xml',1,0,''),
	(19,'PLG_NONSEFTOSEF','extension','https://check.kubik-rubik.de/updates/nonseftosef.xml',1,0,'');

/*!40000 ALTER TABLE `vol_update_sites` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_update_sites_extensions
# ------------------------------------------------------------

CREATE TABLE `vol_update_sites_extensions` (
  `update_site_id` int(11) NOT NULL DEFAULT '0',
  `extension_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`update_site_id`,`extension_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Links extensions to update sites';

LOCK TABLES `vol_update_sites_extensions` WRITE;
/*!40000 ALTER TABLE `vol_update_sites_extensions` DISABLE KEYS */;

INSERT INTO `vol_update_sites_extensions` (`update_site_id`, `extension_id`)
VALUES
	(1,700),
	(2,700),
	(3,802),
	(4,28),
	(15,802),
	(18,808),
	(19,814);

/*!40000 ALTER TABLE `vol_update_sites_extensions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_updates
# ------------------------------------------------------------

CREATE TABLE `vol_updates` (
  `update_id` int(11) NOT NULL AUTO_INCREMENT,
  `update_site_id` int(11) DEFAULT '0',
  `extension_id` int(11) DEFAULT '0',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `element` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `folder` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `client_id` tinyint(3) DEFAULT '0',
  `version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `detailsurl` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `infourl` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra_query` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`update_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Available Updates';



# Dump of table vol_user_keys
# ------------------------------------------------------------

CREATE TABLE `vol_user_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `series` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invalid` tinyint(4) NOT NULL,
  `time` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uastring` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `series` (`series`),
  UNIQUE KEY `series_2` (`series`),
  UNIQUE KEY `series_3` (`series`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_user_notes
# ------------------------------------------------------------

CREATE TABLE `vol_user_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_user_id` int(10) unsigned NOT NULL,
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `review_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table vol_user_profiles
# ------------------------------------------------------------

CREATE TABLE `vol_user_profiles` (
  `user_id` int(11) NOT NULL,
  `profile_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `idx_user_id_profile_key` (`user_id`,`profile_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Simple user profile storage table';



# Dump of table vol_user_usergroup_map
# ------------------------------------------------------------

CREATE TABLE `vol_user_usergroup_map` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__users.id',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__usergroups.id',
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_user_usergroup_map` WRITE;
/*!40000 ALTER TABLE `vol_user_usergroup_map` DISABLE KEYS */;

INSERT INTO `vol_user_usergroup_map` (`user_id`, `group_id`)
VALUES
	(1,2),
	(1,8),
	(2,2),
	(3,2),
	(4,2),
	(5,2),
	(6,2),
	(7,2),
	(8,2),
	(9,2),
	(10,2),
	(11,2),
	(12,2),
	(13,2),
	(14,2),
	(15,2);

/*!40000 ALTER TABLE `vol_user_usergroup_map` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_usergroups
# ------------------------------------------------------------

CREATE TABLE `vol_usergroups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Adjacency List Reference Id',
  `lft` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set lft.',
  `rgt` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set rgt.',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usergroup_parent_title_lookup` (`parent_id`,`title`),
  KEY `idx_usergroup_title_lookup` (`title`),
  KEY `idx_usergroup_adjacency_lookup` (`parent_id`),
  KEY `idx_usergroup_nested_set_lookup` (`lft`,`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_usergroups` WRITE;
/*!40000 ALTER TABLE `vol_usergroups` DISABLE KEYS */;

INSERT INTO `vol_usergroups` (`id`, `parent_id`, `lft`, `rgt`, `title`)
VALUES
	(1,0,1,10,'Public'),
	(2,1,6,9,'Volunteers'),
	(8,1,4,5,'Super Users'),
	(9,1,2,3,'Guest'),
	(10,2,7,8,'Volunteer Team');

/*!40000 ALTER TABLE `vol_usergroups` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_users
# ------------------------------------------------------------

CREATE TABLE `vol_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `block` tinyint(4) NOT NULL DEFAULT '0',
  `sendEmail` tinyint(4) DEFAULT '0',
  `registerDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastvisitDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activation` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `params` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastResetTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Date of last password reset',
  `resetCount` int(11) NOT NULL DEFAULT '0' COMMENT 'Count of password resets since lastResetTime',
  `otpKey` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Two factor authentication encrypted keys',
  `otep` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'One time emergency passwords',
  `requireReset` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Require user to reset password on next login',
  PRIMARY KEY (`id`),
  KEY `idx_block` (`block`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `idx_name` (`name`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_users` WRITE;
/*!40000 ALTER TABLE `vol_users` DISABLE KEYS */;

INSERT INTO `vol_users` (`id`, `name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `activation`, `params`, `lastResetTime`, `resetCount`, `otpKey`, `otep`, `requireReset`)
VALUES
	(1,'Admin Joomler','admin@volunteers.joomla.org','admin@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-23 18:24:31','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(2,'Frontend Leader','frontend.leader@volunteers.joomla.org','frontend.leader@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 20:46:56','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(3,'Frontend Assistant','frontend.assistant@volunteers.joomla.org','frontend.assistant@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(4,'Frontend Coordinator','frontend.coordinator@volunteers.joomla.org','frontend.coordinator@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 20:47:16','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(5,'Backend Leader','backend.leader@volunteers.joomla.org','backend.leader@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(6,'Backend Assistant','backend.assistant@volunteers.joomla.org','backend.assistant@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 19:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(7,'Backend Coordinator','backend.coordinator@volunteers.joomla.org','backend.coordinator@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(8,'Extensions Leader','extensions.leader@volunteers.joomla.org','extensions.leader@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(9,'Extensions Assistant','extensions.assistant@volunteers.joomla.org','extensions.assistant@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(10,'Extensions Member','extensions.member@volunteers.joomla.org','extensions.member@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-23 18:23:41','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(11,'Extensions Contributor','extensions.contributor@volunteers.joomla.org','extensions.contributor@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(12,'Sample Leader','sample.leader@volunteers.joomla.org','sample.leader@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(13,'Sample Assistant','sample.assistant@volunteers.joomla.org','sample.assistant@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(14,'Sample Member','sample.member@volunteers.joomla.org','sample.member@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0),
	(15,'Sample Contributor','sample.contributor@volunteers.joomla.org','sample.contributor@volunteers.joomla.org','$2y$10$v0fEohGPIjyW7OfQqzj3k.ngQR4PXKV6hMjfVkgAqDvXbT4dD3fNO',0,1,'2016-08-08 12:00:00','2016-08-21 15:44:59','0','{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}','0000-00-00 00:00:00',0,'','',0);

/*!40000 ALTER TABLE `vol_users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_utf8_conversion
# ------------------------------------------------------------

CREATE TABLE `vol_utf8_conversion` (
  `converted` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_utf8_conversion` WRITE;
/*!40000 ALTER TABLE `vol_utf8_conversion` DISABLE KEYS */;

INSERT INTO `vol_utf8_conversion` (`converted`)
VALUES
	(2);

/*!40000 ALTER TABLE `vol_utf8_conversion` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_viewlevels
# ------------------------------------------------------------

CREATE TABLE `vol_viewlevels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `rules` varchar(5120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded access control.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_assetgroup_title_lookup` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `vol_viewlevels` WRITE;
/*!40000 ALTER TABLE `vol_viewlevels` DISABLE KEYS */;

INSERT INTO `vol_viewlevels` (`id`, `title`, `ordering`, `rules`)
VALUES
	(1,'Public',0,'[1]'),
	(2,'Registered',1,'[2,8]'),
	(3,'Special',2,'[8]'),
	(5,'Guest',0,'[9]'),
	(6,'Super Users',0,'[8]');

/*!40000 ALTER TABLE `vol_viewlevels` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_departments
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(50) NOT NULL DEFAULT '',
  `description` mediumtext,
  `website` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notes` mediumtext,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_departments` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_departments` DISABLE KEYS */;

INSERT INTO `vol_volunteers_departments` (`id`, `title`, `alias`, `description`, `website`, `email`, `notes`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`)
VALUES
	(1,'Frontend Department','frontend-department','Phasellus id molestie elit. Suspendisse finibus orci ac nunc posuere interdum. Donec elementum cursus condimentum. Cras quis enim tincidunt massa ultricies luctus vel at augue. Proin eget purus sed eros scelerisque venenatis. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce pretium id magna at egestas. Ut tincidunt a lectus non rhoncus. Morbi urna nibh, pulvinar a tempus at, varius pulvinar eros.','http://www.joomla.org','frontend@joomla.org','',1,1,1,1,'2016-08-21 15:07:14',0,'2016-08-21 15:07:14',0,'0000-00-00 00:00:00'),
	(2,'Backend Department','backend-department','Pellentesque tempor augue ut enim ultricies auctor. Nullam metus turpis, volutpat at scelerisque at, rutrum in ligula. Proin neque magna, bibendum ut posuere sit amet, pharetra quis massa. Donec id urna pretium, vulputate erat at, condimentum ipsum. In imperdiet turpis quam, ac eleifend augue porttitor rhoncus. Nulla aliquet laoreet nisl ut maximus. Vivamus eget dolor sollicitudin, sagittis metus sit amet, pellentesque arcu. Sed at elementum ligula. Sed leo eros, ornare ac enim in, convallis semper orci.','http://www.joomla.org/administrator','backend@joomla.org','',1,2,1,1,'2016-08-21 15:07:40',0,'2016-08-21 15:07:40',0,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_departments` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_members
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `volunteer` int(10) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `role` int(10) unsigned NOT NULL,
  `role_old` varchar(255) NOT NULL,
  `date_started` date NOT NULL,
  `date_ended` date NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `department` (`department`),
  KEY `team` (`team`),
  KEY `volunteer` (`volunteer`),
  KEY `position` (`position`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_members` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_members` DISABLE KEYS */;

INSERT INTO `vol_volunteers_members` (`id`, `department`, `team`, `volunteer`, `position`, `role`, `role_old`, `date_started`, `date_ended`, `state`, `ordering`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`)
VALUES
	(1,2,0,5,1,0,'','2016-08-08','0000-00-00',1,1,1,'2016-08-21 20:11:48',0,'2016-08-21 20:11:48',0,'0000-00-00 00:00:00'),
	(2,2,0,6,2,0,'','2016-08-08','0000-00-00',1,2,1,'2016-08-21 20:11:59',0,'2016-08-21 20:11:59',0,'0000-00-00 00:00:00'),
	(3,2,0,7,3,0,'','2016-08-08','0000-00-00',1,3,1,'2016-08-21 20:12:09',0,'2016-08-21 20:12:09',0,'0000-00-00 00:00:00'),
	(4,1,0,2,1,0,'','2016-08-08','0000-00-00',1,4,1,'2016-08-21 20:12:21',0,'2016-08-21 20:12:21',0,'0000-00-00 00:00:00'),
	(5,1,0,3,2,0,'','2016-08-08','0000-00-00',1,5,1,'2016-08-21 20:12:36',0,'2016-08-21 20:12:36',0,'0000-00-00 00:00:00'),
	(6,1,0,4,3,0,'','2016-08-08','0000-00-00',1,6,1,'2016-08-21 20:12:46',0,'2016-08-21 20:12:46',0,'0000-00-00 00:00:00'),
	(7,0,1,8,4,0,'','2016-08-08','0000-00-00',1,7,1,'2016-08-21 20:14:25',0,'2016-08-21 20:14:25',0,'0000-00-00 00:00:00'),
	(8,0,1,9,5,0,'','2016-08-08','0000-00-00',1,8,1,'2016-08-21 20:14:37',0,'2016-08-21 20:14:37',0,'0000-00-00 00:00:00'),
	(9,0,1,10,6,0,'','2016-08-08','0000-00-00',1,9,1,'2016-08-21 20:14:47',0,'2016-08-21 20:14:47',0,'0000-00-00 00:00:00'),
	(10,0,1,11,7,0,'','2016-08-08','0000-00-00',1,10,1,'2016-08-21 20:14:58',0,'2016-08-21 20:14:58',0,'0000-00-00 00:00:00'),
	(11,0,5,12,4,0,'','2016-08-08','0000-00-00',1,11,1,'2016-08-21 20:15:50',0,'2016-08-21 20:15:50',0,'0000-00-00 00:00:00'),
	(12,0,5,13,5,0,'','2016-08-08','0000-00-00',1,12,1,'2016-08-21 20:16:05',0,'2016-08-21 20:16:05',0,'0000-00-00 00:00:00'),
	(13,0,5,14,6,0,'','2016-08-08','0000-00-00',1,13,1,'2016-08-21 20:16:14',1,'2016-08-21 20:22:37',0,'0000-00-00 00:00:00'),
	(14,0,5,15,7,0,'','2016-08-08','0000-00-00',1,14,1,'2016-08-21 20:16:47',0,'2016-08-21 20:16:47',0,'0000-00-00 00:00:00'),
	(15,2,0,8,2,0,'','2016-07-08','2016-08-08',1,15,1,'2016-08-21 20:17:30',0,'2016-08-21 20:17:30',0,'0000-00-00 00:00:00'),
	(16,2,0,14,3,0,'','2016-08-08','0000-00-00',1,16,1,'2016-08-21 20:17:56',0,'2016-08-21 20:17:56',0,'0000-00-00 00:00:00'),
	(17,0,2,10,6,0,'','2016-08-08','0000-00-00',1,17,1,'2016-08-21 20:18:43',0,'2016-08-21 20:18:43',0,'0000-00-00 00:00:00'),
	(18,0,2,8,6,0,'','2016-08-08','0000-00-00',1,18,1,'2016-08-21 20:18:57',0,'2016-08-21 20:18:57',0,'0000-00-00 00:00:00'),
	(19,0,6,14,4,0,'','2016-08-08','0000-00-00',1,19,1,'2016-08-21 20:19:10',0,'2016-08-21 20:19:10',0,'0000-00-00 00:00:00'),
	(20,0,6,13,6,0,'','2016-08-08','0000-00-00',1,20,1,'2016-08-21 20:19:25',0,'2016-08-21 20:19:25',0,'0000-00-00 00:00:00'),
	(21,0,9,1,6,0,'','2016-08-08','0000-00-00',1,21,1,'2016-08-21 20:19:42',0,'2016-08-21 20:19:42',0,'0000-00-00 00:00:00'),
	(22,0,9,2,6,0,'','2016-08-08','0000-00-00',1,22,1,'2016-08-21 20:19:55',0,'2016-08-21 20:19:55',0,'0000-00-00 00:00:00'),
	(23,0,9,3,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(24,0,9,4,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(25,0,9,5,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(26,0,9,6,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(27,0,9,7,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(28,0,9,8,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(29,0,9,9,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(30,0,9,10,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(31,0,9,11,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(32,0,9,12,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(33,0,9,13,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(34,0,9,14,4,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',1,'2016-08-21 20:24:12',0,'0000-00-00 00:00:00'),
	(35,0,9,15,6,0,'','2016-08-08','0000-00-00',1,23,1,'2016-08-21 20:20:17',0,'2016-08-21 20:20:17',0,'0000-00-00 00:00:00'),
	(36,0,1,14,6,4,'','2016-08-08','0000-00-00',1,24,1,'2016-08-21 20:29:46',1,'2016-08-21 20:30:06',0,'0000-00-00 00:00:00'),
	(37,0,1,6,6,2,'','2016-08-21','0000-00-00',1,25,1,'2016-08-21 20:30:25',0,'2016-08-21 20:30:25',0,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_members` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_positions
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_positions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(50) NOT NULL DEFAULT '',
  `description` mediumtext,
  `type` tinyint(3) NOT NULL DEFAULT '0',
  `edit_department` tinyint(3) NOT NULL DEFAULT '0',
  `edit` tinyint(3) NOT NULL DEFAULT '0',
  `create_report` tinyint(3) NOT NULL DEFAULT '0',
  `create_team` tinyint(3) NOT NULL DEFAULT '0',
  `notes` mediumtext,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_positions` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_positions` DISABLE KEYS */;

INSERT INTO `vol_volunteers_positions` (`id`, `title`, `alias`, `description`, `type`, `edit_department`, `edit`, `create_report`, `create_team`, `notes`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`)
VALUES
	(1,'Departmental Coordination Team Leader','departmental-coordination-team-leader','',1,1,1,1,1,'',1,1,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(2,'Assistant Departmental Coordination Team Leader','assistant-departmental-coordination-team-leader','',1,1,1,1,1,'',1,2,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(3,'Departmental Coordinator','departmental-coordinator','',1,1,1,1,1,'',1,3,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(4,'Team Leader','team-leader','',2,0,1,1,1,'',1,4,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(5,'Assistant Team Leader','assistant-team-leader','',2,0,1,1,1,'',1,5,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(6,'Member','member','',2,0,0,1,0,'',1,6,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00'),
	(7,'Contributor','contributor','',2,0,0,0,0,'',1,7,1,430,'2016-08-08 12:00:00',0,'0000-00-00 00:00:00',0,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_positions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_reports
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(50) NOT NULL DEFAULT '',
  `description` mediumtext,
  `notes` mediumtext,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `department` (`department`),
  KEY `team` (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_reports` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_reports` DISABLE KEYS */;

INSERT INTO `vol_volunteers_reports` (`id`, `department`, `team`, `title`, `alias`, `description`, `notes`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`)
VALUES
	(1,1,0,'Report from the Frontend Department meeting June 2016','report-from-the-frontend-department-meeting-june-2','<p>Nulla facilisi. Morbi rhoncus, tortor ut suscipit finibus, est sem laoreet arcu, et ultrices neque nunc quis velit. Pellentesque ornare nibh sit amet venenatis ultrices. Nullam dictum lacus sed erat cursus, sed placerat velit blandit. Fusce vitae scelerisque quam. Quisque nec rutrum ante. Nulla sagittis velit sed aliquam maximus. Nam orci lacus, porttitor in tincidunt id, accumsan eu tortor. Suspendisse ac lacinia ante.</p>\r\n<p>Nulla commodo mattis sem, ac commodo est hendrerit quis. Curabitur sit amet rhoncus nibh. Phasellus diam nibh, faucibus quis ante quis, porttitor tincidunt quam. Mauris sit amet urna enim. Phasellus commodo est eu ante tincidunt, eu maximus ante aliquet. Pellentesque eleifend ligula id convallis pulvinar. Etiam et bibendum ipsum. Etiam sit amet justo justo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam convallis dolor nec mattis porttitor. Aliquam condimentum eros quis tempus euismod. Donec placerat elit aliquam enim efficitur lobortis. Phasellus at pretium leo. Sed blandit, urna quis commodo volutpat, magna metus eleifend turpis, non malesuada massa nibh sit amet nisi. Aliquam lobortis interdum tempor.</p>\r\n<p>Nunc sollicitudin massa ut lobortis aliquam. Sed ex risus, dapibus ut porta et, fermentum eget nunc. Donec iaculis condimentum sapien, eu gravida tortor pharetra id. Quisque rhoncus aliquam urna in pharetra. Sed a egestas dolor. Duis laoreet sagittis neque quis sollicitudin. Aenean est risus, ullamcorper a facilisis id, tristique eget lectus. Donec nisl nisl, pellentesque in interdum sit amet, molestie id dolor. Proin laoreet enim eget nibh varius luctus.</p>',NULL,1,1,1,2,'2016-06-01 12:00:00',0,'2016-08-21 16:24:07',0,'0000-00-00 00:00:00'),
	(2,1,0,'Report from the Frontend Department meeting July 2016','report-from-the-frontend-department-meeting-july-2','<p>Suspendisse id bibendum massa, et tempor turpis. Suspendisse rhoncus purus a augue rutrum efficitur. Nullam a vehicula est, a dapibus elit. Etiam arcu orci, mollis id varius in, elementum nec mi. Etiam scelerisque elit sed urna accumsan egestas. Donec rutrum lorem sit amet tincidunt pretium. Nam varius a ligula eget accumsan. Nam tincidunt ipsum felis, in porta nisi viverra et. Quisque in ultrices diam. Nulla facilisi. Vestibulum porta mi nec mollis euismod. Aenean ut dolor sapien. Nullam a velit fermentum, egestas est eu, ultricies mi. Nullam sagittis semper porttitor.</p>\r\n<p>Vivamus congue risus eget imperdiet congue. Phasellus sodales mauris in ex efficitur pellentesque. Nulla eleifend convallis arcu, in aliquet felis. Phasellus sit amet ex quis mauris pellentesque feugiat. Cras molestie dolor eget ex facilisis, non tempor purus posuere. Suspendisse eu leo at neque faucibus congue. Vestibulum dapibus erat arcu, a molestie sapien maximus at. Pellentesque ut ultrices neque. Praesent iaculis lorem sem, quis scelerisque nisl mollis at. Donec lobortis nunc sed tempor gravida. Pellentesque ultrices dolor sed lorem consequat molestie.</p>\r\n<p>Nulla rutrum faucibus lacus a tempor. Proin hendrerit, tellus quis viverra fringilla, lorem nulla laoreet purus, in maximus turpis odio at mauris. Phasellus aliquet id felis vestibulum accumsan. Vivamus ornare faucibus quam, efficitur faucibus metus mollis at. In gravida leo non mattis mattis. Vestibulum a sapien id metus consectetur varius. Pellentesque vulputate mauris eu fringilla consequat.</p>',NULL,1,1,2,4,'2016-07-03 12:00:00',1,'2016-08-21 16:27:31',0,'0000-00-00 00:00:00'),
	(3,2,0,'Report from the Backend Department meeting June 2016','report-from-the-backend-department-meeting-june-2','<p>Duis nec risus odio. Nam tincidunt turpis non massa suscipit, eu ultricies turpis finibus. Duis aliquet quis elit quis lacinia. Aenean volutpat libero vitae odio dictum tincidunt. Curabitur lacinia lobortis libero. Pellentesque tortor enim, molestie a ornare at, dignissim condimentum lectus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec commodo tincidunt tortor.</p>\r\n<p>Ut malesuada feugiat ornare. Integer ut libero dolor. Aenean suscipit vel leo sit amet vestibulum. Maecenas vel dolor et nunc rhoncus fermentum. Suspendisse sit amet erat auctor, fringilla dolor id, lobortis tortor. Fusce consectetur at diam ut finibus. Quisque semper commodo turpis, eget porta dui molestie sit amet. Praesent consectetur scelerisque ligula nec commodo. Aliquam erat volutpat. Etiam sed volutpat magna, et feugiat enim. Praesent sit amet ante vel lectus dignissim sollicitudin vel ut turpis. Aliquam imperdiet, arcu id sollicitudin imperdiet, est orci iaculis risus, ut consectetur ante justo nec urna. Praesent vulputate et arcu in molestie.</p>\r\n<p>Pellentesque cursus interdum quam, non convallis nunc efficitur ac. Ut rutrum, massa non suscipit interdum, eros lectus tempor felis, ut lacinia ex sem sit amet nunc. Quisque vitae leo a ante viverra fermentum a nec arcu. Ut gravida, enim eu dignissim interdum, augue libero consectetur turpis, eget mollis lorem sapien ac elit. Phasellus quis felis sit amet urna porta finibus vel quis risus. Cras mi urna, consequat quis porttitor vel, malesuada a arcu. Nam congue ex at purus commodo accumsan. Aenean odio nunc, tempus ut placerat ut, lobortis vitae ex. Donec convallis tortor ullamcorper, maximus nunc eu, ullamcorper tellus. Phasellus accumsan, mauris in facilisis pellentesque, turpis urna semper metus, molestie tristique diam mi vitae urna. Duis bibendum augue eu odio tempus auctor. Vivamus quis erat congue, tincidunt lorem vel, congue tortor. Pellentesque vel felis tortor.</p>',NULL,1,1,2,7,'2016-06-03 12:00:00',1,'2016-08-21 16:27:56',0,'0000-00-00 00:00:00'),
	(4,2,0,'Report from the Backend Department meeting July 2016','report-from-the-backend-department-meeting-july-2','<p>Nullam enim est, accumsan quis lorem id, aliquet venenatis neque. Vestibulum fringilla libero eu tellus ultrices pulvinar. Proin pharetra rutrum lectus, quis faucibus neque dictum nec. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean ornare sodales lacus ut venenatis. Nam viverra elit sit amet velit molestie auctor. Nam ante lorem, varius eu nisl at, lobortis consectetur lorem. Curabitur condimentum nisi purus, at pulvinar purus euismod a. Cras a sem eros. Mauris finibus euismod libero, vel iaculis dolor scelerisque sed. Sed hendrerit turpis id ligula elementum, vitae consectetur tellus luctus. Donec vulputate, justo quis luctus mattis, lectus nulla hendrerit leo, eu iaculis nisi massa a dui. Cras vulputate laoreet commodo. Cras at neque aliquet, eleifend nulla vel, tempus nunc.</p>\r\n<p>Proin sed augue nisi. Suspendisse potenti. Interdum et malesuada fames ac ante ipsum primis in faucibus. Cras pellentesque non urna et auctor. Nulla ac sapien porta neque dictum finibus. Aenean mauris ex, consequat in nisl id, dictum mattis augue. Nullam convallis consequat efficitur. Aenean tincidunt efficitur imperdiet. Morbi non tempus nisi. Nunc sapien dolor, pulvinar in tempus eu, fermentum nec lacus. Etiam sagittis imperdiet lorem, non condimentum risus accumsan a. Integer finibus tincidunt ex, ut rhoncus dolor faucibus vitae. Fusce vel sem in dolor commodo malesuada non sed arcu. In hac habitasse platea dictumst.</p>\r\n<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis dapibus odio nec vestibulum commodo. Donec magna velit, luctus a vulputate ut, interdum sed dolor. Fusce nulla mauris, porttitor congue felis vitae, vehicula volutpat diam. Ut id nulla vehicula, pellentesque magna ut, dignissim nunc. Fusce pretium lacus sed neque pulvinar, a mattis purus placerat. Proin at dolor fermentum elit imperdiet convallis. Morbi sodales turpis libero, nec commodo mi sollicitudin sed. Sed facilisis eu elit vel porttitor. Nullam vitae lectus sit amet enim fringilla ultrices id vitae odio. Aenean eu metus a ipsum cursus rutrum id nec nisi. Mauris pharetra leo id orci sodales tristique. Duis ut erat tortor. Vestibulum placerat metus non nibh rhoncus accumsan. Donec nec dignissim nunc, at convallis risus.</p>',NULL,1,1,2,9,'2016-07-01 12:00:00',1,'2016-08-21 16:26:59',0,'0000-00-00 00:00:00'),
	(5,0,1,'Extension Team Report','extension-team-report','<p>Duis facilisis, lacus vitae pellentesque tristique, dolor quam malesuada nulla, id aliquet mi magna fermentum neque. Maecenas placerat pulvinar faucibus. Phasellus in risus nec est scelerisque semper. Proin hendrerit id augue in sollicitudin. Mauris sit amet augue vitae erat feugiat ullamcorper. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nunc elit neque, molestie vitae leo et, mattis tempor mauris. Maecenas non augue placerat sapien volutpat efficitur. Suspendisse rhoncus tortor sem, in congue felis rhoncus id. In dui erat, gravida a eleifend sit amet, venenatis sagittis orci. Fusce convallis, leo at lacinia maximus, erat ex cursus ligula, vitae malesuada felis sapien mollis libero. Donec imperdiet auctor velit, at vulputate nisi vulputate eget. Etiam nec egestas leo.</p>\r\n<p>Sed mattis lorem ut vehicula euismod. Sed eget gravida dolor. Aliquam pretium mi vitae felis porta, ut maximus mi pulvinar. Duis aliquet tincidunt eros, nec aliquam sem pellentesque quis. Aliquam id hendrerit neque. Suspendisse potenti. Nullam ac justo congue eros ultricies scelerisque et vitae augue. Cras sed auctor turpis. Vestibulum a turpis in lectus luctus faucibus vel sit amet ligula. Suspendisse a augue consectetur, molestie ligula id, euismod mi. Quisque placerat aliquet ultrices. Interdum et malesuada fames ac ante ipsum primis in faucibus.</p>\r\n<p>Phasellus volutpat justo et magna mollis, vitae cursus turpis pulvinar. Nullam maximus ipsum non sapien egestas sodales. In sed leo tempor, vehicula neque eget, iaculis elit. Suspendisse convallis, leo in consectetur fermentum, turpis massa commodo nibh, ac cursus libero neque id turpis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Etiam dictum dolor id mattis tincidunt. Nunc rutrum, lectus in pretium iaculis, lectus tellus accumsan eros, at placerat mi eros eget purus. Vestibulum pellentesque aliquet pellentesque. Phasellus vitae magna scelerisque, ornare urna vel, laoreet est. Mauris at sapien sem.</p>','',1,2,2,10,'2016-08-11 20:31:00',1,'2016-08-21 20:32:40',0,'0000-00-00 00:00:00'),
	(6,0,1,'Team meeting report','team-meeting-report','<p>Etiam elementum sapien nec purus pulvinar, ac lobortis mauris placerat. In eleifend urna ex, ut rhoncus augue pellentesque vitae. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ac elit mauris. Quisque venenatis, arcu quis fermentum pellentesque, augue tellus porttitor est, id interdum nisl eros nec nisi. Mauris ligula dui, posuere quis mauris sit amet, luctus faucibus neque. Ut sit amet imperdiet massa. Aliquam sed aliquet ligula. Vestibulum volutpat diam vel arcu efficitur, in facilisis orci posuere. Sed rutrum non est at tristique.</p>\r\n<p>Vivamus finibus id est id porta. Donec mollis pretium ante, sed vulputate diam. Sed ut diam ac lacus suscipit rutrum a quis diam. Duis fermentum, mauris ut mattis luctus, ligula urna porttitor nunc, at commodo velit erat non nibh. Quisque aliquet dui elit, eu posuere tortor sodales rhoncus. Curabitur ut augue et est finibus facilisis vestibulum ac libero. Cras pharetra lacus varius quam maximus aliquet. Suspendisse in efficitur massa. Donec quis blandit arcu, non laoreet dolor. Pellentesque venenatis luctus sem nec euismod.</p>\r\n<p>Nulla varius venenatis dui non dapibus. Donec odio nunc, lacinia in ligula sed, efficitur scelerisque leo. Vestibulum quis turpis efficitur, lacinia sapien tincidunt, porttitor urna. Nam nec semper mi. Donec et justo vitae nisi dignissim aliquam sit amet a neque. Donec non ornare orci, in lobortis nulla. Donec sed scelerisque nunc. Aliquam at nunc sollicitudin, lacinia est a, maximus erat. Nulla posuere ligula magna, vulputate pellentesque ante dictum et. Sed ligula sapien, accumsan sit amet tellus sed, imperdiet sagittis urna. Phasellus pretium erat sit amet arcu dignissim malesuada vitae quis felis.</p>','',1,3,2,8,'2016-06-07 20:31:00',1,'2016-08-21 20:32:49',0,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_reports` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_roles
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` mediumtext,
  `open` tinyint(3) NOT NULL DEFAULT '1',
  `notes` mediumtext,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `team` (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_roles` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_roles` DISABLE KEYS */;

INSERT INTO `vol_volunteers_roles` (`id`, `team`, `title`, `alias`, `description`, `open`, `notes`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`)
VALUES
	(1,1,'Tester','tester','Vivamus mollis, justo nec semper vestibulum, elit nisl lobortis nibh, sit amet blandit massa quam a dolor. Aenean quis accumsan dui. Nunc tellus mi, vehicula vel dolor vitae, blandit fringilla nisi. Donec rutrum ultrices magna eu fermentum. Donec id leo ac dui fermentum vulputate et sit amet risus. Fusce dictum dolor a facilisis euismod. ',1,'',1,1,1,1,'2016-08-21 20:27:34',0,'2016-08-21 20:27:34',0,'0000-00-00 00:00:00'),
	(2,1,'Documentation','documentation','Praesent scelerisque dictum eleifend. Pellentesque viverra ante a convallis tempus. Morbi malesuada convallis metus ac elementum. Aliquam sapien justo, iaculis sit amet venenatis a, aliquam vitae erat. Mauris neque nisi, sagittis nec efficitur quis, tristique eget neque. Fusce gravida elit a justo semper iaculis. ',0,'',1,2,1,1,'2016-08-21 20:27:54',0,'2016-08-21 20:27:54',0,'0000-00-00 00:00:00'),
	(3,9,'Tester','tester','Praesent scelerisque dictum eleifend. Pellentesque viverra ante a convallis tempus. Morbi malesuada convallis metus ac elementum. Aliquam sapien justo, iaculis sit amet venenatis a, aliquam vitae erat. Mauris neque nisi, sagittis nec efficitur quis, tristique eget neque. Fusce gravida elit a justo semper iaculis. ',1,'',1,3,1,1,'2016-08-21 20:28:17',0,'2016-08-21 20:28:17',0,'0000-00-00 00:00:00'),
	(4,1,'Developer','developer','Phasellus mattis id lorem sed venenatis. Mauris placerat consequat lorem tincidunt cursus. Integer cursus accumsan nunc vitae egestas. Etiam maximus dui nunc, ut luctus lacus pulvinar et.',1,'',1,4,1,1,'2016-08-21 20:29:04',0,'2016-08-21 20:29:04',0,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_roles` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_teams
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(3) NOT NULL DEFAULT '0',
  `department` int(10) unsigned NOT NULL,
  `acronym` varchar(255) NOT NULL,
  `description` mediumtext,
  `email` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `getinvolved` mediumtext,
  `notes` mediumtext,
  `date_started` date NOT NULL,
  `date_ended` date NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ready_transition` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_teams` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_teams` DISABLE KEYS */;

INSERT INTO `vol_volunteers_teams` (`id`, `parent_id`, `title`, `alias`, `status`, `department`, `acronym`, `description`, `email`, `website`, `getinvolved`, `notes`, `date_started`, `date_ended`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`, `ready_transition`)
VALUES
	(1,0,'Extensions Team','extensions-team',1,2,'ET','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','extensions.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(2,1,'Components Team','components-team',1,2,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','components.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(3,1,'Plugins Team','plugins-team',1,2,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','plugins.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(4,1,'Modules Team','modules-team',1,2,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','modules.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(5,0,'Sample Data Team','sample-data-team',1,1,'SDT','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','sample.data.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(6,5,'Fruitshop Team','fruitshop-team',1,1,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','fruitshop.team@volunteers.joomla.org','http://www.joomla.org','','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(7,5,'Parks Team','parks-team',1,1,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','parks.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(8,0,'Templates Team','templates-team',1,2,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','templates.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(9,0,'Getting Started Team','getting-started-team',1,1,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','getting.started.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-08-08','0000-00-00',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
	(10,0,'Archived Team','archived-team',1,1,'','Vivamus eu urna ac dolor dapibus facilisis eu eget nisi. Sed vehicula rutrum maximus. Nunc ac felis at sapien bibendum faucibus ut eu augue. Mauris semper, urna id faucibus feugiat, quam risus commodo felis, at accumsan lacus neque sit amet augue. ','archived.team@volunteers.joomla.org','http://www.joomla.org','<p>Cras consequat leo dolor, at interdum tellus sodales a. Pellentesque eleifend auctor neque et dignissim. Pellentesque tincidunt tellus sit amet quam malesuada accumsan. Integer id ultrices turpis, nec cursus nunc. Etiam laoreet eros nec pellentesque luctus. Fusce molestie sem sit amet ex lobortis cursus. Nulla nibh magna, rhoncus eget ante sed, bibendum luctus eros. Fusce non libero ultricies quam dignissim vulputate. In eget arcu accumsan, sollicitudin augue ac, pretium dui. Donec imperdiet tincidunt lorem, vel sodales justo semper sed. Phasellus blandit luctus vestibulum. Sed varius libero a leo tristique, quis consectetur quam elementum. Proin molestie ante id elit euismod, sed placerat leo tempus.</p>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed quis turpis tempus, consequat metus id, dictum nisi. Suspendisse interdum, dui quis interdum vehicula, augue risus ullamcorper sem, sed auctor felis lacus sed elit. Mauris cursus id risus et vehicula. Aenean eget erat in dui sagittis molestie nec nec mi. Aenean quis quam mauris. Cras ac felis eget neque placerat tincidunt. Sed iaculis nisi et mauris semper lacinia. Sed pharetra viverra felis et laoreet.</p>','','2016-07-08','2016-08-08',1,1,3,1,'2016-08-08 12:00:00',1,'2016-08-21 19:31:52',0,'0000-00-00 00:00:00','0000-00-00 00:00:00');

/*!40000 ALTER TABLE `vol_volunteers_teams` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_volunteers_volunteers
# ------------------------------------------------------------

CREATE TABLE `vol_volunteers_volunteers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(50) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `intro` mediumtext,
  `joomlastory` mediumtext,
  `image` varchar(255) NOT NULL,
  `facebook` varchar(255) NOT NULL,
  `twitter` varchar(255) NOT NULL,
  `googleplus` varchar(255) NOT NULL,
  `linkedin` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `github` varchar(255) NOT NULL,
  `certification` varchar(255) NOT NULL,
  `peakon` tinyint(1) NOT NULL DEFAULT 1,
  `birthday` DATE  NOT NULL DEFAULT '0000-00-00',
  `notes` mediumtext NOT NULL,
  `spam` int(10) NOT NULL DEFAULT '0',
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `version` int(10) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` bigint(20) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `latitude` varchar(255) NOT NULL DEFAULT '',
  `longitude` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `vol_volunteers_volunteers` WRITE;
/*!40000 ALTER TABLE `vol_volunteers_volunteers` DISABLE KEYS */;

INSERT INTO `vol_volunteers_volunteers` (`id`, `user_id`, `firstname`, `lastname`, `alias`, `country`, `city`, `intro`, `joomlastory`, `image`, `facebook`, `twitter`, `googleplus`, `linkedin`, `website`, `github`, `notes`, `spam`, `state`, `ordering`, `version`, `created_by`, `created`, `modified_by`, `modified`, `checked_out`, `checked_out_time`, `latitude`, `longitude`)
VALUES
	(1,1,'Admin','Joomler','admin-joomler','NL','Amsterdam','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','7144_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,10,1,'2016-08-08 12:00:00',1,'2016-08-23 11:55:17',1,'2016-08-23 11:55:33','52.3702157','4.895167899999933'),
	(2,2,'Frontend','Leader','frontend-leader','US','New-York','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','70e0_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-08 12:00:00',1,'2016-08-21 16:01:30',0,'0000-00-00 00:00:00','40.7127837','-74.0059413'),
	(3,3,'Frontend','Assistant','frontend-assistant','US','San Francisco','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','c684_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-08 12:00:00',1,'2016-08-21 15:55:13',0,'0000-00-00 00:00:00','37.7749295','-122.4194155'),
	(4,4,'Frontend','Coordinator','frontend-coordinator','ZA','Cape Town','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','9dc0_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-08 12:00:00',1,'2016-08-23 18:37:18',0,'0000-00-00 00:00:00','-33.9248685','18.424055299999964'),
	(5,5,'Backend','Leader','backend-leader','GB','London','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','c520_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,12,1,'2016-08-08 12:00:00',1,'2016-08-23 12:00:11',0,'0000-00-00 00:00:00','51.5073509','-0.12775829999998223'),
	(6,6,'Backend','Assistant','backend-assistant','DE','Berlin','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','ec65_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',1,1,0,10,1,'2016-08-08 12:00:00',1,'2016-08-21 20:07:54',0,'0000-00-00 00:00:00','52.5200066','13.404954'),
	(7,7,'Backend','Coordinator','backend-coordinator','FR','Paris','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','a60f_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-08 12:00:00',1,'2016-08-21 20:07:34',0,'0000-00-00 00:00:00','48.856614','2.3522219'),
	(8,8,'Extensions','Leader','extensions-leader','CN','Shanghai','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','2656_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,12,1,'2016-08-08 12:00:00',1,'2016-08-21 20:07:04',0,'0000-00-00 00:00:00','31.230416','121.473701'),
	(9,9,'Extensions','Assistant','extensions-assistant','IT','Pisa','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','409c_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,10,1,'2016-08-08 12:00:00',1,'2016-08-21 20:06:39',0,'0000-00-00 00:00:00','43.7228386','10.4016888'),
	(10,10,'Extensions','Member','extensions-member','RU','Moscow','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra <a href=\"#\">nulla</a>. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc. Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','2332_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,13,1,'2016-08-08 12:00:00',10,'2016-08-23 18:24:58',0,'0000-00-00 00:00:00','55.755826','37.6173'),
	(11,11,'Extensions','Contributor','extensions-contributor','DK','Copenhagen','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','4b53_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',4,1,0,11,1,'2016-08-13 12:00:00',1,'2016-08-21 20:09:56',0,'0000-00-00 00:00:00','55.6760968','12.5683371'),
	(12,12,'Sample','Leader','sample-leader','ES','Barcelona','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','64bf_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,10,1,'2016-08-09 12:00:00',1,'2016-08-21 20:03:36',0,'0000-00-00 00:00:00','41.3850639','2.1734035'),
	(13,13,'Sample','Assistant','sample-assistant','AU','Sidney','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','4354_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-10 12:00:00',1,'2016-08-21 20:04:03',0,'0000-00-00 00:00:00','-33.8688197','151.2092955'),
	(14,14,'Sample','Member','sample-member','CR','San Jose','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','2f3e_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',0,1,0,9,1,'2016-08-11 12:00:00',1,'2016-08-21 20:04:27',0,'0000-00-00 00:00:00','9.9280694','-84.0907246'),
	(15,15,'Sample','Contributor','sample-contributor','BR','Rio de Janeiro','Pellentesque elementum blandit turpis, sed lacinia metus fermentum condimentum. Cras sit amet nulla faucibus, dapibus sem sed, scelerisque magna. Quisque efficitur lorem facilisis luctus imperdiet. Mauris at vestibulum nibh. Sed in aliquet neque. Cras id consequat risus, non malesuada velit. ','<p>Praesent est orci, maximus et nibh id, luctus pharetra nulla. Nulla consectetur dui neque, in lobortis tortor accumsan tincidunt. Donec dignissim turpis arcu. Quisque velit mi, porta eget felis ut, tincidunt porttitor mauris. Quisque ac libero nunc. Etiam nec sem hendrerit, semper leo eget, ultricies orci. Curabitur lobortis, diam et mollis laoreet, nibh augue facilisis lacus, eu aliquet massa magna eu lacus. Curabitur in congue dui, nec fringilla nulla. Vestibulum nulla ipsum, suscipit non neque eget, tincidunt iaculis sapien. Aenean sodales, arcu eget luctus porta, mauris sem fermentum arcu, non cursus ligula dui vel nunc.</p>\r\n<p>Duis mattis sem sit amet mi porta, id eleifend sapien condimentum. Sed vitae ipsum posuere, tristique massa vitae, porttitor nulla. Aliquam sit amet elit vel lacus cursus tempor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Proin in nisl et lorem ornare porta. Quisque ut tortor vitae turpis sollicitudin tempus non vitae massa. Quisque tincidunt hendrerit auctor. Phasellus rutrum, mi varius sollicitudin vehicula, libero augue mattis mi, nec vulputate metus mi at ipsum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Praesent velit magna, tristique et magna pharetra, imperdiet ornare sapien. Aliquam at eleifend odio. Aliquam ac nunc maximus, commodo urna ut, vestibulum felis. Sed augue justo, bibendum non eleifend at, vestibulum id mauris.</p>','3aa2_joomla.png','joomla','joomla','+Joomla','','www.joomla.org','joomla','',10,1,0,9,1,'2016-08-12 12:00:00',1,'2016-08-21 20:04:57',0,'0000-00-00 00:00:00','-22.9068467','-43.1728965');

/*!40000 ALTER TABLE `vol_volunteers_volunteers` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table vol_wf_profiles
# ------------------------------------------------------------

CREATE TABLE `vol_wf_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `users` text NOT NULL,
  `types` text NOT NULL,
  `components` text NOT NULL,
  `area` tinyint(3) NOT NULL,
  `device` varchar(255) NOT NULL,
  `rows` text NOT NULL,
  `plugins` text NOT NULL,
  `published` tinyint(3) NOT NULL,
  `ordering` int(11) NOT NULL,
  `checked_out` tinyint(3) NOT NULL,
  `checked_out_time` datetime NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `vol_wf_profiles` WRITE;
/*!40000 ALTER TABLE `vol_wf_profiles` DISABLE KEYS */;

INSERT INTO `vol_wf_profiles` (`id`, `name`, `description`, `users`, `types`, `components`, `area`, `device`, `rows`, `plugins`, `published`, `ordering`, `checked_out`, `checked_out_time`, `params`)
VALUES
	(1,'Default','Default Profile for all users','','8','',0,'desktop,tablet,phone','help,newdocument,undo,redo,spacer,bold,italic,underline,strikethrough,justifyfull,justifycenter,justifyleft,justifyright,spacer,blockquote,formatselect,styleselect,removeformat,cleanup;fontselect,fontsizeselect,forecolor,backcolor,spacer,clipboard,indent,outdent,lists,sub,sup,textcase,charmap,hr;directionality,fullscreen,preview,source,print,searchreplace,spacer,table;visualaid,visualchars,visualblocks,nonbreaking,style,xhtmlxtras,anchor,unlink,link,imgmanager,spellchecker,article','charmap,contextmenu,browser,inlinepopups,media,help,clipboard,searchreplace,directionality,fullscreen,preview,source,table,textcase,print,style,nonbreaking,visualchars,visualblocks,xhtmlxtras,imgmanager,anchor,link,spellchecker,article,lists,formatselect,styleselect,fontselect,fontsizeselect,fontcolor,hr',0,3,0,'0000-00-00 00:00:00',''),
	(2,'Front End','Sample Front-end Profile','','','',1,'desktop,tablet,phone','help,newdocument,undo,redo,spacer,bold,italic,underline,strikethrough,justifyfull,justifycenter,justifyleft,justifyright,spacer,formatselect,styleselect;clipboard,searchreplace,indent,outdent,lists,cleanup,charmap,removeformat,hr,sub,sup,textcase,nonbreaking,visualchars,visualblocks;fullscreen,preview,print,visualaid,style,xhtmlxtras,anchor,unlink,link,imgmanager,spellchecker,article','charmap,contextmenu,inlinepopups,help,clipboard,searchreplace,fullscreen,preview,print,style,textcase,nonbreaking,visualchars,visualblocks,xhtmlxtras,imgmanager,anchor,link,spellchecker,article,lists,formatselect,styleselect,hr',0,4,0,'0000-00-00 00:00:00',''),
	(3,'Blogger','Simple Blogging Profile','','1,9,8,2,10','',0,'desktop,tablet,phone','bold,italic,strikethrough,underline,lists,spacer,justifyleft,justifycenter,justifyright,spacer,formatselect,link,unlink,imgmanager,removeformat,spellchecker','lists,formatselect,link,imgmanager,spellchecker,contextmenu,inlinepopups,hr',1,2,0,'0000-00-00 00:00:00','{\"editor\":{\"toggle\":\"0\"},\"formatselect\":{\"blockformats\":\"p,h2,h3,code\"},\"imgmanager\":{\"tabs_rollover\":\"0\",\"tabs_advanced\":\"0\",\"attributes_border\":\"0\",\"folder_new\":\"0\",\"folder_delete\":\"0\",\"folder_rename\":\"0\",\"folder_move\":\"0\",\"file_delete\":\"0\",\"file_rename\":\"0\",\"file_move\":\"0\",\"dir\":\"images\\/reports\"}}'),
	(5,'Blogger - Admin','Simple Blogging Profile','','8,10','',2,'desktop,tablet,phone','bold,italic,strikethrough,underline,lists,spacer,justifyleft,justifycenter,justifyright,spacer,formatselect,link,unlink,imgmanager,removeformat,spellchecker','lists,formatselect,link,imgmanager,spellchecker,contextmenu,inlinepopups,hr',1,1,0,'0000-00-00 00:00:00','{\"editor\":{\"toggle\":\"1\"},\"formatselect\":{\"blockformats\":\"p,h2,h3,code\"}}'),
	(4,'Mobile','Sample Mobile Profile','','8','',0,'tablet,phone','undo,redo,spacer,bold,italic,underline,formatselect,spacer,justifyleft,justifycenter,justifyfull,justifyright,spacer,fullscreen,kitchensink;styleselect,lists,spellchecker,article,link,unlink','fullscreen,kitchensink,spellchecker,article,link,inlinepopups,lists,formatselect,styleselect',0,5,0,'0000-00-00 00:00:00','{\"editor\":{\"toolbar_theme\":\"mobile\",\"resizing\":\"0\",\"resize_horizontal\":\"0\",\"resizing_use_cookie\":\"0\",\"toggle\":\"0\",\"links\":{\"popups\":{\"default\":\"\",\"jcemediabox\":{\"enable\":\"0\"},\"window\":{\"enable\":\"0\"}}}}}');

/*!40000 ALTER TABLE `vol_wf_profiles` ENABLE KEYS */;
UNLOCK TABLES;
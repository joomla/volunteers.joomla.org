/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

--
-- Create the Profiles table
--
CREATE TABLE IF NOT EXISTS `#__akeebabackup_profiles` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `description` varchar(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `configuration` longtext COLLATE utf8mb4_unicode_ci,
    `filters` longtext COLLATE utf8mb4_unicode_ci,
    `quickicon` tinyint(3) NOT NULL DEFAULT '1',
    `access` int(11) NULL DEFAULT '1',
    PRIMARY KEY (`id`)
) ENGINE InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

--
-- Create the default backup profile
--
INSERT IGNORE INTO `#__akeebabackup_profiles`
    (`id`, `description`, `configuration`, `filters`, `quickicon`)
VALUES (1, 'Default Backup Profile', '', '', 1);

--
-- Create the backups table
--
CREATE TABLE IF NOT EXISTS `#__akeebabackup_backups` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `description` varchar(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `comment` longtext COLLATE utf8mb4_unicode_ci,
    `backupstart` timestamp NULL DEFAULT NULL,
    `backupend` timestamp NULL DEFAULT NULL,
    `status` enum('run','fail','complete') NOT NULL DEFAULT 'run',
    `origin` varchar(30) NOT NULL DEFAULT 'backend' COLLATE utf8mb4_unicode_ci,
    `type` varchar(30) NOT NULL DEFAULT 'full' COLLATE utf8mb4_unicode_ci,
    `profile_id` bigint(20) NOT NULL DEFAULT '1',
    `archivename` longtext COLLATE utf8mb4_unicode_ci,
    `absolute_path` longtext COLLATE utf8mb4_unicode_ci,
    `multipart` int(11) NOT NULL DEFAULT '0',
    `tag` varchar(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `backupid` varchar(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `filesexist` tinyint(3) NOT NULL DEFAULT '1',
    `remote_filename` varchar(1000) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `total_size` bigint(20) NOT NULL DEFAULT '0',
    `frozen` tinyint(1) NOT NULL DEFAULT '0',
    `instep` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_fullstatus` (`filesexist`,`status`),
    KEY `idx_stale` (`status`,`origin`)
) ENGINE InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

--
-- Create the custom storage table
--
CREATE TABLE IF NOT EXISTS `#__akeebabackup_storage` (
    `tag` varchar(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COLLATE utf8mb4_unicode_ci,
    `data` longtext COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`tag`(100))
) ENGINE InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

--
-- Create the common table for all Akeeba extensions.
--
-- This table is never uninstalled when uninstalling the extensions themselves.
--
CREATE TABLE IF NOT EXISTS `#__akeeba_common` (
    `key` VARCHAR(190) NOT NULL COLLATE utf8mb4_unicode_ci,
    `value` LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`key`(100))
)
ENGINE InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;
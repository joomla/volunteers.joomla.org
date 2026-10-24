-- Remove Akeeba Backup 8 (pkg_akeeba) and the FOF 3, FOF 4 and FEF frameworks it used, after installing Akeeba Backup 10 (pkg_akeebabackup).
-- Akeeba Backup 8 cannot be uninstalled on Joomla 6, its files are removed in the repository.
-- Run after migrating the profiles and backup records to Akeeba Backup 10:
--   php cli/joomla.php akeeba:migrate

-- The update sites of the old extensions
DELETE `us`, `use`
  FROM `#__update_sites` AS `us`
  JOIN `#__update_sites_extensions` AS `use` ON `use`.`update_site_id` = `us`.`update_site_id`
  JOIN `#__extensions` AS `e` ON `e`.`extension_id` = `use`.`extension_id`
 WHERE (`e`.`type` = 'package' AND `e`.`element` = 'pkg_akeeba')
    OR (`e`.`type` = 'component' AND `e`.`element` = 'com_akeeba')
    OR (`e`.`type` = 'file' AND `e`.`element` IN ('file_akeeba', 'file_fef', 'file_fof30', 'file_fof40'))
    OR (`e`.`type` = 'plugin' AND `e`.`folder` = 'installer' AND `e`.`element` = 'akeebabackup');

-- The administrator menu item and the asset of the old component
DELETE FROM `#__menu` WHERE `client_id` = 1 AND `link` = 'index.php?option=com_akeeba';
DELETE FROM `#__assets` WHERE `name` = 'com_akeeba';

-- The old extensions. Akeeba Backup 10 took over the quickicon, actionlog and backuponupdate plugins, these stay.
DELETE FROM `#__extensions`
 WHERE (`type` = 'package' AND `element` = 'pkg_akeeba')
    OR (`type` = 'component' AND `element` = 'com_akeeba')
    OR (`type` = 'file' AND `element` IN ('file_akeeba', 'file_fef', 'file_fof30', 'file_fof40'))
    OR (`type` = 'plugin' AND `folder` = 'installer' AND `element` = 'akeebabackup');

-- The framework records of FOF and FEF, the table itself is still used by Akeeba Backup 10
DELETE FROM `#__akeeba_common` WHERE `key` IN ('fof30', 'fof40', 'file_fef');

-- The tables of Akeeba Backup 8, akeeba:migrate copied them to the #__akeebabackup_* tables
DROP TABLE IF EXISTS `#__ak_profiles`, `#__ak_stats`, `#__ak_storage`;

-- Clean up after updating RO Single Sign On (pkg_sso) from 1.3.0 to 2.3.1.

-- SSO - Joomla (plg_sso_joomla) is not part of the package and RO Single Sign On 2 no longer triggers its onProcessUserGetFieldMap event.
-- Its files are removed in the repository.
DELETE `use`
  FROM `#__update_sites_extensions` AS `use`
  JOIN `#__extensions` AS `e` ON `e`.`extension_id` = `use`.`extension_id`
 WHERE `e`.`type` = 'plugin' AND `e`.`folder` = 'sso' AND `e`.`element` = 'joomla';

DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'sso' AND `element` = 'joomla';

-- RO Single Sign On 2 maintains its tables in the install script and has no SQL update files, remove the schema version of 1.3.0
DELETE `s`
  FROM `#__schemas` AS `s`
  JOIN `#__extensions` AS `e` ON `e`.`extension_id` = `s`.`extension_id`
 WHERE `e`.`type` = 'component' AND `e`.`element` = 'com_sso';

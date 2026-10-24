-- The System - Joomla Volunteer Portal Identity plugin (plg_system_joomlaidentityvolunteers) is no longer part of the Joomla Volunteer Portal package.
-- It is installed from the identity.joomla.org repository, together with the System - Joomla Identity Client plugin.
UPDATE `#__extensions` SET `package_id` = 0
 WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'joomlaidentityvolunteers';

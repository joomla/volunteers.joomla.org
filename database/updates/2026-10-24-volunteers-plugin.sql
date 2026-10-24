-- Enable the System - Volunteers plugin (plg_system_volunteers) as on the live site.
-- It redirects old Joomler URLs to the right URL and marks My Profile as active on the own Joomler page.
UPDATE `#__extensions` SET `enabled` = 1
 WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'volunteers';

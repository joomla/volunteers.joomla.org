-- Copy the data that changes on the live site from the Joomla 3 database into the migrated database.
-- Run this while connected to the migrated database. Replace #__ with the table prefix and
-- volj_live with the name of the Joomla 3 database.
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- Users, Joomla 4 and later store an empty date as NULL
DELETE FROM `#__users`;
INSERT INTO `#__users` (`id`, `name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `activation`, `params`, `lastResetTime`, `resetCount`, `otpKey`, `otep`, `requireReset`, `authProvider`)
SELECT `id`, `name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, NULLIF(`lastvisitDate`, '0000-00-00 00:00:00'), `activation`, `params`, NULLIF(`lastResetTime`, '0000-00-00 00:00:00'), `resetCount`, `otpKey`, `otep`, `requireReset`, `authProvider`
FROM `volj_live`.`#__users`;

DELETE FROM `#__user_usergroup_map`;
INSERT INTO `#__user_usergroup_map` SELECT * FROM `volj_live`.`#__user_usergroup_map`;

-- Keep the profile values of Joomla 6, such as the guided tours and API tokens
INSERT IGNORE INTO `#__user_profiles` SELECT * FROM `volj_live`.`#__user_profiles`;

DELETE FROM `#__user_notes`;
INSERT INTO `#__user_notes` (`id`, `user_id`, `catid`, `subject`, `body`, `state`, `checked_out`, `checked_out_time`, `created_user_id`, `created_time`, `modified_user_id`, `modified_time`, `review_time`, `publish_up`, `publish_down`)
SELECT `id`, `user_id`, `catid`, `subject`, `body`, `state`, NULLIF(`checked_out`, 0), NULLIF(`checked_out_time`, '0000-00-00 00:00:00'), `created_user_id`, `created_time`, `modified_user_id`, IF(`modified_time` = '0000-00-00 00:00:00', `created_time`, `modified_time`), NULLIF(`review_time`, '0000-00-00 00:00:00'), NULLIF(`publish_up`, '0000-00-00 00:00:00'), NULLIF(`publish_down`, '0000-00-00 00:00:00')
FROM `volj_live`.`#__user_notes`;

-- Joomla Volunteer Portal, the tables are the same as on Joomla 3
DELETE FROM `#__volunteers_departments`;
INSERT INTO `#__volunteers_departments` SELECT * FROM `volj_live`.`#__volunteers_departments`;

DELETE FROM `#__volunteers_members`;
INSERT INTO `#__volunteers_members` SELECT * FROM `volj_live`.`#__volunteers_members`;

DELETE FROM `#__volunteers_positions`;
INSERT INTO `#__volunteers_positions` SELECT * FROM `volj_live`.`#__volunteers_positions`;

DELETE FROM `#__volunteers_reports`;
INSERT INTO `#__volunteers_reports` SELECT * FROM `volj_live`.`#__volunteers_reports`;

DELETE FROM `#__volunteers_roles`;
INSERT INTO `#__volunteers_roles` SELECT * FROM `volj_live`.`#__volunteers_roles`;

DELETE FROM `#__volunteers_teams`;
INSERT INTO `#__volunteers_teams` SELECT * FROM `volj_live`.`#__volunteers_teams`;

DELETE FROM `#__volunteers_volunteers`;
INSERT INTO `#__volunteers_volunteers` SELECT * FROM `volj_live`.`#__volunteers_volunteers`;

-- Privacy
DELETE FROM `#__privacy_consents`;
INSERT INTO `#__privacy_consents` SELECT * FROM `volj_live`.`#__privacy_consents`;

DELETE FROM `#__privacy_requests`;
INSERT INTO `#__privacy_requests` (`id`, `email`, `requested_at`, `status`, `request_type`, `confirm_token`, `confirm_token_created_at`)
SELECT `id`, `email`, `requested_at`, `status`, `request_type`, `confirm_token`, NULLIF(`confirm_token_created_at`, '0000-00-00 00:00:00')
FROM `volj_live`.`#__privacy_requests`;

-- User actions log, replaces the log entries of the local migration
DELETE FROM `#__action_logs`;
INSERT INTO `#__action_logs` SELECT * FROM `volj_live`.`#__action_logs`;

COMMIT;

-- Sessions and remember me keys of the local site
TRUNCATE TABLE `#__session`;
TRUNCATE TABLE `#__user_keys`;

SET FOREIGN_KEY_CHECKS = 1;

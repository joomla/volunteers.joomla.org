--
-- Joomla 4+ strict SQL mode support, mirroring the core 4.0.0 schema changes
-- (com_admin/sql/updates/mysql/4.0.0-2019-09-22.sql and 4.0.0-2020-05-29.sql):
--
-- * created:  NOT NULL without default, zero dates become '1980-01-01 00:00:00'
-- * modified: NOT NULL without default, zero dates are copied from created
-- * optional dates (date_started, date_ended, birthday, checked_out_time, ready_transition): NULL, zero dates become NULL
-- * checked_out: nullable INT UNSIGNED, 0 becomes NULL
-- * optional integer and varchar columns get DEFAULT 0 / DEFAULT '', optional text columns become nullable
--

-- Joomla's own session mode, which allows the zero dates that are converted below
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

--
-- Departments
--
ALTER TABLE `#__volunteers_departments`
  MODIFY `parent_id` INT NOT NULL DEFAULT 0,
  MODIFY `website` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `email` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_departments` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_departments` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_departments` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_departments` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

--
-- Members
--
ALTER TABLE `#__volunteers_members`
  MODIFY `department` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `team` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `volunteer` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `position` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `role` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `role_old` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `date_started` DATE NULL DEFAULT NULL,
  MODIFY `date_ended` DATE NULL DEFAULT NULL,
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_members` SET `date_started` = NULL WHERE `date_started` = '0000-00-00';
UPDATE `#__volunteers_members` SET `date_ended` = NULL WHERE `date_ended` = '0000-00-00';
UPDATE `#__volunteers_members` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_members` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_members` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_members` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

--
-- Positions
--
ALTER TABLE `#__volunteers_positions`
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_positions` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_positions` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_positions` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_positions` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

--
-- Reports
--
ALTER TABLE `#__volunteers_reports`
  MODIFY `department` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `team` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_reports` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_reports` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_reports` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_reports` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

--
-- Roles
--
ALTER TABLE `#__volunteers_roles`
  MODIFY `department` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `team` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_roles` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_roles` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_roles` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_roles` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

--
-- Teams
--
ALTER TABLE `#__volunteers_teams`
  MODIFY `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `department` INT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY `acronym` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `email` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `website` VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY `date_started` DATE NULL DEFAULT NULL,
  MODIFY `date_ended` DATE NULL DEFAULT NULL,
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL,
  MODIFY `ready_transition` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_teams` SET `date_started` = NULL WHERE `date_started` = '0000-00-00';
UPDATE `#__volunteers_teams` SET `date_ended` = NULL WHERE `date_ended` = '0000-00-00';
UPDATE `#__volunteers_teams` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_teams` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_teams` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_teams` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_teams` SET `ready_transition` = NULL WHERE `ready_transition` = '0000-00-00 00:00:00';

--
-- Volunteers
--
ALTER TABLE `#__volunteers_volunteers`
  MODIFY `notes` MEDIUMTEXT,
  MODIFY `birthday` DATE NULL DEFAULT NULL,
  MODIFY `created` DATETIME NOT NULL,
  MODIFY `modified` DATETIME NOT NULL,
  MODIFY `checked_out` INT UNSIGNED,
  MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;

UPDATE `#__volunteers_volunteers` SET `birthday` = NULL WHERE `birthday` = '0000-00-00';
UPDATE `#__volunteers_volunteers` SET `created` = '1980-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_volunteers` SET `modified` = `created`, `modified_by` = `created_by` WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `#__volunteers_volunteers` SET `checked_out` = NULL WHERE `checked_out` = 0;
UPDATE `#__volunteers_volunteers` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';

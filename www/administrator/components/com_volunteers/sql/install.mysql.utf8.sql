/*Table structure for table `#__volunteers_departments` */

CREATE TABLE IF NOT EXISTS `#__volunteers_departments`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `parent_id`        int unsigned NOT NULL,
    `title`            varchar(255) NOT NULL,
    `alias`            varchar(50)  NOT NULL DEFAULT '',
    `description`      mediumtext,
    `website`          varchar(255) NOT NULL,
    `email`            varchar(255) NOT NULL,
    `notes`            mediumtext,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `version`          int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_members` */

CREATE TABLE IF NOT EXISTS `#__volunteers_members`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `department`       int unsigned NOT NULL,
    `team`             int unsigned NOT NULL,
    `volunteer`        int unsigned NOT NULL,
    `position`         int unsigned NOT NULL,
    `role`             int unsigned NOT NULL,
    `role_old`         varchar(255) NOT NULL,
    `date_started`     date         NOT NULL,
    `date_ended`       date         NOT NULL,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    KEY `department` (`department`),
    KEY `team` (`team`),
    KEY `volunteer` (`volunteer`),
    KEY `position` (`position`),
    KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_positions` */

CREATE TABLE IF NOT EXISTS `#__volunteers_positions`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `title`            varchar(255) NOT NULL,
    `alias`            varchar(50)  NOT NULL DEFAULT '',
    `description`      mediumtext,
    `type`             tinyint      NOT NULL DEFAULT '0',
    `edit_department`  tinyint      NOT NULL DEFAULT '0',
    `edit`             tinyint      NOT NULL DEFAULT '0',
    `create_report`    tinyint      NOT NULL DEFAULT '0',
    `create_team`      tinyint      NOT NULL DEFAULT '0',
    `notes`            mediumtext,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `version`          int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_reports` */

CREATE TABLE IF NOT EXISTS `#__volunteers_reports`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `department`       int unsigned NOT NULL,
    `team`             int unsigned NOT NULL,
    `title`            varchar(255) NOT NULL,
    `alias`            varchar(50)  NOT NULL DEFAULT '',
    `description`      mediumtext,
    `notes`            mediumtext,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `version`          int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    KEY `department` (`department`),
    KEY `team` (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_roles` */

CREATE TABLE IF NOT EXISTS `#__volunteers_roles`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `department`       int unsigned NOT NULL,
    `team`             int unsigned NOT NULL,
    `title`            varchar(255) NOT NULL,
    `alias`            varchar(255) NOT NULL,
    `description`      mediumtext,
    `open`             tinyint      NOT NULL DEFAULT '1',
    `notes`            mediumtext,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `version`          int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    KEY `team` (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_teams` */

CREATE TABLE IF NOT EXISTS `#__volunteers_teams`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `parent_id`        int unsigned NOT NULL,
    `title`            varchar(255) NOT NULL,
    `alias`            varchar(50)  NOT NULL DEFAULT '',
    `status`           tinyint      NOT NULL DEFAULT '0',
    `department`       int unsigned NOT NULL,
    `acronym`          varchar(255) NOT NULL,
    `description`      mediumtext,
    `email`            varchar(255) NOT NULL,
    `website`          varchar(255) NOT NULL,
    `getinvolved`      mediumtext,
    `notes`            mediumtext,
    `date_started`     date         NOT NULL,
    `date_ended`       date         NOT NULL,
    `state`            tinyint      NOT NULL DEFAULT '1',
    `ordering`         int          NOT NULL DEFAULT '0',
    `version`          int          NOT NULL DEFAULT '0',
    `created_by`       bigint       NOT NULL DEFAULT '0',
    `created`          datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`      bigint       NOT NULL DEFAULT '0',
    `modified`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`      bigint       NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ready_transition` datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `#__volunteers_volunteers` */

CREATE TABLE IF NOT EXISTS `#__volunteers_volunteers`
(
    `id`                  int unsigned NOT NULL AUTO_INCREMENT,
    `user_id`             int          NOT NULL,
    `firstname`           varchar(255) NOT NULL DEFAULT '',
    `lastname`            varchar(255) NOT NULL DEFAULT '',
    `alias`               varchar(50)  NOT NULL DEFAULT '',
    `address`             varchar(255) NOT NULL DEFAULT '',
    `city`                varchar(255) NOT NULL DEFAULT '',
    `city-location`       varchar(255) NOT NULL DEFAULT '',
    `region`              varchar(255) NOT NULL DEFAULT '',
    `zip`                 varchar(255) NOT NULL DEFAULT '',
    `country`             varchar(255) NOT NULL DEFAULT '',
    `intro`               mediumtext,
    `joomlastory`         mediumtext,
    `image`               varchar(255) NOT NULL DEFAULT '',
    `facebook`            varchar(255) NOT NULL DEFAULT '',
    `twitter`             varchar(255) NOT NULL DEFAULT '',
    `googleplus`          varchar(255) NOT NULL DEFAULT '',
    `linkedin`            varchar(255) NOT NULL DEFAULT '',
    `website`             varchar(255) NOT NULL DEFAULT '',
    `github`              varchar(255) NOT NULL DEFAULT '',
    `certification`       varchar(255) NOT NULL DEFAULT '',
    `stackexchange`       varchar(255) NOT NULL DEFAULT '',
    `joomlastackexchange` varchar(255) NOT NULL DEFAULT '',
    `joomlaforum`         varchar(255) NOT NULL DEFAULT '',
    `joomladocs`          varchar(255) NOT NULL DEFAULT '',
    `crowdin`             varchar(255) NOT NULL DEFAULT '',
    `peakon`              int          NOT NULL DEFAULT '1',
    `birthday`            date         NOT NULL DEFAULT '0000-00-00',
    `notes`               mediumtext   NOT NULL,
    `spam`                int          NOT NULL DEFAULT '0',
    `state`               int          NOT NULL DEFAULT '1',
    `latitude`            varchar(255) NOT NULL DEFAULT '',
    `longitude`           varchar(255) NOT NULL DEFAULT '',
    `ordering`            int          NOT NULL DEFAULT '0',
    `version`             int          NOT NULL DEFAULT '0',
    `created_by`          bigint       NOT NULL DEFAULT '0',
    `created`             datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`         bigint       NOT NULL DEFAULT '0',
    `modified`            datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `checked_out`         bigint       NOT NULL DEFAULT '0',
    `checked_out_time`    datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `email_feed`          int          NOT NULL DEFAULT '0',
    `send_permission`     int          NOT NULL DEFAULT '0',
    `coc`                 int          NOT NULL DEFAULT '0',
    `jca`                 int          NOT NULL DEFAULT '0',
    `osmAddress`          int          NOT NULL DEFAULT '0',
    `nda`                 int          NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

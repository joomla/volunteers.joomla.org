CREATE TABLE IF NOT EXISTS `#__sso_profiles` (
  `id`               INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100)        NOT NULL,
  `alias`            VARCHAR(255)        NOT NULL,
  `provider`         VARCHAR(50)         NOT NULL,
  `params`           TEXT                NOT NULL,
  `fieldmap`         TEXT                NOT NULL,
  `ordering`         TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `published`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created`          DATETIME            NOT NULL DEFAULT '1001-01-01 00:00:00',
  `created_by`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `modified`         DATETIME            NOT NULL DEFAULT '1001-01-01 00:00:00',
  `modified_by`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME            NOT NULL DEFAULT '1001-01-01 00:00:00',
  `checked_out`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  CHARSET = utf8
  COMMENT = 'Profiles with SSO settings';

CREATE TABLE IF NOT EXISTS `#__kvstore` (
  `_type`   varchar(30) NOT NULL,
  `_key`    varchar(50) NOT NULL,
  `_value`  longtext    NOT NULL,
  `_expire` timestamp   NOT NULL DEFAULT CURRENT_TIMESTAMP
  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`_key`, `_type`),
  KEY `#__kvstore_expire` (`_expire`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `#__saml_LogoutStore` (
  `_authSource`   varchar(255) NOT NULL,
  `_nameId`       varchar(40) NOT NULL,
  `_sessionIndex` varchar(50) NOT NULL,
  `_expire`       timestamp   NOT NULL DEFAULT CURRENT_TIMESTAMP
  ON UPDATE CURRENT_TIMESTAMP,
  `_sessionId`    varchar(50) NOT NULL,
  UNIQUE KEY `_authSource` (`_authSource`, `_nameId`, `_sessionIndex`),
  KEY `#__saml_LogoutStore_expire` (`_expire`),
  KEY `#__saml_LogoutStore_nameId` (`_authSource`, `_nameId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `#__tableVersion` (
  `_name`    varchar(30) NOT NULL,
  `_version` int(11)     NOT NULL,
  UNIQUE KEY `_name` (`_name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

TRUNCATE TABLE `#__tableVersion`;

INSERT INTO `#__tableVersion` (`_name`, `_version`)
VALUES ('kvstore', 1),
       ('saml_LogoutStore', 2);
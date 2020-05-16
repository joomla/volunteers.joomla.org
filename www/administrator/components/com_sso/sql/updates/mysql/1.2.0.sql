TRUNCATE TABLE `#__tableVersion`;

INSERT INTO `#__tableVersion` (`_name`, `_version`)
VALUES ('kvstore', 2),
       ('saml_LogoutStore', 4);

ALTER TABLE `#__saml_LogoutStore` MODIFY COLUMN `_authSource` varchar(191) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `#__saml_LogoutStore` MODIFY COLUMN `_expire` DATETIME DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL;
ALTER TABLE `#__saml_LogoutStore` DROP KEY `_authSource`;
ALTER TABLE `#__saml_LogoutStore` ADD CONSTRAINT `_authsource` UNIQUE KEY (`_authSource`(191),`_nameId`,`_sessionIndex`);

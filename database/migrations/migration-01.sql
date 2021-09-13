-- DB Migration for supporting additional license managers.

ALTER TABLE `servers` CHANGE `lmgrd_version` `version` VARCHAR(15) NULL;
ALTER TABLE `servers` ADD `license_manager` VARCHAR(25) NOT NULL AFTER `status`;
UPDATE `servers` SET `license_manager` = 'flexlm';

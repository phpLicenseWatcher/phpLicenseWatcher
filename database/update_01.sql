-- -----------------------------------------------------
-- Schema phplw (update 01)
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `server` -> `servers`
-- -----------------------------------------------------
ALTER TABLE `server`
    RENAME TO `servers`,
    ADD COLUMN `is_active` TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN `notes` TEXT,
    ADD COLUMN `lmgrd_version` VARCHAR(20),
    ADD COLUMN `last_updated` DATETIME,
    ADD UNIQUE INDEX `ck_name_alias_isactive` (`name`, `alias`, `is_active`),
    ENGINE = InnoDB,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

UPDATE `servers`
    INNER JOIN `server_status` ON `servers`.`id`=`server_status`.`server_id`
    SET `servers`.`lmgrd_version`=`server_status`.`lmgrd_version`, `servers`.`last_updated`=`server_status`.`last_updated`
    WHERE `servers`.`id`=`server_status`.`server_id`;

-- -----------------------------------------------------
-- Table `feature` -> `features`
-- -----------------------------------------------------
ALTER TABLE `feature`
    RENAME TO `features`
    CHANGE COLUMN `featureID` `id` INT NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `showInLists` `show_in_lists` TINYINT NOT NULL,
    ADD UNIQUE INDEX `ck_serverid_feature` (`server_id`, `feature`),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `licenses_available` -> `available`
-- -----------------------------------------------------

-- Refactor `licenses_available` -> `available`
ALTER TABLE `licenses_available`
    RENAME TO `available`,
    ADD COLUMN `server_id` INT FIRST,
    CHANGE COLUMN `flmavailable_date` `date` DATE NOT NULL,
    CHANGE COLUMN `flmavailable_product` `product` VARCHAR(100) NOT NULL AFTER `server_id`,
    CHANGE COLUMN `flmavailable_num_licenses` `num_licenses` INT NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Make sure servers in `available` also exist in `servers`.
INSERT INTO `servers` (`name`, `alias`, `is_active`)
    SELECT DISTINCT `available`.`flmavailable_server`, replace(`available`.`flmavailable_server`, '.', '_'), 0
    FROM `available`
    LEFT JOIN `servers` ON `available`.`flmavailable_server`=`servers`.`name`
    WHERE `servers`.`name` IS NULL;

-- Make sure id's in `servers` also exist in `available`
UPDATE `available`
INNER JOIN `servers` ON `available`.`flmavailable_server`=`servers`.`name`
SET `available`.`server_id`=`servers`.`id`
WHERE `available`.`flmavailable_server`=`servers`.`name`;

-- Fix primary key, establish foreign key
ALTER TABLE `available`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`server_id`, `date`, `product`, `num_licenses`),
    DROP COLUMN `flmavailable_server`,
    ADD INDEX `fk_available_servers_idx` (`server_id` ASC),
    ADD INDEX `fk_available_features` ('product' ASC),
    ADD CONSTRAINT `fk_available_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_available_features`
        FOREIGN KEY (`product`)
        REFERENCES `features` (`feature`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- -----------------------------------------------------
-- Table `license_usage` -> `usage`
-- -----------------------------------------------------

-- Combine date and time columns to a single datetime column
ALTER TABLE `license_usage`
    ADD COLUMN `time` datetime NOT NULL DEFAULT now() FIRST;

UPDATE `license_usage` SET `time` = timestamp(`flmusage_date`, `flmusage_time`);

-- Refactor `license_usage` -> `usage`
ALTER TABLE `license_usage`
    RENAME TO `usage`,
    ADD COLUMN `server_id` INT NOT NULL FIRST,
    CHANGE COLUMN `flmusage_product` `product` VARCHAR(100) NOT NULL AFTER `server_id`,
    CHANGE COLUMN `flmusage_users` `users` INT NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Make sure servers in `usage` also exist in `servers`.
INSERT INTO `servers` (`name`, `alias`, `is_active`)
    SELECT DISTINCT `usage`.`flmusage_server`, replace(`usage`.`flmusage_server`, '.', '_'), 1
    FROM `usage`
    LEFT JOIN `servers` ON `usage`.`flmusage_server`=`servers`.`name`
    WHERE `servers`.`name` IS NULL;

-- Make sure id's in `servers` also exist in `usage`
UPDATE `usage`
INNER JOIN `servers` ON `usage`.`flmusage_server`=`servers`.`name`
SET `usage`.`server_id`=`servers`.`id`
WHERE `usage`.`flmusage_server`=`servers`.`name`;

-- Fix primary key, establish foreign key
ALTER TABLE `usage`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`server_id`, `time`, `product`),
    DROP COLUMN `flmusage_server`,
    DROP COLUMN `flmusage_date`,
    DROP COLUMN `flmusage_time`,
    ADD INDEX `fk_usage_servers_idx` (`server_id` ASC),
    ADD INDEX `fk_usage_features_idx` (`product` ASC),
    ADD CONSTRAINT `fk_usage_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_usage_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- -----------------------------------------------------
-- Table `events`
-- This is to replace the old `flexlm_events` table.
-- History is not being preserved.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `flexlm_events`;
DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
    `server_id` INT NOT NULL,
    `feature` VARCHAR(100) NOT NULL,
    `time` DATETIME NOT NULL,
    `user` VARCHAR(80) NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `reason` TEXT NOT NULL,
    PRIMARY KEY (`server_id`, `feature`, `time`, `user`),
    INDEX `fk_events_features_idx` (`feature` ASC),
    INDEX `fk_events_servers_idx` (`server_id` ASC),
    CONSTRAINT `fk_events_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_events_features`
        FOREIGN KEY (`feature`)
        REFERENCES `features` (`feature`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `server_status`
-- Relevant data is merged into `servers` table.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `server_status`;

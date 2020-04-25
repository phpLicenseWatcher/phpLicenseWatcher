-- -----------------------------------------------------
-- Schema phplw (update 01)
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `server` -> `servers`
-- -----------------------------------------------------

-- Deduplicate entries in `server`
DELETE `t1` FROM `server` `t1`
    INNER JOIN `server` `t2`
    WHERE `t1`.`id` < `t2`.`id` AND `t1`.`name`=`t2`.`name` AND `t1`.`alias`=`t2`.`alias`;

-- Refactor `server` table.
ALTER TABLE `server`
    RENAME TO `servers`,
    MODIFY COLUMN `alias` VARCHAR(100) NOT NULL,
    ADD COLUMN `is_active` TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN `notes` TEXT,
    ADD COLUMN `lmgrd_version` TEXT,
    ADD COLUMN `last_updated` DATETIME DEFAULT now(),
    ADD UNIQUE INDEX `name_alias_isactive_UNIQUE` (`name`, `alias`),
    ENGINE = InnoDB,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Merge relevant data from `server_status` table.
UPDATE `servers`
    INNER JOIN `server_status` ON `servers`.`id`=`server_status`.`server_id`
    SET `servers`.`lmgrd_version`=`server_status`.`lmgrd_version`, `servers`.`last_updated`=`server_status`.`last_updated`
    WHERE `servers`.`id`=`server_status`.`server_id`;

-- `server_status` table is no longer needed.
DROP TABLE IF EXISTS `server_status`;

-- -----------------------------------------------------
-- Table `feature` -> `features`
-- -----------------------------------------------------

-- Deduplicate entries in `feature`
DELETE `t1` FROM `feature` `t1`
    INNER JOIN `feature` `t2`
    WHERE `t1`.`featureID` < `t2`.`featureID` AND `t1`.`feature` = `t2`.`feature`;

-- Refactor `feature` table
ALTER TABLE `feature`
    RENAME TO `features`,
    CHANGE COLUMN `featureID` `id` INT NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `showInLists` `show_in_lists` TINYINT NOT NULL,
    CHANGE COLUMN `feature` `name` VARCHAR(100) NOT NULL AFTER `id`,
    ADD UNIQUE INDEX `name_UNIQUE` (`name` ASC),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `licenses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `licenses` ;

CREATE TABLE IF NOT EXISTS `licenses` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `server_id` INT NOT NULL,
    `feature_id` INT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `serverid_featureid_UNIQUE` (`server_id` ASC, `feature_id` ASC),
    INDEX `fk_licenses_features1_idx` (`feature_id` ASC),
    CONSTRAINT `fk_licenses_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_licenses_features`
        FOREIGN KEY (`feature_id`)
        REFERENCES `features` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_bin;

-- -----------------------------------------------------
-- Table `licenses_available` -> `available`
-- -----------------------------------------------------

-- Refactor `licenses_available` -> `available`
ALTER TABLE `licenses_available`
    RENAME TO `available`,
    ADD COLUMN `license_id` INT FIRST,
    CHANGE COLUMN `flmavailable_date` `date` DATE NOT NULL,
    CHANGE COLUMN `flmavailable_num_licenses` `num_licenses` INT NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Make sure servers in `available` also exist in `servers`.
-- These servers will default to being inactive.
INSERT IGNORE INTO `servers` (`name`, `alias`, `is_active`)
    SELECT DISTINCT `available`.`flmavailable_server`, replace(`available`.`flmavailable_server`, '.', '_'), 0
    FROM `available`
    LEFT JOIN `servers` ON `available`.`flmavailable_server`=`servers`.`name`
    WHERE `servers`.`name` IS NULL;

-- Make sure products in `available` also exist in `features`.
INSERT IGNORE INTO `features` (`name`, `show_in_lists`)
    SELECT DISTINCT `available`.`flmavailable_product`, 1
    FROM `available`
    LEFT JOIN `features` ON `available`.`flmavailable_product`=`features`.`name`
    WHERE `features`.`name` IS NULL;

-- Make sure all `servers` and `features` are added to `licenses`.
INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id`
    FROM `available`
    JOIN `servers` ON `available`.`flmavailable_server`=`servers`.`name`
    JOIN `features` ON `available`.`flmavailable_product`=`features`.`name`
    WHERE `servers`.`name`=`available`.`flmavailable_server` AND `features`.`name`=`available`.`flmavailable_product`;

-- Now INSERT those license ids back into `available`
UPDATE `available`
    JOIN `servers` ON `servers`.`name`=`available`.`flmavailable_server`
    JOIN `features` ON `features`.`name`=`available`.`flmavailable_product`
    JOIN `licenses` ON `licenses`.`server_id`=`servers`.`id` AND `licenses`.`feature_id`=`features`.`id`
SET `license_id`=`licenses`.`id`
WHERE `licenses`.`server_id`=`servers`.`id` AND `available`.`flmavailable_server`=`servers`.`name`
    AND `licenses`.`feature_id`=`features`.`id` AND `available`.`flmavailable_product`=`features`.`name`;

-- Fix primary key, establish foreign key
ALTER TABLE `available`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`license_id`, `date`, `num_licenses`),
    DROP COLUMN `flmavailable_server`,
    DROP COLUMN `flmavailable_product`,
    ADD CONSTRAINT `fk_available_licenses`
        FOREIGN KEY (`license_id`)
        REFERENCES `licenses` (`id`)
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
    ADD COLUMN `license_id` INT NOT NULL FIRST,
    CHANGE COLUMN `flmusage_users` `users` INT NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Make sure servers in `usage` also exist in `servers`.
INSERT IGNORE INTO `servers` (`name`, `alias`, `is_active`)
    SELECT DISTINCT `usage`.`flmusage_server`, replace(`usage`.`flmusage_server`, '.', '_'), 0
    FROM `usage`
    LEFT JOIN `servers` ON `usage`.`flmusage_server`=`servers`.`name`
    WHERE `servers`.`name` IS NULL;

-- Make sure products in `usage` also exist in `features`.
INSERT IGNORE INTO `features` (`name`, `show_in_lists`)
    SELECT DISTINCT `usage`.`flmusage_product`, 1
    FROM `usage`
    LEFT JOIN `features` ON `usage`.`flmusage_product`=`features`.`name`
    WHERE `features`.`name` IS NULL;

-- Make sure all `servers` and `features` are added to `licenses`.
INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id`
    FROM `usage`
    JOIN `servers` ON `usage`.`flmusage_server`=`servers`.`name`
    JOIN `features` ON `usage`.`flmusage_product`=`features`.`name`
    WHERE `servers`.`name`=`usage`.`flmusage_server` AND `features`.`name`=`usage`.`flmusage_product`;

-- Now INSERT those license ids back into `usage`
UPDATE `usage`
    JOIN `servers` ON `servers`.`name`=`usage`.`flmusage_server`
    JOIN `features` ON `features`.`name`=`usage`.`flmusage_product`
    JOIN `licenses` ON `licenses`.`server_id`=`servers`.`id` AND `licenses`.`feature_id`=`features`.`id`
SET `license_id`=`licenses`.`id`
WHERE `licenses`.`server_id`=`servers`.`id` AND `usage`.`flmusage_server`=`servers`.`name`
    AND `licenses`.`feature_id`=`features`.`id` AND `usage`.`flmusage_product`=`features`.`name`;

-- Fix primary key, establish foreign key
ALTER TABLE `usage`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`license_id`, `time`),
    DROP COLUMN `flmusage_server`,
    DROP COLUMN `flmusage_product`,
    DROP COLUMN `flmusage_date`,
    DROP COLUMN `flmusage_time`,
    ADD CONSTRAINT `fk_usage_licenses`
        FOREIGN KEY (`license_id`)
        REFERENCES `licenses` (`id`)
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
    `license_id` INT NOT NULL,
    `time` DATETIME NOT NULL,
    `user` VARCHAR(80) NOT NULL,
    `type` TEXT NOT NULL,
    `reason` TEXT NOT NULL,
    PRIMARY KEY (`license_id`, `time`, `user`),
    CONSTRAINT `fk_events_licenses`
        FOREIGN KEY (`license_id`)
        REFERENCES `licenses` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

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
    ADD UNIQUE INDEX `unique_name_alias_isactive` (`name`, `alias`, `is_active`),
    ENGINE = InnoDB,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

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
    CHANGE COLUMN `flmusage_product` `product` VARCHAR(80) NOT NULL,
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
    ADD CONSTRAINT `fk_usage_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

-- -----------------------------------------------------
-- Table `licenses_available` -> `available`
-- -----------------------------------------------------

-- Refactor `licenses_available` -> `available`
ALTER TABLE `licenses_available`
    RENAME TO `available`,
    ADD COLUMN `server_id` INT FIRST,
    CHANGE COLUMN `flmavailable_date` `date` DATE NOT NULL,
    CHANGE COLUMN `flmavailable_product` `product` VARCHAR(80) NOT NULL,
    CHANGE COLUMN `flmavailable_num_licenses` `num_licenses` INT NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- Make sure servers in `available` also exist in `servers`.
INSERT INTO `servers` (`name`, `alias`, `is_active`)
    SELECT DISTINCT `available`.`flmavailable_server`, replace(`available`.`flmavailable_server`, '.', '_'), 1
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
    ADD CONSTRAINT `fk_available_servers`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

-- -----------------------------------------------------
-- Table `feature` -> `features`
-- -----------------------------------------------------
ALTER TABLE `feature`
    RENAME TO `features`
    CHANGE COLUMN `featureID` `id` INT NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `showInLists` `show_in_lists` TINYINT DEFAULT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `flexlm_events` -> `events`
-- -----------------------------------------------------

-- Combine date and time columns to a single datetime column
ALTER TABLE `flexlm_events`
    ADD COLUMN `time` datetime NOT NULL DEFAULT now() FIRST;

UPDATE `flexlm_events` SET `time` = timestamp(`flmevent_date`, `flmevent_time`);

-- Refactor `flexlm_events` -> `events`
ALTER TABLE `flexlm_events`
    RENAME TO `events`,
    CHANGE COLUMN `flmevent_type` `type` VARCHAR(20) NOT NULL,
    CHANGE COLUMN `flmevent_feature` `feature` VARCHAR(40) NOT NULL,
    CHANGE COLUMN `flmevent_user` `user` VARCHAR(80) NOT NULL,
    CHANGE COLUMN `flmevent_reason` `reason` TEXT NOT NULL,
    DROP PRIMARY KEY,
    DROP COLUMN `flmevent_date`,
    DROP COLUMN `flmevent_time`,
    ADD PRIMARY KEY (`time`, `feature`, `user`),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `server_status` -> `status`
-- -----------------------------------------------------
ALTER TABLE `server_status`
    RENAME TO `status`,
    MODIFY COLUMN `server_id` INT NOT NULL FIRST,
    CHANGE COLUMN `server_dns` `dns` VARCHAR(100) DEFAULT NULL,
    CHANGE COLUMN `server_port` `port` INT DEFAULT NULL,
    CHANGE COLUMN `lm_hostname` `hostname` VARCHAR(100) DEFAULT NULL,
    CHANGE COLUMN `isMaster` `is_master` TINYINT DEFAULT NULL,
    CHANGE COLUMN `lmgrd_version` `version` VARCHAR(20) DEFAULT NULL,
    ADD PRIMARY KEY (`server_id`),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

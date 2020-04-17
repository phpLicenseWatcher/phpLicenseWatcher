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
-- TO DO: Foreign key `server_id` -> `servers`.`id`
--        Fix data for `server_id` (varchar(80) -> int)
-- -----------------------------------------------------
ALTER TABLE `license_usage`
    ADD COLUMN `time` datetime NOT NULL DEFAULT now() FIRST;

UPDATE `license_usage` SET `time` = timestamp(`flmusage_date`, `flmusage_time`);

ALTER TABLE `license_usage`
    RENAME TO `usage`,
    CHANGE COLUMN `flmusage_server` `server_id` INT NOT NULL FIRST, -- was varchar(80)
    CHANGE COLUMN `flmusage_product` `product` VARCHAR(80) NOT NULL,
    CHANGE COLUMN `flmusage_users` `users` INT NOT NULL,
    DROP PRIMARY KEY,
    DROP COLUMN `flmusage_date`,
    DROP COLUMN `flmusage_time`,
    ADD PRIMARY KEY (`time`, `server`, `product`),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `licenses_available` -> `available`
-- TO DO: Foreign key `server_id` -> `servers`.`id`
-- -----------------------------------------------------
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
    ADD CONSTRAINT `fk_usage_server1`
        FOREIGN KEY (`server_id`)
        REFERENCES `servers` (`id`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

-- -----------------------------------------------------
-- Table `feature`
-- -----------------------------------------------------
ALTER TABLE `feature`
    CHANGE COLUMN `featureID` `id` BIGINT NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `showInLists` `show_in_lists` TINYINT DEFAULT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `flexlm_events` -> `events`
-- -----------------------------------------------------
ALTER TABLE `flexlm_events`
    ADD COLUMN `time` datetime NOT NULL DEFAULT now() FIRST;

UPDATE `flexlm_events` SET `time` = timestamp(`flmevent_date`, `flmevent_time`);

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
    CHANGE COLUMN `server_id` `id` INT NOT NULL FIRST,
    CHANGE COLUMN `server_dns` `dns` VARCHAR(100) DEFAULT NULL,
    CHANGE COLUMN `server_port` `port` INT DEFAULT NULL,
    CHANGE COLUMN `lm_hostname` `hostname` VARCHAR(100) DEFAULT NULL,
    CHANGE COLUMN `isMaster` `is_master` TINYINT DEFAULT NULL,
    CHANGE COLUMN `lmgrd_version` `version` VARCHAR(20) DEFAULT NULL,
    CHANGE COLUMN `last_updated` DATETIME DEFAULT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

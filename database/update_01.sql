-- -----------------------------------------------------
-- Schema phplw (update 01)
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `server` -> `servers`
-- -----------------------------------------------------
ALTER TABLE `server`
    RENAME TO `servers`,
    ADD TABLE `is_active` tinyint NOT NULL DEFAULT 1,
    ADD TABLE `notes` text,
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
    CHANGE COLUMN `flmusage_server` `server_id` int NOT NULL FIRST, -- was varchar(80)
    CHANGE COLUMN `flmusage_product` `product` varchar(80) NOT NULL,
    CHANGE COLUMN `flmusage_users` `users` int NOT NULL,
    DROP PRIMARY KEY,
    DROP COLUMN `flmusage_date`,
    DROP COLUMN `flmusage_time`,
    ADD PRIMARY KEY (`time`, `server`, `product`),
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `licenses_available` -> `available`
-- TO DO: Foreign key `server_id` -> `servers`.`id`
--        Fix data for `server_id` (varchar(80) -> int)
-- -----------------------------------------------------
ALTER TABLE `licenses_available`
    RENAME TO `available`,
    CHANGE COLUMN `flmavailable_date` `date` date NOT NULL,
    CHANGE COLUMN `flmavailable_server` `server_id` int NOT NULL FIRST, -- was varchar(80)
    CHANGE COLUMN `flmavailable_product` `product` varchar(80) NOT NULL,
    CHANGE COLUMN `flmavailable_num_licenses` `num_licenses` int NOT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `feature`
-- -----------------------------------------------------
ALTER TABLE `feature`
    CHANGE COLUMN `featureID` `id` bigint NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `showInLists` `show_in_lists` tinyint DEFAULT NULL,
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
    CHANGE COLUMN `flmevent_type` `type` varchar(20) NOT NULL,
    CHANGE COLUMN `flmevent_feature` `feature` varchar(40) NOT NULL,
    CHANGE COLUMN `flmevent_user` `user` varchar(80) NOT NULL,
    CHANGE COLUMN `flmevent_reason` `reason` text NOT NULL,
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
    CHANGE COLUMN `server_id` `id` int NOT NULL FIRST,
    CHANGE COLUMN `server_dns` `dns` varchar(100) DEFAULT NULL,
    CHANGE COLUMN `server_port` `port` int DEFAULT NULL,
    CHANGE COLUMN `lm_hostname` `hostname` varchar(100) DEFAULT NULL,
    CHANGE COLUMN `isMaster` `is_master` tinyint DEFAULT NULL,
    CHANGE COLUMN `lmgrd_version` `version` varchar(20) DEFAULT NULL,
    CHANGE COLUMN `last_updated` datetime DEFAULT NULL,
    CONVERT TO CHARACTER SET utf8,
    DEFAULT CHARACTER SET = utf8;

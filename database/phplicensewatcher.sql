SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL, NO_AUTO_VALUE_ON_ZERO';

-- -----------------------------------------------------
-- Schema phplw
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `features`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `features` ;

CREATE TABLE IF NOT EXISTS `features` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `show_in_lists` TINYINT NOT NULL,
  `label` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `servers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `servers` ;

CREATE TABLE IF NOT EXISTS `servers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `alias` VARCHAR(100) NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `lmgrd_version` TEXT NULL,
  `last_updated` DATETIME NULL DEFAULT now(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_alias_UNIQUE` (`name` ASC, `alias` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 1
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
  CONSTRAINT `fk_licenses_servers1`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_licenses_features1`
    FOREIGN KEY (`feature_id`)
    REFERENCES `features` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_bin;


-- -----------------------------------------------------
-- Table `events`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `events` ;

CREATE TABLE IF NOT EXISTS `events` (
  `license_id` INT NOT NULL,
  `time` DATETIME NOT NULL,
  `user` VARCHAR(80) NOT NULL,
  `type` TEXT NOT NULL,
  `reason` TEXT NOT NULL,
  PRIMARY KEY (`license_id`, `time`, `user`),
  CONSTRAINT `fk_events_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `usage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `usage` ;

CREATE TABLE IF NOT EXISTS `usage` (
  `license_id` INT NOT NULL,
  `time` DATETIME NOT NULL,
  `users` INT NOT NULL,
  PRIMARY KEY (`license_id`, `time`),
  CONSTRAINT `fk_usage_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `available`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `available` ;

CREATE TABLE IF NOT EXISTS `available` (
  `license_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `licenses` INT NOT NULL,
  PRIMARY KEY (`license_id`, `date`),
  CONSTRAINT `fk_available_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

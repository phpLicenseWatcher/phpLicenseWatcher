SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- -----------------------------------------------------
-- Schema phplw
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `features`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `features` ;

CREATE TABLE IF NOT EXISTS `features` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `label` VARCHAR(100) NULL DEFAULT NULL,
  `show_in_lists` TINYINT NOT NULL DEFAULT 1,
  `is_tracked` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `servers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `servers` ;

CREATE TABLE IF NOT EXISTS `servers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `lm_default_usage_reporting` TINYINT NOT NULL DEFAULT 1,
  `status` VARCHAR(25) NULL,
  `license_manager` VARCHAR(25) NOT NULL,
  `version` VARCHAR(15) NULL,
  `last_updated` DATETIME NULL DEFAULT now(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `licenses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `licenses` ;

CREATE TABLE IF NOT EXISTS `licenses` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_id` INT UNSIGNED NOT NULL,
  `feature_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `serverid_featureid_UNIQUE` (`server_id` ASC, `feature_id` ASC),
  INDEX `fk_licenses_features1_idx` (`feature_id` ASC),
  CONSTRAINT `fk_licenses_servers1`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_licenses_features1`
    FOREIGN KEY (`feature_id`)
    REFERENCES `features` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `usage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `usage` ;

CREATE TABLE IF NOT EXISTS `usage` (
  `license_id` SMALLINT UNSIGNED NOT NULL,
  `time` DATETIME NOT NULL,
  `num_users` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`license_id`, `time`),
  CONSTRAINT `fk_usage_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `available`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `available` ;

CREATE TABLE IF NOT EXISTS `available` (
  `license_id` SMALLINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `num_licenses` INT NOT NULL,
  PRIMARY KEY (`license_id`, `date`),
  CONSTRAINT `fk_available_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- -----------------------------------------------------
-- Schema phplw
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `feature`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `feature` ;

CREATE TABLE IF NOT EXISTS `feature` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `feature` VARCHAR(100) NULL DEFAULT NULL,
  `show_in_lists` TINYINT NULL DEFAULT NULL,
  `label` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 8
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `events`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `events` ;

CREATE TABLE IF NOT EXISTS `events` (
  `time` DATETIME NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `feature` VARCHAR(40) NOT NULL,
  `user` VARCHAR(80) NOT NULL,
  `reason` TEXT NOT NULL,
  PRIMARY KEY (`time`, `feature`, `user`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `server`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `server` ;

CREATE TABLE IF NOT EXISTS `server` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `alias` VARCHAR(50) NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `usage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `usage` ;

CREATE TABLE IF NOT EXISTS `usage` (
  `server_id` INT NOT NULL,
  `product` VARCHAR(80) NOT NULL,
  `time` DATETIME NOT NULL,
  `users` INT NOT NULL,
  PRIMARY KEY (`product`, `server_id`, `time`),
  INDEX `fk_usage_server1_idx` (`server_id` ASC),
  CONSTRAINT `fk_usage_server1`
    FOREIGN KEY (`server_id`)
    REFERENCES `server` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `available`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `available` ;

CREATE TABLE IF NOT EXISTS `available` (
  `date` DATE NOT NULL,
  `server_id` INT NOT NULL,
  `product` VARCHAR(80) NOT NULL,
  `num_licenses` INT NOT NULL,
  PRIMARY KEY (`date`, `product`, `num_licenses`, `server_id`),
  INDEX `fk_available_server_idx` (`server_id` ASC),
  CONSTRAINT `fk_available_server`
    FOREIGN KEY (`server_id`)
    REFERENCES `server` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `status`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `status` ;

CREATE TABLE IF NOT EXISTS `status` (
  `id` INT NOT NULL,
  `dns` VARCHAR(100) NULL DEFAULT NULL,
  `port` BIGINT NULL DEFAULT NULL,
  `label` VARCHAR(100) NULL DEFAULT NULL,
  `status` VARCHAR(10) NULL DEFAULT NULL,
  `lm_hostname` VARCHAR(100) NULL DEFAULT NULL,
  `is_master` TINYINT NULL DEFAULT NULL,
  `lmgrd_version` VARCHAR(20) NULL DEFAULT NULL,
  `last_updated` DATETIME NULL DEFAULT NULL)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL, NO_AUTO_VALUE_ON_ZERO';

-- -----------------------------------------------------
-- Schema phplw
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `servers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `servers` ;

CREATE TABLE IF NOT EXISTS `servers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `alias` VARCHAR(50) NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unique_name_alias_isactive` (`name` ASC, `alias` ASC, `is_active` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `features`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `features` ;

CREATE TABLE IF NOT EXISTS `features` (
  `server_id` INT NOT NULL AUTO_INCREMENT,
  `feature` VARCHAR(100) NOT NULL,
  `show_in_lists` TINYINT NOT NULL,
  `label` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`server_id`, `feature`),
  CONSTRAINT `fk_features_servers`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 8
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `events`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `events` ;

CREATE TABLE IF NOT EXISTS `events` (
  `server_id` INT NOT NULL,
  `feature` VARCHAR(40) NOT NULL,
  `time` DATETIME NOT NULL,
  `user` VARCHAR(80) NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `reason` TEXT NOT NULL,
  PRIMARY KEY (`server_id`, `feature`, `time`, `user`),
  CONSTRAINT `fk_events_servers`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `usage`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `usage` ;

CREATE TABLE IF NOT EXISTS `usage` (
  `server_id` INT NOT NULL,
  `time` DATETIME NOT NULL,
  `product` VARCHAR(80) NOT NULL,
  `users` INT NOT NULL,
  PRIMARY KEY (`server_id`, `time`, `product`),
  INDEX `fk_usage_servers_idx` (`server_id` ASC),
  CONSTRAINT `fk_usage_servers`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `available`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `available` ;

CREATE TABLE IF NOT EXISTS `available` (
  `server_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `product` VARCHAR(80) NOT NULL,
  `licenses` INT NOT NULL,
  PRIMARY KEY (`server_id`, `date`, `product`),
  INDEX `fk_available_servers_idx` (`server_id` ASC),
  CONSTRAINT `fk_available_servers`
    FOREIGN KEY (`server_id`)
    REFERENCES `servers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `status`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `status` ;

CREATE TABLE IF NOT EXISTS `status` (
  `server_id` INT NOT NULL,
  `dns` VARCHAR(100) NULL DEFAULT NULL,
  `port` INT NULL DEFAULT NULL,
  `label` VARCHAR(100) NULL DEFAULT NULL,
  `status` VARCHAR(10) NULL DEFAULT NULL,
  `lm_hostname` VARCHAR(100) NULL DEFAULT NULL,
  `is_master` TINYINT NULL DEFAULT NULL,
  `lmgrd_version` VARCHAR(20) NULL DEFAULT NULL,
  `last_updated` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`server_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

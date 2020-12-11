-- -----------------------------------------------------
-- Table `events`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `events` ;

CREATE TABLE IF NOT EXISTS `events` (
  `license_id` SMALLINT UNSIGNED NOT NULL,
  `time` DATETIME NOT NULL,
  `user` VARCHAR(80) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`license_id`, `time`, `user`),
  CONSTRAINT `fk_events_licenses1`
    FOREIGN KEY (`license_id`)
    REFERENCES `licenses` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;

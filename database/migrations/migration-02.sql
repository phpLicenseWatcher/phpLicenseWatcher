-- DB Migration for whether or not to count reserve tokens as used licenses.

ALTER TABLE `servers` ADD `lm_default_usage_reporting` TINYINT NOT NULL DEFAULT 1 AFTER `is_active`;

-- DB Migration for whether or not to count reserve tokens as used licenses.

ALTER TABLE `servers` ADD `count_reserve_tokens_as_used` TINYINT NOT NULL DEFAULT 1 AFTER `is_active`;

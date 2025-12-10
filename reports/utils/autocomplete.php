<?php
namespace PhpLicenseWatcher\Reports\Utils;
require_once __DIR__ . "/database.php";

/** @author Peter Bailie (pbailie@github) */
final class autocomplete {
    public static function lookup_servers(string $term) {
        $sql = <<<SQL
        SELECT `id` AS value, concat(`label`, ' (', `name`, ')') AS label
        FROM `servers`
        WHERE `is_active` = 1 AND `label` REGEXP(?)
        SQL;

        return db::query($sql, "s", $term);
    }

    public static function lookup_licenses_by_server(string $term, int $server_id) {
        $sql = <<<SQL
        SELECT f.`name` AS label, l.`id` AS value
        FROM `features` f
        INNER JOIN `licenses` l ON f.`id` = l.`feature_id`
        INNER JOIN `servers` s ON l.`server_id` = s.`id`
        WHERE f.`is_tracked` = 1 AND f.`name` REGEXP(?) AND s.`id` = ?
        SQL;

        return db::query($sql, "si", $term, $server_id);
    }
}

// EOF

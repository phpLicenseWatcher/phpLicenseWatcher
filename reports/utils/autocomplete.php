<?php
namespace PhpLicenseWatcher\Reports\Utils;

require_once __DIR__ . "../../common.php";

class autocomplete {
    public static function lookup_server($term) {
        $sql = <<<SQL
        SELECT `id` AS value, concat(`label`, ' (', `name`, ')') AS label
        FROM `servers`
        WHERE `is_active` = 1 AND `label` REGEXP(?)
        SQL;

        $db = null;
        $fetch = null;

        db_connect($db);
        $query = $db->prepare($sql);
        $query->bind_result($fetch);
        $query->bind_param("s", $term);
        $query->execute();
        $query->fetch_all(MYSQLI_ASSOC);
        return json_encode($fetch);
    }

    public static function lookup_license_by_server($term, $server_id) {
        $sql = <<<SQL
        SELECT f.`name` AS label, l.`id` AS value
        FROM `features` f
        INNER JOIN `licenses` l ON f.`id` = l.`feature_id`
        INNER JOIN `servers` s ON l.`server_id` = s.`id`
        WHERE f.`is_tracked` = 1 AND f.`name` REGEXP(?) AND s.`id` = ?
        SQL;

        $db = null;
        $fetch = null;

        db_connect($db);
        $query = $db->prepare($sql);
        $query->bind_result($fetch);
        $query->bind_param("si", $term, $server_id);
        $query->execute();
        $query->fetch_all(MYSQLI_ASSOC);
        return json_encode($fetch);
    }
}

<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/lmtools.php";

db_connect($db);
$servers = db_get_servers($db, array('name'));

$lmtools = new lmtools();

foreach ($servers as $server) {
    if ($lmtools->lm_open('flexlm', 'license_cache', $server['name']) === false) {
        fprintf(STDERR, "%s\n", $lmtools->err);
        exit(1);
    }

    $lmdata = $lmtools->lm_nextline();
    if ($lmdata === false) {
        fprintf(STDERR, "%s\n", $lmtools->err);
        exit(1);
    }

    while (!is_null($lmdata)) {
        $feature = $lmdata['feature'];
        $num_licenses = $lmdata['num_licenses'];

        $sql = <<<SQL
        INSERT IGNORE INTO `available` (`license_id`, `date`, `num_licenses`)
        SELECT `licenses`.`id`, NOW(), {$num_licenses} FROM `licenses`
        JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
        JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
        WHERE `servers`.`name`='{$server["name"]}' AND `features`.`name`='{$feature}';
        SQL;

        $result = $db->query($sql);
        if (!$result) {
            die ($db->error);
        }

        $lmdata = $lmtools->lm_nextline();
        if ($lmdata === false) {
            fprintf(STDERR, "%s\n", $lmtools->err);
            exit(1);
        }
    }
}

$db->close();

?>

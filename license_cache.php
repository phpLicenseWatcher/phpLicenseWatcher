<?php
require_once(__DIR__."/common.php");
require_once("DB.php");

$db = db_connect();
$servers = db_get_servers($db);
$today = date("Y-m-d");

foreach ($servers as $server) {
    $fp = popen($lmutil_loc . " lmstat -a -c " . $server['name'], "r");
    while ( !feof ($fp) ) {
        $line = fgets ($fp, 1024);

        // Look for features in the output. You will see stuff like
        // Users of Allegro_Viewer: (Total of 5 licenses available
        if ( preg_match("/^Users of (.*)Total /i", $line ) )  {
            $out = explode(" ", $line);
            // Remove the : in the end of the string
            $feature = str_replace(":", "", $out[2]);

            $sql = <<<SQL
INSERT INTO `available` (`license_id`, `date`, `num_licenses`)
    SELECT `licenses`.`id`, '{$today}', {$out[6]}
    FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`='{$server["name"]}' AND `features`.`name`='{$feature}';
SQL;

            $recordset = $db->query($sql);
            print_sql ($sql);
            if (DB::isError($recordset)) {
                die ($recordset->getMessage());
            }
        }
    }
    pclose($fp);
}

$db->disconnect();

?>

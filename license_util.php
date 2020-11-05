<?php

require_once(__DIR__.'/tools.php');
require_once(__DIR__."/common.php");
require_once("DB.php");

define ('SERVER_UP', "UP");
define ('SERVER_DOWN', "DOWN");
define ('SERVER_VENDOR_DOWN', "VENDOR DOWN");

db_connect($db);
$servers = db_get_servers($db, array('name'));

// Update statuses of all servers.
$update_data = array();
foreach ($servers as $index => $server) {
    // Retrieve server details via lmstat.
    $fp=popen("{$lmutil_binary} lmstat -c {$server['name']}", "r");

    $stdout = "";
    while (!feof($fp)) {
        $stdout .= fgets($fp);
    }
    pclose($fp);

    print_var($stdout);

    // Determine status and lmgrd version;
    $status = null;
    $lmgrd_version = null;

    // If server is up, also read its lmgrd version.
    if (preg_match("/license server UP (?:\(MASTER\) )?(?<lmgrd_version>v\d+[\d\.]*)$/im", $stdout, $matches)) {
        $status = SERVER_UP;
        $lmgrd_version = $matches['lmgrd_version'];
    }

    // This can override $status is UP.
    if (preg_match("/vendor daemon is down/im", $stdout)) {
        $status = SERVER_VENDOR_DOWN;
    }

    // If $status isn't determined by now, assume it is DOWN.
    if (is_null($status)) {
        $status = SERVER_DOWN;
    }

    // Use same $index as $servers[$index], making parrallel arrays.
    $update_data[$index] = array($status, $lmgrd_version, $server['name']);
    // Servers that are not up are discarded so they are not (futilely) checked for license info.
    if ($update_data[$index][0] !== SERVER_UP) {
        unset ($servers[$index]);
    }

}

    print_var($update_data);
    print_var($servers);

$sql = $db->prepare("UPDATE `servers` SET `status`=?, `lmgrd_version`=?, `last_updated`=NOW() WHERE `name`=?;");
$db->query("LOCK TABLES `servers` WRITE;");
foreach($update_data as $data) {
    $db->execute($sql, $data);
}
$db->query("UNLOCK TABLES;");

exit;

foreach ($servers as $server) {
    $fp=popen("{$lmutil_binary} lmstat -a -c {$server['name']}", "r");

    while ( !feof ($fp) ) {
        $line = fgets ($fp, 1024);

    	// Look for features in the output. You will see stuff like
    	// Users of Allegro_Viewer: (Total of 5 licenses available
    	if ( preg_match("/^(Users of) (.*)/i", $line, $out ) )  {
            if ( preg_match("/(Total of) (.*) (license[s]? issued;  Total of) (.*) (license[s]? in use)/i", $line, $items ) ) {
                $licenses[] = array (
                    "feature" => substr($out[2],0,strpos($out[2],":")),
                    "licenses_used" => $items[4] ) ;
                unset($out);
                unset($items);
            }
        }
    }

    pclose($fp);

    if ( isset($licenses) && is_countable($licenses) ) {
        foreach ($licenses as $license) {

            $sql = array();
            // Populate feature, if needed.
            $sql[0] = <<<SQL
INSERT IGNORE INTO `features` (`name`, `show_in_lists`)
    VALUES ('{$license["feature"]}', 1);
SQL;

            // Populate server/feature to licenses, if needed.
            $sql[1] = <<<SQL
INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id`
    FROM `servers`, `features`
    WHERE `servers`.`name` = '{$server["name"]}' AND `features`.`name` = '{$license["feature"]}';
SQL;

            // Insert license usage.  Needs feature and license populated, first.
            $sql[2] = <<<SQL
INSERT IGNORE INTO `usage` (`license_id`, `time`, `num_users`)
    SELECT `licenses`.`id`, '{$time}', {$license["licenses_used"]}
    FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`='{$server["name"]}' AND `features`.`name`='{$license["feature"]}';
SQL;

            $recordset = $db->query($sql[2]);
            print_sql($sql[2]);
            if (DB::isError($recordset)) {
                die ($recordset->getMessage());
            }


            // when affectedRows < 1, feature and license needs to be
            // populated and license usage query re-run.
            if ($db->affectedRows() < 1) {
                foreach ($sql as $statement) {
                    $recordset = $db->query($statement);
                    print_sql($statement);
                    if (DB::isError($recordset)) {
                        die ($recordset->getMessage());
                    }
                }
            }
        }
        unset($licenses);
    }
}

$db->disconnect();

?>

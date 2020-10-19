<?php

include_once(__DIR__.'/tools.php');
require_once(__DIR__."/common.php");

// Load PEAR DB abstraction library
require_once("DB.php");

// If DB settings set up - Connect to the database
if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {
	$db = DB::connect($dsn, true);

	if (DB::isError($db)) {
		die ($db->getMessage());
	}
}

// Get server list from DB
$sql = "SELECT `name` FROM `servers` WHERE `is_active` = 1;";
$servers = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);

// Get current date and time
$time = date('Y-m-d H:i:00');

for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {
    $fp = popen($lmutil_loc . " lmstat -a -c " . $servers[$i]['name'], "r");

    while ( !feof ($fp) ) {
        $line = fgets ($fp, 1024);

    	// Look for features in the output. You will see stuff like
    	// Users of Allegro_Viewer: (Total of 5 licenses available
    	if ( preg_match("/^(Users of) (.*)/i", $line, $out ) )  {
            if ( preg_match("/(Total of) (.*) (license[s]? issued;  Total of) (.*) (license[s]? in use)/i", $line, $items ) ) {
                $license_array[] = array (
                    "feature" => substr($out[2],0,strpos($out[2],":")),
                    "licenses_used" => $items[4] ) ;
                unset($out);
                unset($items);
            }
        }
    }

    pclose($fp);
    // $conn->debug=true;

    if ( isset($license_array) && is_array ($license_array) ) {
        for ( $j = 0 ; $j < sizeof($license_array) ; $j++ ) {

            // Only insert into the database if DB settings are set
            if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {

                $sql = array();
                // Populate feature, if needed.
                $sql[0] = <<<SQL
INSERT IGNORE INTO `features` (`name`)
    VALUES ('{$license_array[$j]["feature"]}');
SQL;

                // Populate server/feature to licenses, if needed.
                $sql[1] = <<<SQL
INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id`
    FROM `servers`, `features`
    WHERE `servers`.`name` = '{$servers[$i]["name"]}' AND `features`.`name` = '{$license_array[$j]["feature"]}';
SQL;

                // Insert license usage.  Needs feature and license populated, first.
                $sql[2] = <<<SQL
INSERT INTO `usage` (`license_id`, `time`, `num_users`)
    SELECT `licenses`.`id`, '{$time}', {$license_array[$j]["licenses_used"]}
    FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`='{$servers[$i]["name"]}' AND `features`.`name`='{$license_array[$j]["feature"]}';
SQL;

                if ( isset($debug) && $debug == 1 ) {
                    print_sql ($sql[2]);
                }

                $recordset = $db->query($sql[2]);
                if (DB::isError($recordset)) {
                    die ($recordset->getMessage());
                }

                // when affectedRows < 1, feature and license needs to be
                // populated and license usage query re-run.
                if ($db->affectedRows() < 1) {
                    foreach ($sql as $statement) {
                        $recordset = $db->query($statement);
                    }
                }
            }
        }
        unset($license_array);
    }
}

// Disconnect connection if using DB
if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {
	$db->disconnect();
}

?>

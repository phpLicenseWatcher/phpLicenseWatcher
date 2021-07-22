<?php
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/lmtools.php";

db_connect($db);
$servers = db_get_servers($db, array('name'));
update_servers($db, $servers);
update_licenses($db, $servers);
$db->close();
exit;

/**
 * Update statuses of all active servers in DB
 *
 * All entries in $servers will be updated in DB.  Afterwards any servers that
 * are not up get culled from the list as their license info is inaccessible.
 *
 * @param &$db DB connection object.
 * @param &$servers active servers list array.
 */
function update_servers(&$db, &$servers) {
    global $lmutil_binary;

    $update_data = array();
    $lmtools = new lmtools();
    foreach ($servers as $index => $server) {
        // Retrieve server details via lmstat.
        $lmtools->lm_open('flexlm', 'license_util_update_servers', $server['name']);
        $lmtools->regex_matched($pattern, $matches);

        // DB update data as parralel arrays, using server's $index
        $status[$index] = SERVER_DOWN; // assume server is down unless determined otherwise.
        $server_version[$index] = "";
        $name[$index] = $server['name'];

        // If server is up, also read its lmgrd version.
        if ($pattern === "server_up") {
            $status[$index] = SERVER_UP;
            $server_version[$index] = $matches['server_version'];
        }

        // This can override $status[$index] is UP.
        if ($pattern === "vendor_down") {
            $status[$index] = SERVER_VENDOR_DOWN;
        }

        // Servers that are not SERVER_UP are discarded so they are not checked for license info, later.
        if ($status[$index] !== SERVER_UP) {
            unset ($servers[$index]);
        }
    }

    // Server statuses DB update
    $db->query("LOCK TABLES `servers` WRITE;");
    $query = $db->prepare("UPDATE `servers` SET `status`=?, `lmgrd_version`=?, `last_updated`=NOW() WHERE `name`=?;");
    foreach($name as $index => $_n) {
        $query->bind_param("sss", $status[$index], $server_version[$index], $name[$index]);
        $query->execute();
    }

    $query->close();
    $db->query("UNLOCK TABLES;");
} // END function update_servers()

/**
 * Get license use info from all servers that are up.
 *
 * @param &$db DB connection object.
 * @param $servers active/up server list array.
 */
function update_licenses(&$db, $servers) {
    // Setup DB queries
    // $queries, $data, $type are parallel arrays.  $data is defined later.
    // $type refers to number of "s"trings being prepared for each query.
    // Populate feature, if needed.
    // ? = $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `features` (`name`, `show_in_lists`, `is_tracked`) VALUES (?, 1, 1);
    SQL;
    $queries[0] = $db->prepare($sql);
    $type[0] = "s"; // one string to bind to mysqli_stmt::bind_param()

    // Populate server/feature to licenses, if needed.
    // ?, ? = $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id` FROM `servers`, `features`
    WHERE `servers`.`name` = ? AND `features`.`name` = ?;
    SQL;
    $queries[1] = $db->prepare($sql);
    $type[1] = "ss"; // two strings to bind to mysqli_stmt::bind_param()

    // Insert license usage.  Needs feature and license populated, first.
    // ?, ?, ? = $lmdata['licenses_used'], $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `usage` (`license_id`, `time`, `num_users`)
    SELECT `licenses`.`id`, NOW(), ? FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`=? AND `features`.`name`=? AND `features`.`is_tracked`=1;
    SQL;
    $queries[2] = $db->prepare($sql);
    $type[2] = "sss"; // three strings to bind to mysqli_stmt::bind_param()
    // END setup DB queries.

    $lmtools = new lmtools();
    foreach ($servers as $server) {
        $licenses = array();
        $lmtools->lm_open('flexlm', 'license_util_update_licenses', $server['name']);
        $lmdata = $lmtools->lm_nextline();
        if ($lmdata === false) {
            fprintf (STDERR, "%s\n", $lmtools->err);
            $queries[2]->close();
            $queries[1]->close();
            $queries[0]->close();
            $db->close();
            exit(1);
        }

        // INSERT license data to DB
        $db->query("LOCK TABLES `features`, `licenses`, `usage` WRITE");
        $db->query("BEGIN");
        while (!is_null($lmdata)) {
            // bind data to prepared queries.
            $data = array();
            $data[0] = array($lmdata['feature']);
            $data[1] = array($server['name'], $lmdata['feature']);
            $data[2] = array($lmdata['licenses_used'], $server['name'], $lmdata['feature']);

            // bind data to each query.
            // q.v. https://www.php.net/functions.arguments#functions.variable-arg-list for '...' token
            $queries[0]->bind_param($type[0], ...$data[0]);
            $queries[1]->bind_param($type[1], ...$data[1]);
            $queries[2]->bind_param($type[2], ...$data[2]);

            // Attempt to INSERT license usage...
            $queries[2]->execute();

            // when affectedRows < 1, feature and license first needs to be
            // populated and then license usage query re-run.
            if ($db->affected_rows < 1) {
                $queries[0]->execute();
                $queries[1]->execute();
                $queries[2]->execute();
            }

            // Get another data set from license manager.
            if ($lmdata === false) {
                fprintf (STDERR, "%s\n", $lmtools->err);
                $db->query("ROLLBACK");
                $db->query("UNLOCK TABLES");
                $queries[2]->close();
                $queries[1]->close();
                $queries[0]->close();
                $db->close();
                exit(1);
            }
        } // END while(!is_null($lmdata())

        // Cleanup
        $db->query("COMMIT");
        $db->query("UNLOCK TABLES");
        $queries[2]->close();
        $queries[1]->close();
        $queries[0]->close();
    } // END foreach($servers as $server)
} // END function update_licenses()

?>

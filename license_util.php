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
        // Retrieve server details from lmtools.php object.
        $lmtools->lm_open('flexlm', 'license_util__update_servers', $server['name']);
        $lmtools->lm_regex_matches($pattern, $matches);

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
        if ($pattern === "server_vendor_down") {
            $status[$index] = SERVER_VENDOR_DOWN;
        }

        // Servers that are not SERVER_UP are discarded so they are not checked for license info, later.
        if ($status[$index] !== SERVER_UP) {
            unset ($servers[$index]);
        }
    }

    // Server statuses DB update
    $query = $db->prepare("UPDATE `servers` SET `status`=?, `lmgrd_version`=?, `last_updated`=NOW() WHERE `name`=?;");
    $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    foreach($name as $index => $_n) {
        $query->bind_param("sss", $status[$index], $server_version[$index], $name[$index]);
        if ($query->execute() === false) {
            $db->rollback();
            print_error_and_die("MySQL: {$query->error}");
        }
    }
    $db->commit();
    $query->close();
} // END function update_servers()

/**
 * Get license use info from all servers that are up.
 *
 * @param &$db DB connection object.
 * @param $servers active/up server list array.
 */
function update_licenses(&$db, $servers) {
    // Setup DB queries

    // Populate feature, if needed.
    // ? = $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `features` (`name`, `show_in_lists`, `is_tracked`) VALUES (?, 1, 1);
    SQL;
    $query_features = $db->prepare($sql);

    // Populate server/feature to licenses, if needed.
    // ?, ? = $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id` FROM `servers`, `features`
    WHERE `servers`.`name` = ? AND `features`.`name` = ?;
    SQL;
    $query_licenses = $db->prepare($sql);

    // Insert license usage.  Needs feature and license populated, first.
    // ?, ?, ? = $lmdata['licenses_used'], $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `usage` (`license_id`, `time`, `num_users`)
    SELECT `licenses`.`id`, NOW(), ? FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`=? AND `features`.`name`=? AND `features`.`is_tracked`=1;
    SQL;
    $query_usage = $db->prepare($sql);
    // END setup DB queries.

    $lmtools = new lmtools();
    $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    foreach ($servers as $server) {
        $licenses = array();
        if ($lmtools->lm_open('flexlm', 'license_util__update_licenses', $server['name']) === false) {
            $db->rollback();
            print_error_and_die($lmtools->err);
        }
        $lmdata = $lmtools->lm_nextline();
        if ($lmdata === false) {
            $db->rollback();
            print_error_and_die($lmtools->err);
        }

        // INSERT license data to DB
        while (!is_null($lmdata)) {
            // bind data to prepared queries.
            $query_features->bind_param("s", $lmdata['feature']);
            $query_licenses->bind_param("ss", $server['name'], $lmdata['feature']);
            $query_usage->bind_param("sss", $lmdata['licenses_used'], $server['name'], $lmdata['feature']);

            // Attempt to INSERT license usage...
            if ($query_usage->execute() === false) {
                $db->rollback();
                print_error_and_die("MySQL: {$query_usage->error}");
            }

            // when affectedRows < 1, feature and license first needs to be
            // populated and then license usage query re-run.
            if ($db->affected_rows < 1) {
                switch (false) {
                case $query_features->execute():
                    $db->rollback();
                    print_error_and_die("MySQL: {$query_features->error}");
                case $query_licenses->execute():
                    $db->rollback();
                    print_error_and_die("MySQL: {$query_licenses->error}");
                case $query_usage->execute():
                    $db->rollback();
                    print_error_and_die("MySQL: {$query_usage->error}");
                }
            }

            // Get another data set from license manager.
            $lmdata = $lmtools->lm_nextline();
            if ($lmdata === false) {
                $db->rollback();
                print_error_and_die($lmtools->err);
            }
        } // END while(!is_null($lmdata())
    } // END foreach($servers as $server)

    // Complete and cleanup
    $db->commit();
    $query_usage->close();
    $query_licenses->close();
    $query_features->close();
} // END function update_licenses()

/**
 * Print error to STDERR and exit 1.
 *
 * die() is insufficient because it prints to STDOUT and exits 0.
 *
 * @param $msg error message
 */
function print_error_and_die($msg) {
    fprintf(STDERR, "%s\n", $msg);
    exit(1);
}
?>

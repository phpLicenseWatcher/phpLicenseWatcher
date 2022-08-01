<?php
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/lmtools.php";

db_connect($db);
$servers = db_get_servers($db, array('name', 'license_manager', 'lm_default_usage_reporting'));
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
 * @param $servers active servers list array.
 */
function update_servers(&$db, $servers) {
    $update_data = array();
    $lmtools = new lmtools();
    foreach ($servers as $index => $server) {
        // Retrieve server details from lmtools.php object.
        $lmtools->lm_open($server['license_manager'], 'license_util__update_servers', $server['name']);
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
    $query = $db->prepare("UPDATE `servers` SET `status`=?, `version`=?, `last_updated`=NOW() WHERE `name`=?;");
    $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    foreach($name as $index => $_n) {
        $query->bind_param("sss", $status[$index], $server_version[$index], $name[$index]);
        if ($query->execute() === false) {
            $db->rollback();
            print_error_and_die($db, "MySQL: {$query->error}");
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
    $queries = array();

    // Populate feature, if needed.
    // ? = $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `features` (`name`, `show_in_lists`, `is_tracked`) VALUES (?, 1, 1);
    SQL;
    $queries['features'] = $db->prepare($sql);

    // Populate server/feature to licenses, if needed.
    // ?, ? = $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `licenses` (`server_id`, `feature_id`)
    SELECT DISTINCT `servers`.`id` AS `server_id`, `features`.`id` AS `feature_id` FROM `servers`, `features`
    WHERE `servers`.`name` = ? AND `features`.`name` = ?;
    SQL;
    $queries['licenses'] = $db->prepare($sql);

    // Insert license usage.  Needs feature and license populated, first.
    // ?, ?, ? = $lmdata['licenses_used'], $server['name'], $lmdata['feature']
    $sql = <<<SQL
    INSERT IGNORE INTO `usage` (`license_id`, `time`, `num_users`)
    SELECT `licenses`.`id`, NOW(), ? FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`=? AND `features`.`name`=? AND `features`.`is_tracked`=1;
    SQL;
    $queries['usage'] = $db->prepare($sql);
    // END setup DB queries.

    // Allows us to use exception handling of MySQL.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Get current autocommit status.  This will be restored after we're done.
    $result = $db->query("SELECT @@autocommit", MYSQLI_STORE_RESULT);
    $reset_autocommit = (bool) $result->fetch_row()[0];

    // MySQL transactions and table locks don't play well together, so we'll
    // disable autocommit before locking tables for writing.
    $db->autocommit(false);
    $db->query("LOCK TABLES `features` WRITE, `servers` READ, `licenses` WRITE, `usage` WRITE;");

    foreach ($servers as $server) {
        $licenses_data = lmtools::get_license_usage_array($server['license_manager'], $server['name'], 1);
        if (is_null($licenses_data)) continue;  // No licenses in use on this $server.
        if ($licenses_data === false) {
            db_cleanup($db, $queries, $reset_autocommit);
            print_error_and_die($db, "Error calling lmtools::get_license_usage_array()");
        }

        foreach ($licenses_data as $license_data) {
            // Translate uncounted licenses to 0 licenses used.
            if ($license_data['num_licenses_used'] === "uncounted") $license_data['num_licenses_used'] = "0";

            $feature       = $license_data['feature_name'];
            $name          = $server['name'];
            $licenses_used = $server['lm_default_usage_reporting'] === "0"
                ? $license_data['num_checkouts']
                : $license_data['num_licenses_used'];



            // INSERT license data to DB
            try {
                // Attempt to INSERT license usage...
                $queries['usage']->bind_param("iss", $licenses_used, $name, $feature);
                $queries['usage']->execute();

                // when affected_rows < 1, we will attempt to populate features
                // and licenses and re-run usage query.
                // 'INSERT IGNORE' in queries prevents unique key collisions.
                if ($db->affected_rows < 1) {
                    // Features table
                    $queries['features']->bind_param("s", $feature);
                    $queries['features']->execute();

                    // Licenses table
                    $queries['licenses']->bind_param("ss", $name, $feature);
                    $queries['licenses']->execute();

                    // Usage table
                    $queries['usage']->execute();
                }
            } catch (mysqli_sql_exception $e) {
                db_cleanup($db, $queries, $reset_autocommit);
                print_error_and_die($db, $e->getMessage());
            }
        } // END foreach ($licences_data as $license)
    } // END foreach ($servers as $server)

    // Complete and cleanup
    db_cleanup($db, $queries, $reset_autocommit);
} // END function update_licenses()

/**
 * Print error to STDERR, close DB, and exit 1.
 *
 * die() is insufficient because it prints to STDOUT and exits 0.
 *
 * @param &$db Database connection object.
 * @param $msg error message
 */
function print_error_and_die(&$db, $msg) {
    fprintf(STDERR, "%s\n", $msg);
    $db->close();
    exit(1);
}

/**
 * Commit DB queries, reset auto-increment counter, unlock tables, and close prepared statements.
 *
 * InnoDB will increment an auto-increment ID when a feature is not tracked, but
 * did exist.  We don't want this, so the auto-increment counters are manually
 * set back to max(id)+1.
 *
 * @param &$db Database connection object.
 * @param &$queries Array of prepared queries (statements) to be closed.
 * @param $reset_autoincrement The DB's auto increment state to be restored.
 */
function db_cleanup(&$db, &$queries, $reset_autocommit) {
    $db->commit();

    // Reset auto-increment ID columns for features and licenses
    foreach (array("features", "licenses") as $table) {
        $result = $db->query("SELECT max(id)+1 FROM `{$table}`;", MYSQLI_STORE_RESULT);
        $max_id = $result->fetch_row()[0];
        if (ctype_digit($max_id)) {
            $db->query("ALTER TABLE `{$table}` AUTO_INCREMENT = {$max_id};");
        } else {
            $table = ucfirst($table);
            print_error_and_die($db, "Error: {$table} table has no records.  Is the license server reachable?");
        }
    }

    $db->query("UNLOCK TABLES;");
    $db->autocommit($reset_autocommit);
    foreach ($queries as &$query) $query->close();
}
?>

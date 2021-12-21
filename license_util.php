<?php
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/lmtools.php";

db_connect($db);
$servers = db_get_servers($db, array('name', 'license_manager'));
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
    global $lmutil_binary;

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

    $lmtools = new lmtools();
    foreach ($servers as $server) {
        if ($lmtools->lm_open($server['license_manager'], 'license_util__update_licenses', $server['name']) === false) {
            db_cleanup($db, $queries, $reset_autocommit);
            print_error_and_die($db, $lmtools->err);
        }
        $lmdata = $lmtools->lm_nextline();
        if ($lmdata === false) {
            db_cleanup($db, $queries, $reset_autocommit);
            print_error_and_die($db, $lmtools->err);
        }
        // INSERT license data to DB
        while (!is_null($lmdata)) {
            // $lmdata['licenses_used'] will be missing when the feature has no license count ("uncounted").
            // But `usage`.`num_users` in the DB can't be null, so we'll fill in '0'.
            if (!array_key_exists('licenses_used', $lmdata)) $lmdata['licenses_used'] = 0;

            // DB operations
            try {
                // Attempt to INSERT license usage...
                $queries['usage']->bind_param("iss", $lmdata['licenses_used'], $server['name'], $lmdata['feature']);
                $queries['usage']->execute();

                // when affectedRows < 1, we will attempt to populate features
                // and licenses and re-run usage query.
                // 'INSERT IGNORE' in queries prevents unique key collisions.
                if ($db->affected_rows < 1) {
                    // Features table
                    $queries['features']->bind_param("s", $lmdata['feature']);
                    $queries['features']->execute();

                    // Licenses table
                    $queries['licenses']->bind_param("ss", $server['name'], $lmdata['feature']);
                    $queries['licenses']->execute();

                    // Usage table
                    $queries['usage']->execute();
                }
            } catch (mysqli_sql_exception $e) {
                db_cleanup($db, $queries, $reset_autocommit);
                print_error_and_die($db, $e->getMessage());
            }

            // Get another data set from license manager.
            $lmdata = $lmtools->lm_nextline();
            if ($lmdata === false) {
                db_cleanup($db, $queries, $reset_autocommit);
                print_error_and_die($db, $lmtools->err);
            }
        } // END while(!is_null($lmdata))
    } // END foreach($servers as $server)

    // Complete and cleanup
    db_cleanup($db, $queries, $reset_autocommit);
} // END function update_licenses()

/**
 * Print error to STDERR and exit 1.
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
        $db->query("ALTER TABLE `{$table}` AUTO_INCREMENT = {$max_id};");
    }

    $db->query("UNLOCK TABLES;");
    $db->autocommit($reset_autocommit);
    foreach ($queries as &$query) $query->close();
}
?>

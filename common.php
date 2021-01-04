<?php

// Load local config.
if (is_readable(__DIR__ . "/config.php")) {
	require_once __DIR__ . "/config.php";
} else {
    print_header();
    print <<< HTML
<h1>Missing Component</h1>
<p>Configuration file <code>config.php</code> could not be read.  Please notify your system administrator.
HTML;
	  print_footer();
	  exit;
}

// Constants
// Server status messages.
define ('SERVER_UP', "UP");
define ('SERVER_DOWN', "DOWN");
define ('SERVER_VENDOR_DOWN', "VENDOR DOWN");

// $lmutil_loc has been renamed $lmutil_binary, but master branch still has $lmutil_loc.
if (isset($lmutil_loc) && !isset($lmutil_binary)) {
    $lmutil_binary = $lmutil_loc;
    unset($lmutil_loc);
}

// Common functions
/** Print HTML header */
function print_header() {
    print file_get_contents(__DIR__ . '/header.html');
}

/** Print HTML footer */
function print_footer() {
    print file_get_contents(__DIR__ . '/footer.html');
}

/**
 * Open persistent mysqli DB connection.
 *
 * @param &$db DB connection object.
 */
function db_connect(&$db) {
    // From config.php
    global $db_hostname, $db_username, $db_password, $db_database;

    // Make sure DB config exists.
    switch (false) {
    case isset($db_hostname):
    case isset($db_username):
    case isset($db_password):
    case isset($db_database):
        die ("Check database configuration.");
    }

    // Using persistent connection as denoted by 'p:' in host string.
    $db = new mysqli("p:{$db_hostname}", $db_username, $db_password, $db_database);
    if (!is_null($db->connect_error)) {
        die("Database Connect Error {$db->connect_errno}: {$db->connect_error}");
    }
}

/**
 * Retrieve details of one or more servers from DB.
 *
 * $cols are the columns queried.  Can be a string or array of strings.
 * When $cols is omitted or NULL, all columns are queried.
 * $ids are the server IDs to lookup.  Can be an int or array of ints.
 * When $ids is omitted as an argument, all ACTIVE servers are retrieved from DB.
 *
 * @param object &$db mysqli DB object with open connection.
 * @param array $cols optional list of columns to lookup per row.  Lookup all cols when omitted/empty.
 * @param array $ids optional list if server IDs to lookup.  Lookup all IDs when omitted/empty.
 * @param string $order_by optional column to sort server list by.
 * @param bool $is_active optional column to filter results by only active servers.
 * @return array list of servers.
 */
function db_get_servers(object &$db, array $cols=array(), array $ids=array(), string $order_by="", bool $is_active=true) {
    if (get_class($db) !== "mysqli") {
        die ("DB not connected when calling db_get_servers().");
    }

    // Determine columns to query.  Query all columms when $cols is empty.
    if (empty($cols)) {
        $cols_queried = "*";
    } else {
        // creates column list like "`id`, `name`, `label`" to be used in SELECT query.
        $cols_queried = "`" . implode("`, `", $cols) . "`";
    }

    // Determine server IDs to query.  Query all servers when $ids is empty.
    if (empty($ids)) {
        $ids_queried = "";  // so that query is "WHERE `is_active`=?"
    } else {
        // Remove IDs that are not numeric.
        $ids = array_filter($ids, 'ctype_digit');

        // Ensure IDs are ints.
        $ids = array_map('intval', $ids);

        // Build something like "`id` IN (2, 3, 5, 8) AND" so that query says
        // "WHERE `id` IN (2, 3, 5, 8) AND `is_active`=1"
        $ids_queried = "`id` IN (" . implode(", ", $ids) . ") AND";
    }

    $is_active_queried = $is_active ? "`is_active`=1" : "TRUE";
    $ordered_by_queried = !empty($order_by) ? "ORDER BY `{$order_by}` ASC" : "";

    $result = $db->query("SELECT {$cols_queried} FROM `servers` WHERE {$ids_queried} {$is_active_queried} {$order_by_queried};", MYSQLI_STORE_RESULT);
    if (!$result) {
        die ($db->error);
    }

    $servers = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $servers;
}

/**
 * Debug helper function to print preformatted SQL code to browser.
 *
 * This is extremely similar to print_var().  Possibly remove this function in the future.
 *
 * @param string $sql SQL code to be shown in browser.
 */
function print_sql ($sql) {
    global $debug; // from config.php
    if (isset($debug) && $debug == 1) {
        $code = print_r($sql, true);
        print "<p><span style='color: crimson;'>Executing SQL: </span><pre>{$code}</pre>\n";
    }
}

/**
 * Debug helper function to print preformatted var_export() to browser
 *
 * @param mixed $var variable to be exported to browser view.
 */
function print_var ($var) {
    global $debug; // from config.php
    if (isset($debug) && $debug == 1) {
        $code = htmlentities(var_export($var, true), ENT_COMPAT | ENT_HTML5);
        print "<p><span style='color: crimson;'>Var Export: </span><pre>{$code}</pre>\n";
    }
}

?>

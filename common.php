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

// Features admin view controls
// Open Source SVG Material Icons by Google Fonts.
define("SEARCH_ICON", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>');
define("CANCEL_SEARCH", '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M15.5,14h-0.79l-0.28-0.27C15.41,12.59,16,11.11,16,9.5C16,5.91,13.09,3,9.5,3C6.08,3,3.28,5.64,3.03,9h2.02 C5.3,6.75,7.18,5,9.5,5C11.99,5,14,7.01,14,9.5S11.99,14,9.5,14c-0.17,0-0.33-0.03-0.5-0.05v2.02C9.17,15.99,9.33,16,9.5,16 c1.61,0,3.09-0.59,4.23-1.57L14,14.71v0.79l5,4.99L20.49,19L15.5,14z"/><polygon points="6.47,10.82 4,13.29 1.53,10.82 0.82,11.53 3.29,14 0.82,16.47 1.53,17.18 4,14.71 6.47,17.18 7.18,16.47 4.71,14 7.18,11.53"/></g></g></svg>');
define("EMPTY_CHECKBOX", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#337ab7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>');
define("CHECKED_CHECKBOX", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#337ab7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM17.99 9l-1.41-1.42-6.59 6.59-2.58-2.57-1.42 1.41 4 3.99z"/></svg>');
define("PREVIOUS_PAGE", '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.61 7.41L14.2 6l-6 6 6 6 1.41-1.41L11.03 12l4.58-4.59z"/></svg>');
define("NEXT_PAGE", '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M10.02 6L8.61 7.41 13.19 12l-4.58 4.59L10.02 18l6-6-6-6z"/></svg>');
define("ROWS_PER_PAGE", 50);

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

/** apply trim() to entirety of superglobal $_POST */
function clean_post() {
    $_POST = array_map('trim', $_POST);
    // Prevent XSS
    $_POST = array_map('htmlspecialchars', $_POST);
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
    $order_by_queried = !empty($order_by) ? "ORDER BY `{$order_by}` ASC" : "";

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

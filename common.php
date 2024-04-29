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

// Page size for features table
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
 * @param &$db Database connection object.
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

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Using persistent connection as denoted by 'p:' in host string.
        $db = new mysqli("p:{$db_hostname}", $db_username, $db_password, $db_database);
        $db->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        error_log($e);
        die("DB Connection Error: {$e->getMessage()}");
    }
}

/**
 * Database lookup for feature and server name/label by license ID.
 *
 * @param object &$db Database connection object.
 * @param int $license_id
 * @return array ['feature_name', 'feature_label', 'server_name', 'server_label']
 */
function db_get_license_params(object &$db, int $license_id) {
    $results = [];

    $sql = <<<SQL
    SELECT `features`.`name`, `features`.`label`, `servers`.`name`, `servers`.`label`
    FROM `licenses`
    JOIN `features` ON `licenses`.`feature_id` = `features`.`id`
    JOIN `servers` ON `licenses`.`server_id` = `servers`.`id`
    WHERE `licenses`.`id` = ?
    SQL;

    $query = $db->prepare($sql);
    $query->bind_param("i", $license_id);
    $query->execute();
    $query->bind_result($results['feature_name'], $results['feature_label'], $results['server_name'], $results['server_label']);
    $query->fetch();
    $query->close();

    return $results;
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
 * Get HTML block to display a bootstrap alert
 *
 * @param string $msg Alert message to display
 * @param string $lvl Alert level ("success", "failure", or default of info alert);
 * @return string HTML block of alert to be added to view.
 */
function get_alert_html($msg, $lvl="info") {
    switch($lvl) {
    case "success":
        $msg = "&#10004; " . $msg;  // prepend checkmark
        $class = "alert-success";   // bootstrap class
        break;
    case "failure":
        $msg = "&#10006; " . $msg;  // prepend crossmark
        $class = "alert-danger";    // bootstrap class
        break;
    default:
        $class = "alert-info";      // bootstrap class
        break;
    }

    return <<<HTML
    <div class='alert {$class} alert-dismissible' role='alert'>
        {$msg}
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
            <span aria-hidden='true'>&times;</span>
        </button>
    </div>
    HTML;

}

function get_not_polled_notice() {
    return get_alert_html(
        "No servers have been polled. Make sure that license_util.php, licence_cache.php, and license_alert.php are setup on a cron schedule.",
        "info"
    );
}

/**
 * Send response data to Ajax request.
 *
 * @param string $data Ajax response data
 */
function ajax_send_data($data, $mime='plain/text') {
    header("Content-Type: {$mime}");
    print $data;
} // END function ajax_send_data()

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
        print "<p><span class='red-text'>Executing SQL: </span><pre>{$code}</pre>\n";
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
        print "<p><span class='red-text'>Var Export: </span><pre>{$code}</pre>\n";
    }
}

/**
 * Debug helper function to send var_export() to a file.
 *
 * The files are written to /opt/debug/ and should be something like
 * "var-0.txt", "var-db.txt", etc.  If the files aren't being written, check
 * if /opt/debug/ exists and that user 'www-data' can write to it.
 *
 * @param mixed $var variable data to be exported to file.
 * @param mixed $label label appended to logged variable file.
 */
function log_var($var, $label="0") {
    global $debug; // from config.php
    if (isset($debug) && $debug == 1 && is_dir("/opt/debug")) {
        $export = var_export($var, true);
        file_put_contents("/opt/debug/var-{$label}.log", $export);
    }
}
?>

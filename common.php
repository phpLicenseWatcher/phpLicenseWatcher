<?php

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
} else {
    print_header();
    print <<< HTML
<h1>Missing Components</h1>
<p>Cannot find composer packages.  Please notify your system administrator.
HTML;
    print_footer();
    exit;
}

if (is_readable(__DIR__ . '/config.php')) {
	require_once(__DIR__ . '/config.php');
} else {
    print_header();
    print <<< HTML
<h1>Missing Component</h1>
<p>Configuration file <code>config.php</code> does not exist.  Please notify your system administrator.
HTML;
	  print_footer();
	  exit;
}

function print_header() {
    print file_get_contents(__DIR__ . '/header.html');
}

function print_footer() {
    print file_get_contents(__DIR__ . '/footer.html');
}

function db_connect(&$db) {
    global $db_type, $db_hostname, $db_username, $db_password, $db_database;

    // Make sure DB config exists.
    switch (false) {
    case isset($db_type):
    case isset($db_hostname):
    case isset($db_username):
    case isset($db_password):
    case isset($db_database):
        die ("Check database configuration.");
    }

    $dsn = array(
        'phptype'  => $db_type,
        'username' => $db_username,
        'password' => $db_password,
        'hostspec' => $db_hostname,
        'database' => $db_database,
    );

    // q.v. https://pear.php.net/manual/en/package.database.db.db-common.setoption.php
    $options = array(
        'autofree'       => false,
        'debug'          => 0,
        'persistent'     => true,
        'portability'    => DB_PORTABILITY_NONE,
        'seqname_format' => '%s_seq',
        'ssl'            => false,
    );

    $db =& DB::connect($dsn, $options);
    if (DB::isError($db)) {
    	die ($db->getMessage());
    }
}

/**
 * Retrieve `id`, `name`, and `label` of one or more servers from DB.
 *
 * When $ids is specified, it is expected to be an array of server ids to lookup.
 * When $ids is omitted as an argument, ALL active servers are retrieved from DB.
 *
 * @param object $db
 * @param $ids
 * @return mixed details of a single server or an array of servers.
 */
function db_get_servers(&$db, $ids=null) {
    global $db_type;
    if (get_class($db) !== "DB_{$db_type}") {
        die ("DB not connected when doing server lookup by ID.");
    }

    // $sql statement depends on whether a list of lookup $ids is provided or not.
    if (is_null($ids)) {
        // When $ids is null, retrieve entire server list.
        $sql = "SELECT `id`, `name`, `label` FROM `servers` WHERE `is_active` = ?;";
        $params = array(1);
    } else {
        // Ensure IDs are ints and placed in $params.
        // int 1 is appened to $params so that "`is_active` = 1" can be tested.
        if (is_array($ids)) {
            $params = array_map('intval', $ids);
            $params[] = 1;
        } else {
            $params = array(intval($ids), 1);
        }

        // Build string "(?,?,? ... )" so that "WHERE `id` IN (?,?,? ... )" is tested.
        $param_holders = "(?";
        for ($i = 2; $i < count($params); $i++) {
            $param_holders .=",?";
        }
        $param_holders .=")";

        $sql = "SELECT `id`, `name`, `label` FROM `servers` WHERE `id` IN {$param_holders} AND `is_active` = ?;";
    }

    $servers = $db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    if (DB::isError($db)) {
        die ($db->getMessage());
    }

    // $servers is an array, but when count() is 1, return array element instead.
    return count($servers) === 1 ? $servers[0] : $servers;
}

// Debug helper functions.
function print_sql ($sql) {
    global $debug;
    if (isset($debug) && $debug == 1) {
        $code = print_r($sql, true);
        print "<p><span style='color: #dc143c;'>Executing SQL: </span><pre>{$code}</pre>\n";
    }
}

function print_var ($var) {
    global $debug;
    if (isset($debug) && $debug == 1) {
        $code = var_export($var, true);
        print "<p><span style='color: #dc143c;'>Var Export: </span><pre>{$code}</pre>\n";
    }
}

?>

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

function db_get_servers(&$db) {
    global $db_type;
    if (get_class($db) !== "DB_{$db_type}") {
        die ("DB not connected when requesting server list.");
    }

    $sql = "SELECT `id`, `name`, `label` FROM `servers` WHERE `is_active` = ?;";
    $servers = $db->getAll($sql, array(1), DB_FETCHMODE_ASSOC);
    if (DB::isError($db)) {
        die ($db->getMessage());
    }

    return $servers;
}

function db_get_server_by_ids(&$db, $ids) {
    global $db_type;
    if (get_class($db) !== "DB_{$db_type}") {
        die ("DB not connected when doing server lookup by ID.");
    }

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
    $servers = $db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    if (DB::isError($db)) {
        die ($db->getMessage());
    }

    // $servers is an array, but when sizeof is 1, return array element.
    if (count($servers) === 1) {
        return $servers[0];
    } else {
        return $servers;
    }
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

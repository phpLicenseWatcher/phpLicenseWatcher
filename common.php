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

function db_connect() {
    global $db_hostname, $db_username, $db_password, $dsn;

    switch (false) {
    case isset($db_hostname):
    case isset($db_username):
    case isset($db_password):
    case isset($dsn):
        die ("Check database configuration.");
    }

    $db = DB::connect($dsn, true);
    if (DB::isError($db)) {
    	die ($db->getMessage());
    }

    return $db;
}

function db_get_servers($db) {
    global $db_type;
    if (get_class($db) !== "DB_{$db_type}") {
        die ("DB not connected when requesting server list.");
    }

    $sql = "SELECT `name`, `label` FROM `servers` WHERE `is_active` = 1;";
    $servers = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    if (DB::isError($db)) {
        die ($db->getMessage());
    }

    return $servers;
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

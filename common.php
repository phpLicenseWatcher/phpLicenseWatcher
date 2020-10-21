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

    $db = DB::connect($dsn, $options);
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

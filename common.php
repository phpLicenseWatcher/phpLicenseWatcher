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
 * Retrieve details of one or more servers from DB.
 *
 * $cols are the columns queried.  Can be a string or array of strings.
 * When $cols is omitted or NULL, all columns are queried.
 * $ids are the server IDs to lookup.  Can be an int or array of ints.
 * When $ids is omitted as an argument, all ACTIVE servers are retrieved from DB.
 *
 * @param object $db DB object with open connection.
 * @param $cols optional list of columns to lookup per row.  Lookup all cols when null.
 * @param $ids optional list if server IDs to lookup.  Lookup all IDs when null.
 * @return array list of servers.
 */
function db_get_servers(&$db, $cols=null, $ids=null) {
    global $db_type;
    if (get_class($db) !== "DB_{$db_type}") {
        die ("DB not connected when doing server lookup by ID.");
    }

    // Determine columns to query.  Query all columms when $cols is null.
    if (is_null($cols)) {
        $cols_queried = "*";
    } else {
        if (!is_array($cols)) {
            $cols = array($cols);
        }

        // creates something like "`id`, `name`, `label`"
        $cols_queried = "`". implode("`, `", $cols) . "`";
    }

    // Determine server IDs to query.  Query all servers when $ids is null.
    if (is_null($ids)) {
        $ids_quered = "";  // so that query is "WHERE `is_active`=1"
    } else {
        // Ensure that $ids is an array.
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        // Remove IDs that are not ints.  Non-int IDs are mapped to -1 and ...
        $ids = array_map(function($id) {
            return ctype_digit($id) ? intval($id) : -1;
        }, $ids);

        // ... then filtered out of the $ids list.
        $ids = array_filter($ids, function($id) {
            return $id > -1;
        });

        // Build something like "`id` IN (3, 5, 7, 11) AND" so that query says
        // "WHERE `id` IN (3, 5, 7, 11) AND `is_active`=1"
        $ids_queried = "`id` IN (" . implode(", ", $ids) . ") AND";
    }

    $sql = "SELECT {$cols_queried} FROM `servers` WHERE {$ids_queried} `is_active` = 1;";
    $servers = $db->getAll($sql, arrray(), DB_FETCHMODE_ASSOC);
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

<?php

require_once("common.php");
include_once('DB.php');
include_once('HTML/Table.php');

define("PASS_MARK", "<span class='green-text'>&#10004; GOOD</span>");  // green heavy checkmark
define("FAIL_MARK", "<span class='red-text'>&#10006; FAIL</span>");    // red cross mark
define("REQUIRED_PHP", "5.3.0");

// Parallel arrays
$test_names   = array();
$test_values  = array();
$test_results = array();

// Calculate minimum php version id for first test.
$ver = explode(".", REQUIRED_PHP);
$minimum_php_version_id = $ver[0] * 10000 + $ver[1] * 100 + $ver[2];

$test           = PHP_VERSION_ID >= $minimum_php_version_id;
$test_names[]   = "PHP Version At Least " . REQUIRED_PHP;
$test_values[]  = phpversion();
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

// TO DO: Expand this test to validate config.php values.
$test           = is_readable("config.php");
$test_names[]   = "<code>config.php</code>";
$test_values[]  = $test ? "Readable" : "Not Readable";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test           = extension_loaded("gd");
$test_names[]   = "PHP Extension \"gd\" (required for graphs)";
$test_values[]  = $test ? "Installed and Enabled" : "Not Found";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test           = extension_loaded("xml");
$test_names[]   = "PHP Extension \"xml\" (required by pear DB class)";
$test_values[]  = $test ? "Installed and Enabled" : "Not Found";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test           = class_exists("HTML_Table");
$test_names[]   = "Pear HTML Table Class";
$test_values[]  = $test ? "Installed" : "Not Found";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test           = class_exists("DB");
$test_names[]   = "Pear DB Class";
$test_values[]  = $test ? "Installed" : "Not Found";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test           = isset($lmutil_loc) && is_executable($lmutil_loc);
$test_names[]   = "<code>lmutil</code>";
$test_values[]  = $test ? "Is Executable" : "Not Executable (maybe check permissions?)";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

$test = (bool) function() {
    switch (false) {
    case isset($db_type):
    case isset($db_username):
    case isset($db_password):
    case isset($db_hostname):
    case isset($db_database):
        return false;
    }

    $dsn = array(
        'phptype'  => $db_type,
        'username' => $db_username,
        'password' => $db_password,
        'hostspec' => $db_hostname,
        'database' => $db_database,
    );

    $db =& DB::connect($dsn, array('persistent' => false));
    if (DB::isError($db)) {
        return false;
    }

    $db->disconnect();
    return true;
};
$test_names[]   = "Database Connectivity";
$test_values[]  = $test ? "Connection OK" : "Connection Failed.";
$test_results[] = $test ? PASS_MARK : FAIL_MARK;

// Build rows for test results table.
$test_table_rows = "";
foreach ($test_names as $i => $test_name) {
    $test_table_rows .= <<< HTML
    <tr>
        <td>{$test_name}</td>
        <td>{$test_values[$i]}</td>
        <td>{$test_results[$i]}</td>
    </tr>

HTML;
}

// Display view.
print_header();

print <<< HTML
<h1>Check PHPlicensewatcher Installation</h1>
<hr/>

<p>This will check whether PHPlicensewatcher has been properly installed.
This is not an exhaustive check but checks for common installation and configuration issues.</p>
<table id='install-check'>
    <tr>
        <th>TEST</th>
        <th>VALUE</th>
        <th>RESULT</th>
    </tr>
{$test_table_rows}
</table>

HTML;

print_footer();
?>

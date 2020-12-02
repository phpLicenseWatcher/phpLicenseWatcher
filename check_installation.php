<?php

require_once __DIR__ . "/common.php";
include_once __DIR__ . "/html_table.php";

define("PASS_MARK", "<span class='green-text'>&#10004; GOOD</span>");  // green heavy checkmark
define("FAIL_MARK", "<span class='red-text'>&#10006; FAIL</span>");    // red cross mark
define("REQUIRED_PHP", "7.0.0");

$table = new html_table(array('id'=>"install-check"));
$table->add_row(array("TEST", "VALUE", "RESULT"), array(), "th");

// Calculate minimum php version id for first test.
$ver = explode(".", REQUIRED_PHP);
$minimum_php_version_id = $ver[0] * 10000 + $ver[1] * 100 + $ver[2];

$test         = PHP_VERSION_ID >= $minimum_php_version_id;
$test_names   = "PHP Version At Least " . REQUIRED_PHP;
$test_values  = phpversion();
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

// TO DO: Expand this test to validate config.php values.
$test         = is_readable("config.php");
$test_names   = "<code>config.php</code>";
$test_values  = $test ? "Readable" : "Not Readable";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

$test         = extension_loaded("gd");
$test_names   = "PHP Extension \"gd\" (required for graphs)";
$test_values  = $test ? "Installed and Enabled" : "Not Found";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

$test         = isset($lmutil_binary) && is_executable($lmutil_binary);
$test_names   = "<code>lmutil</code>";
$test_values  = $test ? "Is Executable" : "Not Executable<br>(maybe check permissions?)";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

$test = (bool) function() {
    switch (false) {
    case isset($db_username):
    case isset($db_password):
    case isset($db_hostname):
    case isset($db_database):
        return false;
    }

    $db = new mysqli($db_host, $db_user, $db_password, $db_database);
    if (!is_null($db->connect_error)) {
        return false;
    }

    $db->close();
    return true;
};
$test_names   = "Database Connectivity";
$test_values  = $test ? "Connection OK" : "Connection Failed.";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

// Display view.
print_header();

print <<< HTML
<h1>Check PHPlicensewatcher Installation</h1>
<hr/>

<p>This will check whether PHPlicensewatcher has been properly installed.
This is not an exhaustive check but checks for common installation and configuration issues.</p>
{$table->get_html()}

HTML;

print_footer();
?>

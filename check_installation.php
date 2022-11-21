<?php

require_once __DIR__ . "/common.php";
include_once __DIR__ . "/html_table.php";

if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require_once __DIR__ . "/vendor/autoload.php";
}
use PHPMailer\PHPMailer\PHPMailer;

define("PASS_MARK", "<span class='green-text'>&#10004; GOOD</span>");  // green heavy checkmark
define("FAIL_MARK", "<span class='red-text'>&#10006; FAIL</span>");    // red cross mark
define("REQUIRED_PHP", "7.3.0");

$table = new html_table(array('class'=>"table alt-rows-bgcolor"));
$table->add_row(array("TEST", "VALUE", "RESULT"), array(), "th");

// Calculate minimum php version id for first test.
$ver = explode(".", REQUIRED_PHP);
$minimum_php_version_id = $ver[0] * 10000 + $ver[1] * 100 + $ver[2];

$test         = PHP_VERSION_ID >= $minimum_php_version_id;
$test_names   = "PHP Version At Least " . REQUIRED_PHP;
$test_values  = phpversion();
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

// Check for 64-bit Unix timestamp
$test         = strtotime("9999-12-31") !== false;
$test_names   = "64-bit Unix Timestamp";
$test_values  = $test ? "64-bit" : "32-bit";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

// TO DO: Expand this test to validate config.php values.
$test         = is_readable("config.php");
$test_names   = "config.php";
$test_values  = $test ? "Readable" : "Not Readable";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

clearstatcache();
$test         = isset($lmutil_binary) && is_executable($lmutil_binary);
$test_names   = "lmutil binary";
$test_values  = $test ? "Is Executable" : "Not Executable (maybe check permissions?)";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

$test         = isset($monitorlm_binary) && is_executable($monitorlm_binary);
$test_names   = "monitorlm binary";
$test_values  = $test ? "Is Executable" : "Not Executable (maybe check permissions?)";
$test_results = $test ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

$test = call_user_func(function() {
    global $db_hostname, $db_username, $db_password, $db_database; // from config.php

    $err = array();
    if (!isset($db_hostname) || empty($db_hostname)) $err[] = '$db_hostname';
    if (!isset($db_username) || empty($db_username)) $err[] = '$db_username';
    if (!isset($db_password) || empty($db_password)) $err[] = '$db_password';
    if (!isset($db_database) || empty($db_database)) $err[] = '$db_database';
    if (!empty($err)) {
        $err = implode(", ", $err);
        return array(false, "{$err} not set in config.php");
    }

    // Using persistent connection as denoted by 'p:' in host string.
    $db = new mysqli("p:{$db_hostname}", $db_username, $db_password, $db_database);
    if (!is_null($db->connect_error)) {
        return array(false, $db->connect_error);
    }

    $db->close();
    return array(true);
});

$test_names   = "Database Connectivity";
$test_values  = $test[0] ? "Connection OK" : $test[1];
$test_results = $test[0] ? PASS_MARK : FAIL_MARK;
$table->add_row(array($test_names, $test_values, $test_results));

// Test to see if PHPMailer exists via composer
$test         = class_exists("PHPMailer\PHPMailer\PHPMailer", true);
$test_names   = "PHPMailer Library (via Composer)";
$test_values  = $test ? "Library Found" : "Library NOT Found";
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

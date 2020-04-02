<?php

require_once("common.php");
include_once('DB.php');
include_once('HTML/Table.php');

define("PASS_MARK", "<span class='green-text'>&#10004; GOOD</span>");  // green heavy checkmark
define("FAIL_MARK", "<span class='red-text'>&#10006; FAIL</span>");  // red cross mark
define("REQUIRED_PHP", 50300);  // PHP 5.3.0

$test     = PHP_VERSION_ID > REQUIRED_PHP;
$php_val  = phpversion();
$php_mark = $test ? PASS_MARK : FAIL_MARK;

$test    = function_exists("imagecreate");
$gd_val  = $test ? "Installed" : "Not installed";
$gd_mark = $test ? PASS_MARK : FAIL_MARK;

// TO DO: Expand this test to validate config.php values.
$test        = is_readable("config.php");
$config_val  = $test ? "Readable" : "Not Readable";
$config_mark = $test ? PASS_MARK : FAIL_MARK;

$test       = class_exists("HTML_Table");
$table_val  = $test ? "Installed" : "Not Installed";
$table_mark = $test ? PASS_MARK : FAIL_MARK;

$test        = is_executable($lmutil_loc);
$lmutil_val  = $test ? "Is executable" : "Not executable (maybe check permissions?)";
$lmutil_mark = $test ? PASS_MARK : FAIL_MARK;

$test = (bool) function() {
    if (isset($db_hostname) && isset($db_username) && isset($db_password)) {
    	$db = DB::connect($dsn, true);
        return !DB::isError($db);
    }

    return false;
};
$db_val  = $test ? "Connection OK" : "Connection Failed.";
$db_mark = $test ? PASS_MARK : FAIL_MARK;

print_header();
?>

<h1>Check PHPlicensewatcher installation</h1>
<hr/>

<p>This will check whether PHPlicensewatcher has been properly installed.
This is not an exhaustive check but checks for common installation and configuration issues.</p>

<table id="install-check">
    <caption>PHPlicensewatcher Installation Check</caption>
    <tr>
        <th>TEST</th>
        <th>VALUE</th>
        <th>RESULT</th>
    </tr><tr>
        <td>PHP Version</td>
        <td><?php print $php_val; ?></td>
        <td><?php print $php_mark; ?></td>
    </tr><tr>
        <td>Config File</td>
        <td><?php print $config_val; ?></td>
        <td><?php print $config_mark; ?></td>
    </tr><tr>
        <td>GD Support for Graphs</td>
        <td><?php print $gd_val; ?></td>
        <td><?php print $gd_mark; ?></td>
    </tr><tr>
        <td>Pear HTML Table Class</td>
        <td><?php print $table_val; ?></td>
        <td><?php print $table_mark; ?></td>
    </tr><tr>
        <td><code><?php print $lmutil_loc; ?></code></td>
        <td><?php print $lmutil_val; ?></td>
        <td><?php print $lmutil_mark; ?></td>
    </tr><tr>
        <td>Database connectivity</td>
        <td><?php print $db_val; ?></td>
        <td><?php print $db_mark; ?></td>
    </tr>
</table>
<?php
    print_footer();
?>

<?php
// Author: Peter Bailie (pbailie@github).
namespace phpLicenseWatcher;

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../code/reports/academic_reports/controller.php";
use phpLicenseWatcher\Reports\AcademicReports\controller as academic_reports;

// Sanitize $_GET, $_POST, and $_REQUEST to guard against XSS.
array_walk_recursive($_GET, function(&$v) { $v = htmlspecialchars($v); });
array_walk_recursive($_POST, function(&$v) { $v = htmlspecialchars($v); });
array_walk_recursive($_REQUEST, function(&$v) { $v = htmlspecialchars($v); });

$type = $_GET['type'] ?? "N/A";
$license_id = $_GET['license'] ?? -1;

switch($type) {
case "academic":
    if ($license_id < 1) academic_reports::select_license();
    else academic_reports::get_and_show_report($license_id);
    break;

default:
    $var_export = var_export($type, true);
    $msg = "Unknown Report: {$var_export}";
    throw new \UnexpectedValueException($msg);
}

// EOF

<?php
// Author: Peter Bailie (pbailie@github).
require_once __DIR__ . "/reports/academic_reports/controller.php";
use PhpLicenseWatcher\Reports\AcademicReports\controller as academic_reports;

// Sanitize $_GET and $_POST to guard against XSS.  Please do not use $_REQUEST.
array_walk_recursive($_GET, function(&$v) { $v = htmlspecialchars($v); });
array_walk_recursive($_POST, function(&$v) { $v = htmlspecialchars($v); });

$type = $_GET['type'] ?? "N/A";
$license_id = $_GET['license'] ?? -1;
switch($type) {
case "academic":
    if ($license_id < 1) academic_reports::select_license();
    else academic_reports::get_and_show_report($license_id);
    break;

default:
    error_log("Unknown report: " . var_export($type, true));
    die();
}

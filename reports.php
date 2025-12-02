<?php
// Author: Peter Bailie (pbailie@github).
require_once __DIR__ . "/reports/academic_reports/controller.php";
use PhpLicenseWatcher\Reports\AcademicReports\controller as academic_reports;

// Sanitize $_GET and $_POST to guard against XSS.  Please do not use $_REQUEST.
array_walk_recursive($_GET, function(&$v) { $v = htmlspecialchars($v); });
array_walk_recursive($_POST, function(&$v) { $v = htmlspecialchars($v); });

$type = $_GET['type'] ?? "";
$license_id = $_GET['license'] ?? -1;
switch($type) {
case "academic":
    print $license_id < 1 ? academic_reports::select_license() : academic_reports::show_report($license_id);
    break;

default:
    // Do nothing by printing blank to the view.
    print "";
    break;
}

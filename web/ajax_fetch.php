<?php
// Author: Peter Bailie (pbailie@gihtub)
namespace phpLicenseWatcher;
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../code/reports/academic_reports/controller.php";
require_once __DIR__ . "/../code/reports/utils/autocomplete.php";
use phpLicenseWatcher\Reports\AcademicReports\controller as academic_reports;
use phpLicenseWatcher\Reports\Utils\autocomplete;

// Sanitize $_GET to guard against XSS.
array_walk_recursive($_GET, function(&$v) { $v = htmlspecialchars($v); });

$a = $_GET['a'] ?? "N/A";
switch ($a) {
case "autocomplete":
    $send = autocomplete();
    break;

case "graphs":
    $send = graphs();
    break;

default:
    $var_export = var_export($a, true);
    $msg = "Unknown AJAX request: {$var_export}";
    throw new \UnexpectedValueException($msg);
}

header('Content-Type: application/json');
print json_encode($send);
exit;

function graphs() {
    $b = $_GET['b'] ?? "N/A";
    $graphing_data = null;

    switch($b) {
    case "academic":
        $license_id = $_GET['license'] ?? -1;
        $graphing_data = academic_reports::fetch_graphing_data($license_id);
        break;

    default:
        $var_export = var_export($b, true);
        $msg = "Unknown graph fetch request: {$var_export}";
        throw new \UnexpectedValueException($msg);
    }

    return $graphing_data;
}

function autocomplete() {
    $b = $_GET['b'] ?? "N/A";
    $term = $_GET['term'] ?? "";
    $autocomplete = null;

    switch($b) {
    case "lookup_servers_autocomplete":
        $autocomplete = autocomplete::lookup_servers($term);
        break;

    case "lookup_licenses_autocomplete":
        $server_id = $_GET['server_id'] ?? -1;
        $autocomplete = autocomplete::lookup_licenses_by_server($term, $server_id);
        break;

    default:
        $var_export = var_export($b, true);
        $msg = "Unknown autocomplete request: {$var_export}";
        throw new \UnexpectedValueException($msg);
    }

    return $autocomplete;
}

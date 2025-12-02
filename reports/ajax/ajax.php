<?php
// Author: Peter Bailie (pbailie@gihtub)
namespace PhpLicenseWatcher\Reports\Ajax;
require_once __DIR__ . "/../utils/autocomplete.php";
use PhpLicenseWatcher\Reports\utils\autocomplete;

// Sanitize $_GET to guard against XSS.
array_walk_recursive($_GET, function(&$v) { $v = htmlspecialchars($v); });

$a = $_GET['a'] ?? "N/A";
switch ($a) {
case "autocomplete":
    $send = autocomplete();
    break;

default:
    error_log("Unknown AJAX request: {$a}");
    die();
}

header('Content-Type: application/json');
print json_encode($send);
exit;

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
        $msg = "Unknown AJAX autocomplete request: {$b}";
        error_log($msg);
        die($msg);
    }

    return $autocomplete;
}

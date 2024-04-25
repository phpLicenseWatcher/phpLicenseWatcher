<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

// URL arg check.  Halt immediately if any arg check fails.
$license_id = htmlspecialchars($_GET['license'] ?? "");
$range = htmlspecialchars($_GET['range'] ?? "");

// Validate $_GET['license'].  Only numeric chars are allowed in $_GET['license'].
if (!ctype_digit($license_id)) die;

// Validate $_GET['range'] and setup data parameters as needed.
switch ($range) {
case "day":
    $daterange_sql = "AND DATE_SUB(NOW(), INTERVAL 1 DAY) <= DATE(`time`)";
    $datestamp = "H:i";
    break;
case "week":
    $daterange_sql = "AND DATE_SUB(NOW(), INTERVAL 1 WEEK) <= DATE(`time`)";
    $datestamp = "Y-m-d H";
    break;
case "month":
    $daterange_sql = "AND DATE_SUB(NOW(), INTERVAL 1 MONTH) <= DATE(`time`)";
    $datestamp = "Y-m-d";
    break;
case "year":
    $daterange_sql = "AND DATE_SUB(NOW(), INTERVAL 1 YEAR) <= DATE(`time`)";
    $datestamp = "Y-m-d";
    break;
case "yearly":
    $daterange_sql = "";
    $datestamp = "Y";
    break;
default:
    // $_GET['days'] invalid.  Halt immediately.
    die;
}

// SQL for usage data by license_id
$sql = <<<SQL
SELECT `time`, `num_users`
FROM `usage`
WHERE `license_id` = ? {$daterange_sql}
GROUP BY `license_id`, `time`
ORDER BY `time` ASC
SQL;

db_connect($db);

// Get feature info from license_id
$feature = db_get_license_params($db, $license_id);
$feature = $feature['feature_label'] ?? $feature['feature_name'];

// Do DB query to get usage data for this license
$query = $db->prepare($sql);
$query->bind_param("i", $license_id);
$query->execute();
$query->bind_result($time, $usage);

// Graph data X-axis = $date, Y-axis = $usage
$data = [];
while ($query->fetch()) {
    $date = date($datestamp, strtotime($time));

    // $usage has multiple data points throughout a single day.
    // This ensures the largest $usage is set to $data per $date.
    $usage = (int) $usage;
    if ($usage > ($data[$date] ?? PHP_INT_MIN)) $data[$date] = $usage;
}

// END of DB operations.
$query->close();
$db->close();

// Format retrieved data into a JSON formatted data table and return via AJAX.
$table = ['cols'=>[], 'rows'=>[]];
$table["cols"][0] = ['id' => "", 'label' => "Date", 'pattern' => "", 'type' => "string"];
$table["cols"][1] = ['id' => "", 'label' => $feature, 'pattern' => "", 'type' => "number"];
foreach ($data as $date => $usage) {
    $row[0] = ['v' => $date];  // Graph X-coordinate
    $row[1] = ['v' => $usage];  // Graph Y-coordinate
    $table['rows'][] = ['c' => $row];
}

header('Content-Type: application/json');
print json_encode($table);

?>

<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

// URL arg check -- only numeric chars are allowed in $_GET['license'] and $_GET['days']
// Halt immediately if either arg check fails.
$license_id = htmlspecialchars($_GET['license'] ?? "");
$days = htmlspecialchars($_GET['days'] ?? "");
if (!ctype_digit($license_id) || !ctype_digit($days)) die;

// SQL for usage data from license_id
$sql = <<<SQL
SELECT `time`, `num_users`
FROM `usage`
WHERE `license_id` = ? AND DATE_SUB(NOW(), INTERVAL ? DAY) <= DATE(`time`)
GROUP BY `license_id`, `time`
ORDER BY `time` ASC
SQL;

db_connect($db);

// Get feature info from license_id
$feature = db_get_license_params($db, $license_id);
$feature = $feature['feature_label'] ?? $feature['feature_name'];

// Do DB query to get usage data for this license
$query = $db->prepare($sql);
$query->bind_param("ii", $license_id, $days);
$query->execute();
$query->bind_result($time, $usage);

// Graph data X-axis = $date, Y-axis = $usage
$data = [];
while ($query->fetch()) {
    switch($days) {
    case 1:
        $date = date("H:i", strtotime($time));
        break;
    case 7:
        $date = date("Y-m-d H", strtotime($time));
        break;
    case 30:
    case 365:
        $date = date("Y-m-d", strtotime($time));
        break;
    }

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

<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

/* URL arg check.  Halt all operation when invalid chars are found.  These
   regex's invalidate the entire input string when a single invalid
   char is found by replacing the whole input string with empty string.
*/
// Only numeric chars are allowed.
$license_id = preg_replace("/^(?![\d]+$).*$/", "", htmlspecialchars($_GET['license'] ?? ""));
// Only numeric chars are allowed.
$days = preg_replace("/^(?![\d]+$).*$/", "", htmlspecialchars($_GET['days'] ?? ""));
// If a URL arg is missing or invalid, halt here.
if ($license === "" || (int) $days === 0) exit;

$sql = <<<SQL
SELECT `features`.`name`, `usage`.`time`, SUM(`usage`.`num_users`)
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `licenses`.`id` = ? AND DATE_SUB(NOW(), INTERVAL ? DAY) <= DATE(`time`)
GROUP BY `features`.`name`, `usage`.`time`
ORDER BY `time` ASC;
SQL;

db_connect($db);
$query = $db->prepare($sql);
$query->bind_param("ii", $license_id, $days);
$query->execute();
$query->bind_result($feature, $time, $usage);

// $row[0] = feature name (aka product).
// $row[1] = date
// $row[2] = SUM(num_users)
while ($query->fetch()) {
    switch($days) {
    case 1:
        $date = date('H:i', strtotime($time));
        break;
    case 7:
        $date = date('Y-m-d H', strtotime($time));
        break;
    default:
        $date = date('Y-m-d', strtotime($time));
        break;
    }

    if (!array_key_exists($feature, $products)) $products[$feature] = $feature;
    if (!array_key_exists($date, $table)) $table[$date] = [];

    // SUM(num_users) has multiple data points throughout a single day.
    // This ensures the largest SUM(num_users) is set each day within $table[date][product]
    if (isset($table[$date][$feature])) {
		//make sure to select the largest value if we are reducing the data by changing the date key
        if ($usage > $table[$date][$feature]) {
            $table[$date][$feature] = $usage;
        }
    } else {
        $table[$date][$feature] = $usage;
    }
}

$query->close();
$db->close();

$result = ['cols'=>[], 'rows'=>[]];
$result['cols'][] = ['id' => "", 'label' => "Date", 'pattern' => "", 'type' => "string"];
$table = [];
$products = [];

foreach (array_keys($products) as $product) {
    $result["cols"][] = ['id' => "", 'label' => $product, 'pattern' => "", 'type' => "number"];
}

foreach (array_keys($table) as $date) {
    $ta = [];
    $ta[] = ['v' => $date];
    foreach (array_keys($products) as $product) {
        if (array_key_exists($product, $table[$date])) {
            $ta[] = ['v' => $table[$date][$product]];
        }
    }

    $result['rows'][] = ['c' => $ta];
}

header('Content-Type: application/json');
echo json_encode($result);

?>

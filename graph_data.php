<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

$license = preg_replace("/^(?!(all|(?!\|)[\d|]+(?<!\|)$)).+$/", "", htmlspecialchars($_GET['license'] ?? ""));
$days = (int) preg_replace("/^(?!([\d]+$)).+$/", "", htmlspecialchars($_GET['days'] ?? ""));

file_put_contents("/opt/debug/vardump.txt", var_export($license, true));
file_put_contents("/opt/debug/vardump1.txt", var_export($days, true));

$crit = "";
if ($license === "all") {
    $lookup = " TRUE ";
} else if ($license !== "") {
    $licenses = [];
    foreach(explode("|", $license) as $i) {
        $licenses[] = "'{$i}'";
    }
    $licenses = implode(",", $licenses);
    $lookup = " `license`.`id` IN ({$licenses}) ";
} else {
    $lookup = " `features`.`show_in_lists`=1 ";
}

if ($days < 1) {
    exit;
}

$sql = <<<SQL
SELECT `features`.`name`, `usage`.`time`, SUM(`usage`.`num_users`)
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE {$lookup} AND DATE_SUB(NOW(), INTERVAL {$days} DAY) <= DATE(`time`)
GROUP BY `features`.`name`, `time`
ORDER BY `time` ASC;
SQL;

db_connect($db);
$recordset = $db->query($sql, MYSQLI_STORE_RESULT);
if (!$recordset) {
    die ($db->error);
}

// $row[0] = feature name (aka product).
// $row[1] = date
// $row[2] = SUM(num_users)
while ($row = $recordset->fetch_row()) {
    $feature = $row[0];
    $date = $row[1];
    $usage = $row[2];

    switch($days) {
    case 1:
        $date = date('H:i', strtotime($date));
        break;
    case 7:
        $date = date('Y-m-d H', strtotime($date));
        break;
    default:
        $date = date('Y-m-d', strtotime($date));
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

$recordset->free();
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

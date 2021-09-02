<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

$feature = preg_replace("/[^a-zA-Z0-9_|]+/", "", htmlspecialchars($_GET['feature']));
if (preg_match("/^(\d+|all)$/", $_GET['days'], $matches) === 1) {
     $days = $matches[1];
} else {
    // No processing when days doesn't match regex pattern.
    exit;
}

$types = "";
$params = array();
if ($feature === "all") {
    $where_features = "TRUE";
} else if ($feature !== "") {
    $params = explode("|", $feature);
    // Every $param is datatype "s" in a mysqli prepared statement.
    $types = str_repeat("s", count($params));
    // Creates a string like "?,?,?" as used in "`features`.`name` IN (?,?,?)", but matching the number of $params.
    $placeholders = implode(",", array_fill(0, count($params), "?"));
    $where_features = "`features`.`name` IN ({$placeholders})";
} else {
    $where_features = "WHERE `features`.`show_in_lists`=1";
}

if ($days === "all") {
    $where_days = "TRUE";
} else {
    // $days will be an integer for a mysqli prepared statement.
    $types .= "i";
    $params[] = $days;
    $where_days = "DATE_SUB(NOW(), INTERVAL ? DAY) <= DATE(`time`)";
}

$sql = <<<SQL
SELECT `features`.`name`, `time`, SUM(`num_users`)
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE {$where_features} AND {$where_days}
GROUP BY `features`.`name`, `time`
ORDER BY `time` ASC;
SQL;

$result = array("cols"=>array(), "rows"=>array() );
$result["cols"][] = array("id" => "", "label" => "Date", "pattern" => "", "type" => "string");
$table = array();
$products = array();

db_connect($db);
$query = $db->prepare($sql);
$query->bind_param($types, ...$params);
$query->execute();
$query->bind_result($row_name, $row_time, $row_sum);

while ($query->fetch()){
    $date = $row_time;
    if (is_numeric($days) && (int) $days === 1) {
        $date = date('H:i', strtotime($date));
    } else if (is_numeric($days) && (int) $days <= 7) {
        $date = date('Y-m-d H', strtotime($date));
    } else {
        $date = date('Y-m-d', strtotime($date));
    }

    if (!array_key_exists($row_name, $products)) $products[$row_name] = $row_name;
    if (!array_key_exists($date, $table))        $table[$date]        = array();

    // SUM(num_users) has multiple data points throughout a single day.
    // This ensures the largest SUM(num_users) is set each day within $table[date][product]
    if (isset($table[$date][$row_name])) {
		//make sure to select the largest value if we are reducing the data by changing the date key
        if ($row_sum > $table[$date][$row_name]) {
            $table[$date][$row_name] = $row_sum;
        }
    } else {
        $table[$date][$row_name] = $row_sum;
    }
}

$query->close();
$db->close();

foreach (array_keys($products) as $product) {
    $result["cols"][] = array("id" => "", "label" => $product, "pattern" => "", "type" => "number");
}

foreach (array_keys($table) as $date){
    $ta = array();
    $ta[] = array('v' => $date);
    foreach (array_keys($products) as $product) {
        $ta[] = array('v' => $table[$date][$product]);
    }

    $result['rows'][] = array('c' => $ta);
}

header('Content-Type: application/json');
echo json_encode($result);
?>

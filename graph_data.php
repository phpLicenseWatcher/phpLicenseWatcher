<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";

$feature = preg_replace("/[^a-zA-Z0-9_|]+/", "", htmlspecialchars($_GET['feature'])) ;
$days = intval($_GET['days']);

$crit = "";
if ($feature === "all") {
    $crit = " TRUE ";
} else if ($feature !== "") {
    $features = array() ;
    foreach(explode('|', $feature ) as $i) {
        $features[] = "'{$i}'";
    }

    $crit = " `features`.`name` IN ( " . implode(',', $features) . " ) ";
} else {
    $crit = " show_in_lists=1 ";
}

if ($days <= 0) {
    $days = 7;
}

db_connect($db);

$result = array("cols"=>array(), "rows"=>array() );
$result["cols"][] = array("id" => "", "label" => "Date", "pattern" => "", "type" => "string");
$table = array();
$products = array();

$sql = <<<SQL
SELECT `features`.`name`, `time`, SUM(`num_users`)
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE $crit AND DATE_SUB(NOW(), INTERVAL $days DAY) <= DATE(`time`)
GROUP BY `features`.`name`, `time`
ORDER BY `time` ASC;
SQL;

$result = $db->query($sql, MYSQLI_STORE_RESULT);
if (!$result) {
    die ($db->error);
}

while ($row = $result->fetch_row()){
    $date = $row[1];

    if ($days == 1) {
        $date = date('H:i', strtotime($date));
    } else if ($days <= 7) {
        $date = date('Y-m-d H', strtotime($date));
    } else {
        $date = date('Y-m-d', strtotime($date));
    }

    if (!array_key_exists($row[0], $products)) {
        $products[$row[0]] = $row[0];
    }

    if (!array_key_exists($date, $table)) {
        $table[$date] = array();
    }

    //[date][product] = value
    if (isset($table[$date][$row[0]])) {
		//make sure to select the largest value if we are reducing the data by changing the date key
        if ($row[2] > $table[$date][$row[0]]) {
            $table[$date][$row[0]] = $row[2];
        }
    } else {
        $table[$date][$row[0]] = $row[2];
    }
}


$resultr->free();
$db->disconnect();

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

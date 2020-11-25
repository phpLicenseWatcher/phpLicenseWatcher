<?php
require_once "common.php";
require_once "tools.php";
require_once "html_table.php";

db_connect($db);

// Check what we want to sort data on.
/* Debugging Notes: original queries are as follows:
 * Sort by date:
 * SELECT `date`,`feature`,count(*) FROM `events` WHERE `type`='DENIED' GROUP BY `feature`,`date` ORDER BY `feature`,`date` DESC;
 * Sort by user:
 * SELECT `date`,`feature`,count(*) AS `numdenials` FROM `events` WHERE `type`='DENIED'  GROUP BY `date`,`feature` ORDER BY `numdenials` DESC;
 * Sort by feature:
 * SELECT `date`,`feature`,count(*) FROM `events` WHERE `type`='DENIED' GROUP BY `date`,`feature` ORDER BY `date` DESC,`feature`;
 */
if (isset($_GET['sortby'])) {
    $sort_by = $_GET['sortby'];
} else {
    // default sort by "feature"
    $sort_by = "date";
}

switch ($sort_by) {
case "feature":
    $sql = <<<SQL
SELECT `time`, `features`.`name`, count(*)
FROM `events`
JOIN `licenses` ON `events`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `type`='DENIED'
GROUP BY `features`.`name`, `time`
ORDER BY `features`.`name`, `time` DESC;
SQL;
    break;

case "number":
    $sql = <<<SQL
SELECT `time`, `features`.`name`, count(*) AS `numdenials`
FROM `events`
JOIN `licenses` ON `events`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `type`='DENIED'
GROUP BY `time`, `features`.`name`
ORDER BY `numdenials` DESC;
SQL;
    break;

default:
    $sql = <<<SQL
SELECT `time`, `features`.`name`, count(*)
FROM `events`
JOIN `licenses` ON `events`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `type`='DENIED'
GROUP BY `time`, `features`.`name`
ORDER BY `time` DESC, `features`.`name`;
SQL;
}

$result = $db->query($sql);

if (!$result) {
    die ($db->error);
}

// Background colors for table data.
// Alternate background color per row for visibility.
$colors = array("transparent", "lavender");
$num_colors = count($colors);

// Create a new table object
$table_style = array('style'=>"border: 1px solid gray; padding: 1px; border-spacing: 2px;");
$table = new html_table($table_style);

// Define a table header
$header_style = array('style'=>"background: yellow;");
$col_headers = array("Date", "Feature", "Total number of denials");
$table->add_row($col_headers, $header_style, "th");

for ($i = 0, $data_row = $result->fetch_row(); $data_row; $i++, $data_row = $result->fetch_row()) {
    $color = $colors[$i % $num_colors];
    $table->add_row($data_row, array('style'=>"background: {$color};"));
    $table_row = $table->get_rows_count() - 1;
    $table->update_cell($table_row, 1, array('style'=>"text-align:right;"));
    $table->update_cell($table_row, 2, array('style'=>"text-align:right;"));
}

$result->free();
$db->close();

$select_box = build_select_box (array("Date", "Feature", "Number"), "sortby", $sort_by);

// Print View
print_header();
print <<<HTML
<h1>License Denials</h1>
<form>
<p>Sort by
{$select_box}
</form>
HTML;

print_sql($sql); // debug
print $table->get_html();
print_footer();

?>

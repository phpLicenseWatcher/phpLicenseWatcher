<?php
require_once "common.php";
require_once "tools.php";
require_once "HTML/Table.php";

// Create a new table object
$tableStyle = "border='1' cellpadding='1' cellspacing='2' ";
$table = new HTML_Table($tableStyle);
$table->setColAttributes(1, "align='right'");

// Define a table header
$headerStyle = "style='background: yellow;'";
$colHeaders = array("Date", "Feature", "Total number of denials");
$table->addRow($colHeaders, $headerStyle, "TH");

db_connect($db);

// Get a list of features that have been denied.
$sql = <<<SQL
SELECT DISTINCT `name`
FROM `features`
JOIN `licenses` ON `features`.`id`=`licenses`.`feature_id`
JOIN `events` ON `licenses`.`id`=`events`.`license_id`
WHERE `events`.`type`='DENIED';
SQL;

$result = $db->query($sql);
if (!$result) {
    die ($db->error);
}

// Color code features so it is easier to group them.
// Get a list of different colors.
$color = explode(",", $colors);
for ($i = 0; $row = $result->fetch_row(); $i++) {
    $features_color[$row[0]] = $color[$i];
}
$result->free();

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

while ($row = $result->fetch_row()) {
    $table->AddRow($row, "style='background: {$features_color[$row[1]]};'");
}

$result->free();
$db->close();

// Right align the 3 column
$table->updateColAttributes(2, "align='right'");

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

print_sql($sql);

$table->display();
print_footer();

?>

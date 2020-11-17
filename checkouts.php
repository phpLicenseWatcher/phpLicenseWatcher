<?php
require_once "common.php";
require_once "tools.php";
require_once "HTML/Table.php";

db_connect($db);

// Get license usage for the product specified in $feature
$sql = <<<SQL
SELECT DISTINCT `name`
FROM `features`
JOIN `licenses` ON `features`.`id`=`licenses`.`feature_id`
JOIN `events` ON `licenses`.`id`=`events`.`license_id`
WHERE `events`.`type`='OUT';
SQL;

$result = $db->query($sql, MYSQLI_STORE_RESULT);
if (!$result) {
	die ($db->error);
}

// Color code the features so it is easier to group them
// Get a list of different colors
$color = explode(",", $colors);
for ($i = 0; $row = $result->fetch_row(); $i++) {
    $features_color[$row[0]] = $color[$i];
}
$result->free();

// Check what we want to sort data on

/* Original queries would error out with MySQL when sql_mode=only_full_group_by
 * Additional debugging may be necessary.  Original queries are as follows:
 * Sort by date:
 * SELECT `flmevent_date`,`flmevent_user`,MAX(`flmevent_feature`),count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_date`,`flmevent_user` ORDER BY `flmevent_date`,`flmevent_user`,`flmevent_feature` DESC;
 * Sort by user:
 * SELECT `flmevent_date`,`flmevent_user`,MAX(`flmevent_feature`),count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_user`,`flmevent_date` ORDER BY `flmevent_user`,`flmevent_date`,`flmevent_feature` DESC;
 * Sort by feature:
 * SELECT `flmevent_date`,MAX(flmevent_user),`flmevent_feature`,count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_feature`,`flmevent_date` ORDER BY `flmevent_feature`,`flmevent_date`,`flmevent_user` DESC;
 */
$sql = <<<SQL
SELECT `events`.`time`, `events`.`user`, MAX(`features`.`name`), count(*)
FROM `events`
JOIN `licenses` ON `events`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `events`.`type`='OUT'
GROUP BY `events`.`time`, `events`.`user`
SQL;

if (isset($_GET['sortby'])) {
    $sort_by = $_GET['sortby'];
} else {
    // default sort by date.
    $sort_by = "date";
}

switch($sort_by) {
case "date":
    $sql .= "ORDER BY `events`.`time`, `events`.`user`, MAX(`features`.`name`) DESC;";
    break;
case "user":
    $sql .= "ORDER BY `events`.`user`, `events`.`time`, MAX(`features`.`name`) DESC;";
    break;
default:
    $sql .= "ORDER BY MAX(`features`.`name`), `events`.`time`, `events`.`user` DESC;";
}

$result = $db->query($sql, MYSQLI_STORE_RESULT);
if (!$result) {
    die ($db->error);
}

// Create a new table object
$tableStyle = "border='1' cellpadding='1' cellspacing='2'";
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1, "align='right'");

// Define a table header
$headerStyle = "style='background: yellow;'";
$colHeaders = array("Date", "User", "Feature", "Total number of checkouts");
$table->addRow($colHeaders, $headerStyle, "TH");

$table->updateColAttributes(3, "align='enter'");

// Right align the 3 column
$table->updateColAttributes(2, "align='right'");

// Add data rows to table
while ($row = $result->fetch_row()) {
    $table->AddRow($row, "style='background: {$features_color[$row[2]]};'");
}

$result->free();
$db->close();

// function build_select_box (&$html, $options, $name, $checked_val=null) {
$select_box = build_select_box(array("Date", "User", "Feature"), "sortby", $sort_by);

// Print View
print_header();

print <<< HTML
<h1>License Checkouts</h1>
<form>
<p>Sort by
{$select_box}
</p>
</form>
HTML;
print_sql($sql); // debug
$table->display();
print_footer();
?>

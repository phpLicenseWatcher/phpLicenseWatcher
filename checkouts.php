<?php
require_once __DIR__ . "/common.php";
require_once "HTML/Table.php";
require_once 'DB.php';

// First let's get license usage for the product specified in $feature
// Connect to the database.  Use persistent connections.
$db = DB::connect($dsn, true);
if (DB::isError($db)) {
	die ($db->getMessage());
}

$sql = <<<SQL
SELECT DISTINCT `name`
FROM `features`
JOIN `licenses` ON `features`.`id`=`licenses`.`feature_id`
JOIN `events` ON `licenses`.`id`=`events`.`license_id`
WHERE `events`.`type`='OUT';
SQL;

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
	die ($recordset->getMessage());
}

// Color code the features so it is easier to group them
// Get a list of different colors
$color = explode(",", $colors);
for ($i = 0; $row = $recordset->fetchRow(); $i++) {
    $features_color[$row[0]] = $color[$i];
}

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

switch ($_GET['sortby']) {
case "date":
    $sql .= "ORDER BY `events`.`time`, `events`.`user`, MAX(`features`.`name`) DESC;";
    break;
case "user":
    $sql .= "ORDER BY `events`.`user`, `events`.`time`, MAX(`features`.`name`) DESC;";
    break;
default:
    $sql .= "ORDER BY MAX(`features`.`name`), `events`.`time`, `events`.`user` DESC;";
}

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
    die ($recordset->getMessage());
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
while ($row = $recordset->fetchRow()) {
    $table->AddRow($row, "style='background: {$features_color[$row[2]]};'");
}

$recordset->free();
$db->disconnect();

// Print View
print_header();

print <<< HTML
<h1>License Checkouts</h1>
<form>
<p>Sort by
<select onChange='this.form.submit();' name="sortby">
	<option value="date">Date</option>
	<option value="user">User</option>
	<option value="feature">Feature</option>
</select>
</p>
</form>
HTML;

if (isset($debug) && $debug == true)
    print_sql($sql);

$table->display();
print_footer();
?>

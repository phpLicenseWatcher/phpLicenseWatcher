<?php
require_once "common.php";
require_once "HTML/Table.php";
require_once "DB.php";

// Create a new table object
$table = new HTML_Table($tableStyle);
$table->setColAttributes(1, "align='right'");

$tableStyle = "border='1' cellpadding='1' cellspacing='2' ";

// Define a table header
$headerStyle = "style='background: yellow;'";
$colHeaders = array("Date", "Feature", "Total number of denials");
$table->addRow($colHeaders, $headerStyle, "TH");

// Connect to the database.   Use persistent connections.
$db = DB::connect($dsn, true);
if (DB::isError($db)) {
    die ($db->getMessage());
}

// Get a list of features that have been denied.
$sql = <<<SQL
SELECT DISTINCT `name`
FROM `features`
JOIN `licenses` ON `features`.`id`=`licenses`.`feature_id`
JOIN `events` ON `licenses`.`id`=`events`.`license_id`
WHERE `events`.`type`='DENIED';
SQL;

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
    die ($recordset->getMessage());
}


// Color code features so it is easier to group them.
// Get a list of different colors.
$color = explode(",", $colors);
for ($i = 0; $row = $recordset->fetchRow(); $i++) {
    $features_color[$row[0]] = $color[$i];
}


// Check what we want to sort data on.

/* Debugging Notes: original queries are as follows:
 * Sort by date:
 * SELECT `date`,`feature`,count(*) FROM `events` WHERE `type`='DENIED' GROUP BY `feature`,`date` ORDER BY `feature`,`date` DESC;
 * Sort by user:
 * SELECT `date`,`feature`,count(*) AS `numdenials` FROM `events` WHERE `type`='DENIED'  GROUP BY `date`,`feature` ORDER BY `numdenials` DESC;
 * Sort by feature:
 * SELECT `date`,`feature`,count(*) FROM `events` WHERE `type`='DENIED' GROUP BY `date`,`feature` ORDER BY `date` DESC,`feature`;
 */
switch ($_GET['sortby']) {

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

case "numdenials":
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

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
    die ($recordset->getMessage());
}

while ($row = $recordset->fetchRow()) {
    $table->AddRow($row, "style='background: {$features_color[$row[1]]};'");
}

$recordset->free();

$db->disconnect();

// Right align the 3 column
$table->updateColAttributes(2, "align='right'");

// Print View
print_header();

print <<<HTML
<h1>License Denials</h1>
<form>
<p>Sort by
<select onChange='this.form.submit();' name="sortby">
    <option value="date">Date</option>
    <option value="feature">Feature</option>
    <option value="numdenials">Number of denials</option>
</select>
<input type="submit" value="Submit"/></p>
</form>

HTML;

if (isset($debug) && $debug === 1)
    print_sql($sql);

$table->display();
print_footer();

?>

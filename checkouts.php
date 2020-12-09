<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";

db_connect($db);

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

// Create new html_table object
$table_style = array('class'=>"alt-row-bgcolor", 'style'=>"border: 1px solid gray; padding: 1px; border-spacing: 2px;");
$table = new html_table($table_style);

// Table header
$header_style = array('style'=>"background: yellow;");
$col_headers = array("Date", "User", "Feature", "Total number of checkouts");
$table->add_row($col_headers, $header_style, "th");

// Add data rows to table
for ($i = 0, $data_row = $result->fetch_row(); $data_row; $i++, $data_row = $result->fetch_row()) {
    $table->add_row($data_row);
    $table_row = $table->get_rows_count() - 1;
    $table->update_cell($table_row, 1, array('style'=>"text-align: right;"));
    $table->update_cell($table_row, 2, array('style'=>"text-align: right;"));
    $table->update_cell($table_row, 3, array('style'=>"text-align: center;"));
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
print $table->get_html();
print_footer();
?>

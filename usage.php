<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

$table = new html_table(['class' => "table alt-rows-bgcolor"]);
$heading = ["Feature", "Server Name", "Server Label"];
$table->add_row($heading, [], "th");

$sql = <<<SQL
SELECT `licenses`.`id`, COALESCE(`features`.`label`, `features`.`name`) AS `feature`, `servers`.`name`, `servers`.`label`
FROM `licenses`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
WHERE `features`.`show_in_lists`=1
GROUP BY `licenses`.`id`
ORDER BY `feature`, `servers`.`label`, `servers`.`name`
SQL;

db_connect($db);
$result = $db->query($sql);
if (!$result) {
    die ($db->error);
}

// Build a list of licenses to display in view.
// $row[0] = license ID
// $row[1] = feature label (feature name when label is null)
// $row[2] = server name
// $row[3] = server label
while ($row = $result->fetch_row()) {
    $license_id = $row[0];
    $feature = $row[1];
    $server_name = $row[2];
    $server_label = $row[3];

    $link = "<a href='monitor_detail.php?license={$license_id}'>{$feature}</a>";
    $html_row = [$link, $server_name, $server_label];
    $table->add_row($html_row);
}

$result->free();
$db->close();

// Print View
print_header();

print <<< HTML
<h1>License Usage Monitoring</h1>

<hr/>
<p>The following links will show usage graphs for different licenses.  Data is being collected every {$collection_interval} minutes.
<p>A feature can be associated with more than one server, and each pairing is a separately tracked license.
{$table->get_html()}
HTML;

print_footer();
?>

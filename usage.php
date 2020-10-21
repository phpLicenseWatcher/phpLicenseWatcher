<?php
require_once("common.php");
require_once("DB.php");

$db = db_connect();

$sql = <<<SQL
SELECT `name`, `label`
FROM `features`
WHERE `show_in_lists`=1
ORDER BY `name`;
SQL;

$recordset = $db->query($sql);
if (DB::isError($recordset)) {
    die ($recordset->getMessage());
}

// Build a list of features to display in view.
// $row[0] = `name`, $row[1] = `label`
while ($row = $recordset->fetchRow()) {
   $label = $row[1];
   if (empty($label)) {
       // Actual `label` is NULL, so we'll use `name` instead.
       $label = $row[0];
   }
   $html_labels[] = "<li><a href='monitor_detail.php?feature={$row[0]}'>{$label}</a></li>";
}

$recordset->free();
$db->disconnect();

// Print View
print_header();
print <<< HTML
<h1>License monitoring</h1>

<hr/>
<p>Following links will show the license usage for different tools. Data is being collected every {$collection_interval} minutes.</p>
<p>Features (click on link to show past usage):</p>

<ul>

HTML;

// Print labels in unordered list to view.
foreach ($html_labels as $html_label) {
    print $html_label;
}

// Print remaining list to view.
print <<< HTML
<li><a href="monitor_detail.php?feature=">All listed above</a></li>
<li><a href="monitor_detail.php?feature=all">Every single feature (slow)</a></li>
</ul>

HTML;
print_footer();
?>

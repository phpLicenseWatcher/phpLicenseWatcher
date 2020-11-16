<?php

require_once("common.php");
require_once("tools.php");
require_once("HTML/Table.php");

// Retrieve server list.  All columns.  All IDs.
db_connect($db);
$servers = db_get_servers($db);
$db->close();

$table_style = "class='table' ";

// Create a new table object
$table = new HTML_Table($table_style);
//$table->setColAttributes(1,"align=\"center\"");

// Define a table header
$header_style = "";
$col_headers = array("ID", "License port@server", "Description", "Status", "Current Usage", "Available features/license", "lmgrd version","Last Update");
$table->addRow($col_headers, $header_style, "TH");

foreach ($servers as $server) {
    switch ($server['status']) {
    case SERVER_UP:
        $class = "up";
        $detail_link="<a href='details.php?listing=0&amp;server={$server['id']}'>Details</a>";
        $listing_expiration_link="<a href='details.php?listing=1&amp;server={$server['id']}'>Listing/Expiration dates</a>";
        break;
    case SERVER_VENDOR_DOWN:
        $class = "warning";
	    $detail_link="<a href='details.php?listing=0&amp;server={$server['id']}'>Details</a>";
	    $listing_expiration_link="<a href='details.php?listing=1&amp;server={$server['id']}'>Listing/Expiration dates</a>";
        break;
    case SERVER_DOWN:
    default:
        $class = "down";
        $detail_link="No Details";
        $listing_expiration_link="";
        break;
    }

    $table->AddRow(array(
        $server['id'],
        $server['name'],
        $server['label'],
        $server['status'],
        $detail_link,
        $listing_expiration_link,
        $server['lmgrd_version'],
        date_format(date_create($server['last_updated']), "M j Y h:i:s A")
    ));

	// Set the background color of status cell
	$table->updateCellAttributes(($table->getRowCount() - 1) , 3, "class='{$class}'");
	//$table->updateCellAttributes( 1 , 0, "");
}

// Output view.
print_header();
print <<< HTML
<h1>License Server Status</h1>
<hr />
<h2>Flexlm Servers</h2>
<p>To get current usage for an individual server please click on the "Details" link next to the server. If you would like to get current usage for multiple servers on the same page use checkbox on the right of the server then click on the "Get current usage for multiple servers on one page".</p>
HTML;

$table->display();
print_footer();
?>

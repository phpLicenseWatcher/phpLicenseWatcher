<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";

// Retrieve server list.  All columns.  All IDs.
db_connect($db);
$servers = db_get_servers($db, array(), array(), "label");
$db->close();

// Start a new html_table
$table = new html_table(array('class'=>"table"));

// Define the table header
$col_headers = array("Description", "License port@server", "Status", "Current Usage", "Available features/license", "lmgrd version","Last Update");
$table->add_row($col_headers, array(), "th");

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

    $table->add_row(array(
        $server['label'],
        $server['name'],
        $server['status'],
        $detail_link,
        $listing_expiration_link,
        $server['lmgrd_version'],
        date_format(date_create($server['last_updated']), "M j Y h:i:s A")
    ));

	// Set the background color of status cell via class attribute
	$table->update_cell(($table->get_rows_count() - 1), 2, array('class'=>"{$class}"));
}

// Output view.
print_header();
print <<< HTML
<h1>License Server Status</h1>
<hr />
<h2>Flexlm Servers</h2>
<p>To get current usage for an individual server please click on the "Details" link next to the server. If you would like to get current usage for multiple servers on the same page use checkbox on the right of the server then click on the "Get current usage for multiple servers on one page".</p>
{$table->get_html()}
HTML;

print_footer();
?>

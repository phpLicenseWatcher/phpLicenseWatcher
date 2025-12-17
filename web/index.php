<?php

require_once __DIR__ . "/../code/common.php";
require_once __DIR__ . "/../code/tools.php";
require_once __DIR__ . "/../code/html_table.php";

// Retrieve server list.  All columns.  All IDs.
db_connect($db);
$servers = db_get_servers($db, array(), array(), "label");
$db->close();

// Start a new html_table
$table = new html_table(array('class'=>"table alt-rows-bgcolor"));

// Define the table header
$col_headers = array("Description", "License port@server", "Status", "Current Usage", "Available features/license", "Server Version", "Last Update");
$table->add_row($col_headers, array(), "th");

// Whether or not to display notice about setting up cron jobs.
// Notice is displayed when at least one server is configured and all servers
// have not been polled.  e.g. such as the first run.
$display_cron_notice = count($servers) > 0 ? true : false;
foreach ($servers as $server) {
    // $class refers to bootstrap, not local CSS.
    switch ($server['status']) {
    case null:
        $server['status']="Not Polled";
        $class = array('class' => "info"); // blue
        $detail_link="No Details";
        $listing_expiration_link="";
        break;
    case SERVER_UP:
        $display_cron_notice = false;
        $class = array(); // no color
        $detail_link="<a href='details.php?listing=0&amp;server={$server['id']}' aria-label='usage details for {$server['label']})'>Usage Details</a>";
        $listing_expiration_link="<a href='details.php?listing=1&amp;server={$server['id']}' aria-label='listing and expiration dates for {$server['label']})'>Listing/Expiration Dates</a>";
        break;
    case SERVER_VENDOR_DOWN:
        $display_cron_notice = false;
        $class = array('class' => "warning"); // yellow
	    $detail_link="<a href='details.php?listing=0&amp;server={$server['id']}' aria-label='usage details for {$server['label']})'>Usage Details</a>";
	    $listing_expiration_link="<a href='details.php?listing=1&amp;server={$server['id']}' aria-label='listing and expiration dates for {$server['label']})'>Listing/Expiration dates</a>";
        break;
    case SERVER_DOWN:
    default:
        $display_cron_notice = false;
        $class = array('class' => "danger"); // red
        $detail_link="No Details";
        $listing_expiration_link="";
        break;
    }

    $table->add_row(array(
        $server['label'],
        format_server_names( $server['name'] ),
        $server['status'],
        $detail_link,
        $listing_expiration_link,
        $server['version'],
        date_format(date_create($server['last_updated']), "m/d/Y h:ia")
    ));

    // Set server label as row header
    $table->update_cell(($table->get_rows_count() - 1), 0, null, null, "th");

	// Set the background color of status cell via class attribute
	$table->update_cell(($table->get_rows_count() - 1), 2, $class);
}

$cron_notice = !$display_cron_notice ? "" : get_not_polled_notice();

// Output view.
print_header();
print <<< HTML
<h1>License Management Server Status</h1>
<hr />
<p>To get current usage for an individual server please click on the "Usage Details" link next to the server.  The "Listings/Expiration Dates" link will provide time-to expiration for a server's features.</p>
{$cron_notice}
{$table->get_html()}
HTML;

print_footer();
?>

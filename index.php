<?php

require_once("common.php");
require_once("tools.php");
require_once("HTML/Table.php");
require_once("DB.php");

// Retrieve server list.
db_connect($db);
$servers = db_get_servers($db);
$db->disconnect();

$tableStyle = "class='table' ";

// Create a new table object
$table = new HTML_Table($tableStyle);
//$table->setColAttributes(1,"align=\"center\"");

// Define a table header
$headerStyle = "";
$colHeaders = array("ID", "License port@server", "Description", "Status", "Current Usage", "Available features/license", "lmgrd version");
$table->addRow($colHeaders, $headerStyle, "TH");

foreach ($servers as $server) {
	$data = run_command("{$lmutil_binary} lmstat -c {$server['name']}");
	$status_string = "";
	$detaillink="<a href='details.php?listing=0&amp;server={$server['id']}'>Details</a>";
	$listingexpirationlink="<a href='details.php?listing=1&amp;server={$server['id']}'>Listing/Expiration dates</a>";

	foreach(explode(PHP_EOL, $data) as $line) {
    	// Look for an expression like this ie. kalahari: license server UP (MASTER) v6.1
      	// preg_match() explicity returns int 1 on success.
      	switch (1) {
      	case preg_match ("/: license server UP \(MASTER\)/i", $line):
        	$status_string = "UP";
        	$class = "up";
        	$lmgrdversion = preg_replace("/.*license server UP \(MASTER\)/i ", "", $line);
            break;
        case preg_match ("/: license server UP/i", $line):
            $status_string = "UP";
            $class = "up";
            $lmgrdversion = preg_replace("/.*license server UP/i ", "", $line);
        }

        switch(1) {
        case preg_match("/Cannot connect to license server/i", $line, $out):
        case preg_match("/Cannot read data/i", $line, $out):
        case preg_match("/Error getting status/i", $line, $out):
            $status_string = "DOWN";
            $class = "down";
            $lmgrdversion = "";
            $detaillink="No details";
            $listingexpirationlink = "";
            break 2;
        // Checking if vendor daemon has died even if lmgrd is still running
        case preg_match("/vendor daemon is down/i", $line, $out):
            $status_string = "VENDOR DOWN";
            $class = "warning";
            $lmgrdversion = preg_replace(".*license server UP \(MASTER\) /i", "", $line);
            preg_replace(".*license server UP /i", "", $line);
            break 2;
        }
    }

    // If I don't get explicit reason that the server is UP set the status to DOWN
    if ($status_string === "") {
        $status_string = "DOWN";
        $class = "down";
        $lmgrdversion = "";
        $detaillink = "No details";
        $listingexpirationlink = "";
    }

    $table->AddRow(array(
        $server['id'],
        $server['name'],
        $server['label'],
        $status_string,
        $detaillink,
        $listingexpirationlink,
        $lmgrdversion
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

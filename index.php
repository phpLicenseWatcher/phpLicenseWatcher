<?php

require_once("common.php");
require_once("tools.php");
print_header("License Server Status");

?>


<link rel="top" href="index.php"/>
</head>
<body>
<h1>License Server Status</h1>

<hr/>
<h2>Flexlm Servers</h2>


<p>To get current usage for an individual server please click on the "Details" link next to the server. If you would like to get current usage for multiple servers on the same page use checkbox on the right of the server then click on the "Get current usage for multiple servers on one page".</p>

<?php

##########################################################################
# We are using PHP Pear library to create tables :-)
##########################################################################
require_once ("HTML/Table.php");

$tableStyle = "class='table' ";

# Create a new table object
$table = new HTML_Table($tableStyle);

//$table->setColAttributes(1,"align=\"center\"");

# Define a table header
$headerStyle = "";
$colHeaders = array("License port@server","Description", "Status", "Current Usage", "Available features/license","Master",  "lmgrd version");
$table->addRow($colHeaders, $headerStyle, "TH");

for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {
	$data = run_command($lmutil_loc . " lmstat -c " . $servers[$i]);
        
        

	$status_string = "";
	$detaillink="<a href=\"details.php?listing=0&amp;server=" . $i . "\">Details</a>" ;
	$listingexpirationlink="<a href=\"details.php?listing=1&amp;server=" . $i . "\">Listing/Expiration dates</a>" ;

	foreach(explode(PHP_EOL, $data) as $line){
		/* Look for an expression like this ie. kalahari: license server UP (MASTER) v6.1 */
		if ( preg_match ("/: license server UP \(MASTER\)/i", $line) ) {
			$status_string = "UP";
			$class = "up";
			$lmgrdversion = preg_replace("/.*license server UP \(MASTER\)/i ", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
		}else{

                    if ( preg_match ("/: license server UP/i", $line) ) {
                            $status_string = "UP";
                            $class = "up";
                            $lmgrdversion = preg_replace("/.*license server UP/i ", "", $line);
                            $lmmaster = substr($line,0,strpos($line,':',0));
                    }
                
                }

		if ( preg_match ("/Cannot connect to license server/i", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "" ;
			$lmmaster = "";
			$detaillink="No details";
			$listingexpirationlink="";
			break;
		}

		if ( preg_match ("/Cannot read data/i", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "" ;
			$lmmaster = "";
			$detaillink="No details";
			$listingexpirationlink="";
			break;
		}

		if ( preg_match ("/Error getting status/i", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "" ;
			$lmmaster = "";
			$detaillink="No details";
			$listingexpirationlink="";
			break;
		}

		/* Checking if vendor daemon has died evenif lmgrd is still running */
		if ( preg_match ("/vendor daemon is down/i", $line, $out) ) {
			$status_string = "VENDOR DOWN";
			$class = "warning";
			$lmgrdversion = preg_replace(".*license server UP \(MASTER\) /i", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
			preg_replace(".*license server UP /i", "", $line);
			break;
		}
		

	}

	/* If I don't get explicit reason that the server is UP set the status to DOWN */
	if ( $status_string == "" ) {
 		$status_string = "DOWN";
		$class = "down";
		$lmgrdversion = "" ;
		$lmmaster = "";
		$detaillink="No details";
		$listingexpirationlink="";
	}
	
	
	$table->AddRow(array($servers[$i],$description[$i],$status_string,
			 $detaillink,
			 $listingexpirationlink,
			 $lmmaster,
			 $lmgrdversion));

	# Set the background color of status cell
	$table->updateCellAttributes( ($table->getRowCount() - 1) , 2, "class='" . $class . "'");
	//$table->updateCellAttributes( 1 , 0, "");

	# Close the pipe
	//pclose($fp);
}

# Display the table
$table->display();
?>


<?php


# Check whether we are monitoring LUM servers. 
if ( isset ($i4blt_loc) && $i4blt_loc != "" ) {

echo ("<h2>LUM Servers</h2>");
$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\" ";

# Create a new table object
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1,"align=\"center\"");

# Define a table header
$headerStyle = "";
$colHeaders = array("Server Name","Target ID", "Target Type", "Server Type", "Details","Status");
$table->addRow($colHeaders, $headerStyle, "TH");


  $fp = popen("TIMEOUT_FACTOR=2 ; export TIMEOUT_FACTOR ; " . $i4blt_loc . " -ls ", "r"); // FIXME: shell dependent code!
   while ( !feof ($fp) ) {

        $line = fgets ($fp, 1024);

# If no license server is running, lum outputs:
# ADM-10037: There are no active license servers
		if ( preg_match ("/ADM-10037: There are no active license servers/i", $line) ) {
			$servername = "" ;
			$targetid = "" ;
			$targettype = "" ;
			$servertype = "" ;
			$details = "" ;
			$status_string = "DOWN";
			$class="down" ;
	$table->AddRow(array($servername,$targetid,$targettype,$servertype, $details, $status_string));
	# Set the background color of status cell
	$table->updateCellAttributes( ($table->getRowCount() - 1) , 5, "class='" . $class . "'");
	$table->updateCellAttributes( 1 , 0, "");


		}


# If a license server is running, lum outputs something like:
// ===========================================================================
// ===                             S e r v e r s                           ===
// ===========================================================================
// 
//   Server Name:     ip:linux.site
//   Target ID:       608cf8f8
//   Target Type:     Linux
//   Server Type:     Network   
//   Load:            Null or Not Available
//   Load Threshold:  90          
//   Trace:           none       
// 
// ===========================================================================
// 
//                           ==========================
//                           === End of Server List ===
//                           ==========================
		if ( preg_match ("/Server Name:/i", $line) ) {
                        $servername = preg_replace(".*Server Name:\ */i", "", $line);
                        $targetid = preg_replace(".*Target ID:\ */i", "", fgets ($fp, 1024));  // Next line: TargetID
                        $targettype = preg_replace(".*Target Type:\ */i", "", fgets ($fp, 1024));  // Next line: TargetType
                        $servertype = preg_replace(".*Server Type:\ */i", "", fgets ($fp, 1024));  // Next line: ServerType
 			$load = fgets ($fp, 1024);  // Next line: Load (not displayed)
			$loadthreshold = fgets ($fp, 1024);  // Next line: Load Threshod (not displayed)
			$trace = fgets ($fp, 1024);  // Next line: Trace (not displayed)
			$details = "<a href=\"lumdetails.php?server=" . $servername . "\">Details</a>" ;
			$status_string = "UP";
			$class="up";
	$table->AddRow(array($servername,$targetid,$targettype,$servertype, $details, $status_string));
	# Set the background color of status cell
	$table->updateCellAttributes( ($table->getRowCount() - 1) , 5, "class='" . $class . "'");
	$table->updateCellAttributes( 1 , 0, "");


		}


}
   pclose($fp);

# Display the table
$table->display();

} // end if (
?>


<?php echo footer(); ?>

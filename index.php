<?php

require_once("common.php");
print_header("License Server Status");

?>

<!-- hide script
<script language="javascript" type="text/javascript">
function beforepost(which) {
	var pass=true;
	var servers = "";

	for (i=0;i<which.length;i++) {
		//servers = servers + i;
		var tempobj=which.elements[i];
		if (tempobj.checked) {
			if ( servers == "" )
				servers = servers + tempobj.name.substring(16,20);
			else
				servers = servers + "," + tempobj.name.substring(16,20);
		}
	} // for (i=0, ) .... loop

	if ( servers == "" ) {
		alert("No servers selected");
		return false;
	}else{
		which.server.value=servers;
		which.submit();
	}
}
</script>
-->

<link rel="top" href="index.php"/>
</head>
<body>
<h1>License Server Status</h1>
<p class="a_centre"><a href="index.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>
<h2>Flexlm Servers</h2>
<form action="details.php" onsubmit="beforepost(this); return false;">
<div>
<input type="hidden" name="listing" value="0"/>
<input type="hidden" name="server" value=""/>
</div>

<p>To get current usage for an individual server please click on the "Details" link next to the server. If you would like to get current usage for multiple servers on the same page use checkbox on the right of the server then click on the "Get current usage for multiple servers on one page".</p>

<?php

##########################################################################
# We are using PHP Pear library to create tables :-)
##########################################################################
require_once ("HTML/Table.php");

$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\" ";

# Create a new table object
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1,"align=\"center\"");

# Define a table header
$headerStyle = "";
$colHeaders = array("License port@server","Description", "Status", "Current Usage", "Available features/license","Master", "LM Hostname", "lmgrd version");
$table->addRow($colHeaders, $headerStyle, "TH");

for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {
	$fp = popen($lmutil_loc . " lmstat -c " . $servers[$i], "r");

	$status_string = "";
	$detaillink="<a href=\"details.php?listing=0&amp;server=" . $i . "\">Details</a>" ;
	$listingexpirationlink="<a href=\"details.php?listing=1&amp;server=" . $i . "\">Listing/Expiration dates</a>" ;

	while ( !feof ($fp) ) {
		$line = fgets ($fp, 1024);
		/* Look for an expression like this ie. kalahari: license server UP (MASTER) v6.1 */
		if ( eregi (": license server UP \(MASTER\) ", $line) ) {
			$status_string = "UP";
			$class = "up";
			$lmgrdversion = eregi_replace(".*license server UP \(MASTER\) ", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
		}

		if ( eregi ("Cannot connect to license server", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		if ( eregi ("Cannot read data", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		if ( eregi ("Error getting status", $line, $out) ) {
			$status_string = "DOWN";
			$class = "down";
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		/* Checking if vendor daemon has died evenif lmgrd is still running */
		if ( eregi ("vendor daemon is down", $line, $out) ) {
			$status_string = "VENDOR DOWN";
			$class = "warning";
			$lmgrdversion = eregi_replace(".*license server UP \(MASTER\) ", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
			eregi_replace(".*license server UP ", "", $line);
			break;
		}
		

	}

	/* If I don't get explicit reason that the server is UP set the status to DOWN */
	if ( $status_string == "" ) {
 		$status_string = "DOWN";
		$class = "down";
		$lmgrdversion = "unknown" ;
		$lmmaster = "unknown";
		$detaillink="Details not available";
		$listingexpirationlink="Listing/Expiration dates not available";
	}
	
	$checkbox = '<input type="checkbox" name="multiple_servers[]" value=' . $i . '>';

	$table->AddRow(array($checkbox,$servers[$i],$description[$i],$status_string,
			 $detaillink,
			 $listingexpirationlink,
			 $lmmaster,
			 $lmgrdversion));

	# Set the background color of status cell
	$table->updateCellAttributes( ($table->getRowCount() - 1) , 3, "class='" . $class . "'");
	$table->updateCellAttributes( 1 , 0, "");

	# Close the pipe
	pclose($fp);
}

# Display the table
$table->display();
?>

<p>
<input type="submit" value="Get current usage for multiple servers on one page"/>
<input type="reset"/>
</p>
</form>

<?php


# Check whether we are monitoring LUM servers. 
if ( isset ($i4blt_loc) ) {

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
		if ( eregi ("ADM-10037: There are no active license servers", $line) ) {
			$servername = "unknown" ;
			$targetid = "unknown" ;
			$targettype = "unknown" ;
			$servertype = "unknown" ;
			$details = "unknown" ;
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
		if ( eregi ("Server Name:", $line) ) {
			$servername = eregi_replace(".*Server Name:\ *", "", $line) ;
			$targetid = eregi_replace(".*Target ID:\ *", "", fgets ($fp, 1024));  // Next line: TargetID
			$targettype = eregi_replace(".*Target Type:\ *", "", fgets ($fp, 1024));  // Next line: TargetType
			$servertype = eregi_replace(".*Server Type:\ *", "", fgets ($fp, 1024));  // Next line: ServerType
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

<?PHP
if ( $showversion ) {
  include_once('./version.php');
}
?>

</body></html>

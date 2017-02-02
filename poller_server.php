<?php

require_once("common.php");
require_once("tools.php");
print_header("License Server Status");

?>



<h2>Flexlm Servers</h2>



<p>To get current usage for an individual server please click on the "Details" link next to the server. If you would like to get current usage for multiple servers on the same page use checkbox on the right of the server then click on the "Get current usage for multiple servers on one page".</p>

<?php






for ( $i = 0 ; $i < sizeof($servers) && $i < 2 ; $i++ ) {
	//$fp = popen($lmutil_loc . " lmstat -c " . $servers[$i], "r");
        
        $data = run_command($lmutil_loc . " lmstat -c " . $servers[$i]);
        
       // echo "$i".$data;
        
	$status_string = "";
	
        
        
	//while ( !feof ($fp) ) {
		//$line = fgets ($fp, 1024);
            //preg_split("/((\r?\n)|(\r\n?)(\n))/", $subject)
        foreach(explode(PHP_EOL, $data) as $line){
          
		/* Look for an expression like this ie. kalahari: license server UP (MASTER) v6.1 */
		if ( preg_match ("/: license server UP (MASTER)/i", $line) ) {
			$status_string = "UP";
			
			$lmgrdversion = "XS".preg_replace("/.*license server UP (MASTER)/i ", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
		}

		if ( preg_match ("/: license server UP/i", $line) ) {
			$status_string = "UP";
			
			$lmgrdversion = preg_replace("/.*license server UP/i ", "", $line);
                        
                        
			$lmmaster = substr($line,0,strpos($line,':',0));
		}

		if ( preg_match ("/Cannot connect to license server/i", $line, $out) ) {
			$status_string = "DOWN";
			
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		if ( preg_match ("/Cannot read data/i", $line, $out) ) {
			$status_string = "DOWN";
			
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		if ( preg_match ("/Error getting status/i", $line, $out) ) {
			$status_string = "DOWN";
			
			$lmgrdversion = "unknown" ;
			$lmmaster = "unknown";
			$detaillink="Details not available";
			$listingexpirationlink="Listing/Expiration dates not available";
			break;
		}

		/* Checking if vendor daemon has died evenif lmgrd is still running */
		if ( preg_match ("/vendor daemon is down/i", $line, $out) ) {
			$status_string = "VENDOR DOWN";
			
			$lmgrdversion = eregi_replace(".*license server UP \(MASTER\) ", "", $line);
			$lmmaster = substr($line,0,strpos($line,':',0));
			eregi_replace(".*license server UP ", "", $line);
			break;
		}
		

	}

	/* If I don't get explicit reason that the server is UP set the status to DOWN */
	if ( $status_string == "" ) {
 		$status_string = "DOWN";
		
		$lmgrdversion = "unknown" ;
		$lmmaster = "unknown";
		$detaillink="Details not available";
		$listingexpirationlink="Listing/Expiration dates not available";
	}
	
	
	$x = array($servers[$i],$description[$i],$status_string,
			 $lmmaster,
			 $lmgrdversion);

	echo print_r($x,true);

	# Close the pipe
	
}


?>

<?php echo footer(); ?>

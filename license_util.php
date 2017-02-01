<?php

include_once('./tools.php');
require_once("./common.php");

##################################################
# Load PEAR DB abstraction library
##################################################
require_once("DB.php");

################################################################
#  If DB settings set up - Connect to the database
################################################################
if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {

	$db = DB::connect($dsn, true);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

}

################################################################
# Get current date and time
################################################################
$date = date('Y-m-d');
$time = date('H:i') . ":00";

for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {

   $fp = popen($lmutil_loc . " lmstat -a -c " . $servers[$i], "r");

   while ( !feof ($fp) ) {

	$line = fgets ($fp, 1024);

	# Look for features in the output. You will see stuff like
	# Users of Allegro_Viewer: (Total of 5 licenses available
	if ( eregi("^(Users of) (.*)",$line, $out ) )  {


          if ( eregi("(Total of) (.*) (license[s]? issued;  Total of) (.*) (license[s]? in use)", $line, $items ) ) {
	  			$license_array[] = array ( 
				"feature" => substr($out[2],0,strpos($out[2],":")), 
					"licenses_used" => $items[4] ) ;
                                unset($out);
				unset($items);
	  }
	}
   }


    pclose($fp);

#       $conn->debug=true;

   if ( isset($license_array) && is_array ($license_array) ) {

    for ( $j = 0 ; $j < sizeof($license_array) ; $j++ ) {

	############################################################################
	# Only insert into the database if DB settings are set
	############################################################################
	if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {

		$sql = "INSERT INTO license_usage (flmusage_server,flmusage_product,flmusage_date,flmusage_time,flmusage_users) VALUES ('$servers[$i]','" .
		$license_array[$j]["feature"] . "', '$date', '$time'," . $license_array[$j]["licenses_used"] . ")";
	
		if ( isset($debug) && $debug == 1 )
			print_sql ($sql);
		
		$recordset = $db->query($sql);
	
		if (DB::isError($recordset)) {
			die ($recordset->getMessage());
		}

	}

	##########################################################################
	# Insert into the RRD file if feature is being monitored
	##########################################################################	
	if ( isset($rrdtool_bin) && is_executable($rrdtool_bin) &&  in_array($license_array[$j]["feature"],  $license_feature) ) {
	
		$feature = strtolower($license_array[$j]["feature"]);
		$filename = $rrd_dir . "/" . $feature . ".rrd";
		insert_into_rrd($rrdtool_bin, $filename, $license_array[$j]["licenses_used"]);

	
	}

    }

    unset($license_array);

    }

}

##########################################################################
# Disconnect connection if using DB
##########################################################################
if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {

	$db->disconnect();

}

?>

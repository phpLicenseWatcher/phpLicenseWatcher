<?php

require_once("./common.php");

include_once('./tools.php');

require_once("DB.php");

# This may take a while so let's unlimit it
set_time_limit(0);

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
# Loop through the list of features we are trying to monitor
################################################################
for ( $i = 0 ; $i < sizeof($license_feature) ; $i++ ) {

	$feature = strtolower($license_feature[$i]);

	# 
	$filename = $rrd_dir . "/" . $feature . ".rrd";
	
	if ( ! file_exists($filename) ) {
	
		##################################################################
		# Convert the collection interval into seconds
		##################################################################
		$step = $collection_interval * 60;
		# Startime is today - a year
		$startdate = time() - 365 * 1440 * 60;
		exec($rrdtool_bin . " create " . $filename . " --start " . $startdate . " --step " .  $step . " DS:" . $feature . ":GAUGE:900:0:10000" .
		"RRA:AVERAGE:0.5:1:800 RRA:AVERAGE:0.5:6:800 RRA:AVERAGE:0.5:24:800 RRA:AVERAGE:0.5:288:800 " .
		"RRA:MAX:0.5:1:800 RRA:MAX:0.5:6:800 RRA:MAX:0.5:24:800 RRA:MAX:0.5:288:800");
	
	}
	
	chmod($filename, 0644);
	
	if ( ! file_exists($filename) ) {
		echo "ERROR: Couldn't create " . $filename . ". Check permissions :-(.\n";
	} else {
		echo "INFO: File created " . $filename . "\n";		
	}
	
	#####################################################################################
	# If we have DB setup
	#####################################################################################
	if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {
	
		#####################################################################################
		# Get utilization
		#####################################################################################
		$sql = "SELECT flmusage_date, flmusage_time, flmusage_users FROM license_usage WHERE flmusage_product='" .
		$feature . "'ORDER BY flmusage_date, flmusage_time";
	
		$recordset = $db->query($sql);
		
		if (DB::isError($recordset)) {
			die ($recordset->getMessage());
		}
		
		while ($row = $recordset->fetchRow()) {
			insert_into_rrd($rrdtool_bin, $filename, $row[2], strtotime($row[0] . " " . $row[1]));		
		}
		
		$recordset->free();
	}

}
?>
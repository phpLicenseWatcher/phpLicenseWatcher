<?php

require_once("common.php");
print_header("Licenses in Detail");

##############################################################
# We are using PHP Pear stuff ie. pear.php.net
##############################################################
require_once ("HTML/Table.php");

if ( isset($_GET['refresh']) && $_GET['refresh'] > 0 && ! $disable_autorefresh){
	echo('<meta http-equiv="refresh" content="' . intval($_GET['refresh']) . '"/>');
}
?>
</head>
<body>

<h1>Flexlm Licenses in Detail</h1>
<p class="a_centre"><a href="index.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>

<?php

#################################################################
# List available features and their expiration dates
#################################################################
if ( $_GET['listing'] == 1 ) {
	echo('<p>This is a list of licenses (features) available on this particular license server. If there are multiple entries under "Expiration dates" it means there are different entries for the same license. If expiration is in red it means expiration is within ' . $lead_time . ' days.</p>');

	$today = mktime(0,0,0,date("m"),date("d"),date("Y"));

	$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\" ";

	# Create a new table object
	$table = new HTML_Table($tableStyle);

	# First row should be the name of the license server and it's description
	$headerStyle = "colspan=\"4\"";
	$colHeaders = array("Server: " . $servers[$_GET['server']] . " ( " . $description[$_GET['server']] . " )");

	$table->addRow($colHeaders, $headerStyle, "TH");

	include_once("./tools.php");

	build_license_expiration_array($lmutil_loc, $servers[$_GET['server']], $expiration_array);

	# Define a table header
	$headerStyle = "style=\"background: yellow;\"";
	$colHeaders = array("Feature", "Vendor Daemon", "Total licenses", "Number licenses, Days to expiration, Date of expiration");
	$table->addRow($colHeaders, $headerStyle, "TH");

	#######################################################
	# Get names of different colors. These will be used to group visually
	# licenses from the same license server
	#######################################################
	$color = explode(",", $colors);

	#######################################################
	# We should have gotten an array with features
	# their expiration dates etc.
	#######################################################

	foreach ($expiration_array as $key => $feature_array) {
		$total_licenses = 0;
		$feature_string = "";
-
		$feature_table = new HTML_Table("width=100%");

		for ( $p = 0 ; $p < sizeof($feature_array) ; $p++ ) {

			# Keep track of total number of licenses for a particular feature
			# this is since you can have licenses with different expiration
			$total_licenses += $feature_array[$p]["num_licenses"];
			$feature_table->addRow(array($feature_array[$p]["num_licenses"] . " license(s) expire(s) in ". $feature_array[$p]["days_to_expiration"] . " day(s) Date of expiration: " . $feature_array[$p]["expiration_date"] ), "colspan=\"3\"");
			
			#######################################################################
			# Check whether license is close to expiration date			
			#######################################################################
			if ( $feature_array[$p]["days_to_expiration"] < 4000 ) {

				if ( $feature_array[$p]["days_to_expiration"] <= $lead_time && $feature_array[$p]["days_to_expiration"] >= 0 ){
					$feature_table->updateRowAttributes( ($feature_table->getRowCount() - 1) , "class=\"expires_soon\"");
				}

				if ( $feature_array[$p]["days_to_expiration"] < 0 ){
					$feature_table->updateRowAttributes( ($feature_table->getRowCount() - 1) , "class=\"already_expired\"");
				}

			}

		}

		$table->addRow(array(
			$key,
			$feature_array[0]["vendor_daemon"],
			$total_licenses,
			$feature_table->toHTML(),
		));

		unset($feature_table);
	}

	########################################################
	# Center columns 2. Columns start with 0 index
	########################################################
	$table->updateColAttributes(1,"align=\"center\"");

	$table->display();

} else {
	########################################################
	# Licenses currently being used
	########################################################
	echo ("<p>Following is the list of licenses currently being used. Licenses that are currently not in use are not shown.</p>");

	# Display the autorefresh drop box if disable_autorefresh is not set
	if ( ! isset($disable_autorefresh) || $disable_autorefresh == 1 ) {
		print("<form action=\"".$_SERVER['PHP_SELF']."\">");
		echo("<table border=\"0\" width=\"100%\"><tr><td>Refresh this page every <select name=\"refresh\" onchange='this.form.submit();'><option value=\"norefresh\">Don't refresh</option>");
	
		# How often to refresh in minutes 120,60,30,15,10,5,2,1
		$refresh = array (360,240,120,60,30,15,10,5,2,1);

		# Loop through the refresh array
		foreach ($refresh as $key) {
			# Convert into seconds since that is what is supplied to the META refresh
			$seconds = $key * 60;

			if ( isset($_GET['refresh']) && $_GET['refresh'] == $seconds ){
				echo("<option value=\"" . $seconds . "\" selected>" . $key . " minutes</option>\n");
			}else{
				echo("<option value=\"" . $seconds . "\">" . $key . " minutes</option>\n");
			}

		}

		echo('</select>');
		echo('</td><td class="a_centre">Page last refreshed at: ' . date("Y-m-d H:i:s") . '</td></tr></table>');
		print("</form>");
	}


	echo("<form action=\"".$_SERVER['PHP_SELF']."\"><div><input type=\"hidden\" name=\"listing\" value=\"" . intval($_GET['listing']) . "\"/><input type=\"hidden\" name=\"server\" value=\"" . $_GET['server'] . "\"/>");
	if ( isset($_GET['filter_feature']) ) {
		foreach ( $_GET['filter_feature'] as $key ){
			echo("<input type=\"hidden\" name=\"filter_feature[]\" value=\"" . $key . "\"/>\n");
		}
	}
	print("</div>");

	# If person is filtering for certain features
	if ( isset($_GET['filter_feature']) ) {
		echo("<p>You are currently filtering these features: <span style=\"color: blue;\">");
		foreach ( $_GET['filter_feature'] as $key ) {
			echo("(" . str_replace(":","", $key) . ") " );
		}
	
		echo("</span></p>");
	}
	
	######################################################################
	# This is a hack so we can support both specifying multiple
	# servers as comma separated arguments or multiple_servers array 
	######################################################################
	if ( isset($_GET['multiple_servers']) ){
		$current_server = $_GET['multiple_servers'];
	} else {
		$current_server = split(",", $_GET['server']);
	}

	# We'll need timestamp class to get a human readable time difference
	include_once('./tools.php');

	#######################################################
	# Get names of different colors
	#######################################################
	$color = explode(",", $colors);

	###############################################################3
	# Loop through the available servers
	###############################################################3
	for ( $n = 0 ; $n < sizeof($current_server) ; $n++ ) {
		# Make sure that current server is an integer value
		if ( $servers[$current_server[$n]] ) {
			# Execute lmutil lmstat -A -c 27000@license or similar
			$fp = popen($lmutil_loc . " lmstat -A -c " . $servers[$current_server[$n]], "r");

			# Feature counter
			$i = -1;

			################################################################################
			# Loop through the output. Look for lines starting with Users. Then look for any
			# consecutive entries showing who is using it
			################################################################################
			while ( !feof ($fp) ) {

				$line = fgets ($fp, 1024);

				# Look for features in the output. You will see stuff like
				# Users of Allegro_Viewer: (Total of 5 licenses available
				if ( preg_match('/(Users of) (.*)(\(Total of) (\d+) (.*) (Total of) (\d+) /i', $line, $out) && !eregi("No such feature exists", $line) ) {
					$i++;
					$license_array[$i]["feature"] = $out[2];
					$license_array[$i]["num_licenses"] = $out[4];
					$license_array[$i]["licenses_used"] = $out[7];
				}

                                # NJI: Sometimes a vendor gives a "uncounted" file, with infinite licenses.
				if ( preg_match('/(Users of) (.*)(\(Uncounted, node-locked)/i', $line, $out) ) {
					$i++;
					$license_array[$i]["feature"] = $out[2];
					$license_array[$i]["num_licenses"] = "uncounted";
					$license_array[$i]["licenses_used"] = "uncounted";
				}

				# Count the number of licenses. Each used license has ", start" string in it
				if ( eregi(", start", $line ) ){
					$users[$current_server[$n]][$i][] = $line;
				}
			}

			################################################################################
			# Check whether anyone is using licenses from this particular license server
			################################################################################
			if ( $i > - 1 ) {

				# Create a new table
				$tableStyle = "width=\"100%\"";

				$table = new HTML_Table($tableStyle);

				# Show a banner with the name of the serve@port plus description
				$headerStyle = "colspan=\"5\"";
				$colHeaders = array("Server: " . $servers[$current_server[$n]] . " ( " . $description[$current_server[$n]] . " )");
				$table->addRow($colHeaders, $headerStyle, "TH");

				$headerStyle = "style=\"background: lightblue;\"";
				$colHeaders = array("Filter/<br/>Remove","Feature", "# cur. avail", "Details","Time checked out");
				$table->addRow($colHeaders, $headerStyle, "TH");

				# Get current UNIX time stamp
				$now = time ();

				###########################################################################
				# Loop through the used features
				###########################################################################
				for ( $j = 0 ; $j <= $i ; $j++ ) {
					if ( ! isset($_GET['filter_feature']) || in_array($license_array[$j]["feature"], $_GET['filter_feature']) ) {
						# How many licenses are currently used
						$licenses_available = $license_array[$j]["num_licenses"] - $license_array[$j]["licenses_used"];
						$license_info = "Total of " . $license_array[$j]["num_licenses"] . " licenses, " .
						$license_array[$j]["licenses_used"] . " currently in use, <b>" . $licenses_available . " available</b>";

						$checkbox = '<input type="checkbox" name="filter_feature[]" value="' . $license_array[$j]["feature"] . '"/>';
						$table->addRow(array($checkbox,$license_array[$j]["feature"], "$licenses_available", $license_info), "style=\"background: $color[$j];\"");

						for ( $k = 0; $k < sizeof($users[$current_server[$n]][$j]) ; $k++ ) {
							################################################################
							# I want to know how long a license has been checked out. This
							# helps in case some people forgot to close an application and
							# have licenses checked out for too long.
							# LMstat view will contain a line that says
							# jdoe machine1 /dev/pts/4 (v4.0) (licenserver/27000 444), start Thu 12/5 9:57
							# the date after start is when license was checked out
							################################################################
							$line = split(", start ", $users[$current_server[$n]][$j][$k]);
							preg_match("/(.+?) (.+?) (\d+):(\d+)/i", $line[1], $line2);
				
							# Convert the date and time ie 12/5 9:57 to UNIX time stamp
							$time_checkedout = strtotime ($line2[2] . " " . $line2[3] . ":" . $line2[4]);
			
							$time_difference = "";

							################################################################
							# This is what I am not very clear on but let's say a license has been
							# checked out on 12/31 and today is 1/2. It is unclear to me whether
							# strotime will handle the conversion correctly ie. 12/31 will actually
							# be 12/31 of previous year and not the current. Thus I will make a
							# little check here. Will just append the previous year if now is less
							# then time_checked_out
							################################################################

							if ( $now < $time_checkedout ){
								$time_checkedout = strtotime ($line2[2] . "/" . (date("Y") - 1) . " " . $line2[3]);
							}else {
								# Get the time difference
								$t = new timespan( $now, $time_checkedout );
		
								# Format the date string
								if ( $t->years > 0) $time_difference .= $t->years . " years(s), ";
								if ( $t->months > 0) $time_difference .= $t->months . " month(s), ";
								if ( $t->weeks > 0) $time_difference .= $t->weeks . " week(s), ";
								if ( $t->days > 0) $time_difference .= " " . $t->days . " day(s), ";
								if ( $t->hours > 0) $time_difference .= " " . $t->hours . " hour(s), ";
								$time_difference .= $t->minutes . " minute(s)";	
							}
		
							# Output the user line
							if ( ! isset($disable_license_removal) ) {
								$removal_dialog = "<h6><a href='' onclick='if (confirm(\"Are you sure you want to remove this license ?\")) this.href=\"lmremove.php\?server=" . $current_server[$n] . "&amp;feature=" . $license_array[$j]["feature"] . "&amp;arg=" . urlencode(trim($users[$current_server[$n]][$j][$k])) ."\";'>Remove</a>";
							
								$table->addRow(array($removal_dialog, "&nbsp;", "" ,$users[$current_server[$n]][$j][$k], $time_difference), "style=\"background: $color[$j];\"");	
							} else{
								$table->addRow(array("&nbsp;", "&nbsp;", "" ,$users[$current_server[$n]][$j][$k], $time_difference), "style=\"background: $color[$j];\"");
							}

						}
					}

					$table->updateColAttributes(2,"align=\"center\"");
					$table->updateColAttributes(4,"align=\"center\"");
					$table->updateColAttributes(0,"align=\"center\"");
				}

			     # Display the table
			     if ( $table->getRowCount() > 2 ){
				$table->display();
			     }

			} else {
				echo("<p style=\"color: red;\">No licenses are currently being used on " . $servers[$current_server[$n]]. " ( " . $description[$current_server[$n]] . " )</p>");
			}
			pclose($fp);
		}
	} // end for loop

	// End of current usage
	echo('<div><input type="submit" value="Filter"/><input type="reset"/></div>');
	echo('</form>');
} // end if ( $listing == 1 )
?>

<?PHP
if ( $showversion ) {
  include_once('./version.php');
}
?>

</body></html>

<?php

require_once("common.php");
require_once('./tools.php');

print_header("Licenses in Detail");

##############################################################
# We are using PHP Pear stuff ie. pear.php.net
##############################################################
require_once ("HTML/Table.php");

if ( isset($_GET['refresh']) && $_GET['refresh'] > 0 && ! $disable_autorefresh){
	echo('<meta http-equiv="refresh" content="' . intval($_GET['refresh']) . '"/>');
}

$server = preg_replace("/[^0-9,]+/", "", htmlspecialchars($_GET['server'])) ;


?>


<h1>Flexlm Licenses in Detail</h1>

<hr/>

<?php

#################################################################
# List available features and their expiration dates
#################################################################
if ( $_GET['listing'] == 1 ) {
	echo('<p>This is a list of licenses (features) available on this particular license server. If there are multiple entries under "Expiration dates" it means there are different entries for the same license. If expiration is in red it means expiration is within ' . $lead_time . ' days.</p>');

	$today = mktime(0,0,0,date("m"),date("d"),date("Y"));


	# Create a new table object
	$table = new HTML_Table("class='table' style='width:100%;' ");

	# First row should be the name of the license server and it's description
	$colHeaders = array("Server: " . $servers[$server] . " ( " . $description[$server] . " )");

	$table->addRow($colHeaders, "colspan='4'", "th");

	include_once("./tools.php");

	build_license_expiration_array($lmutil_loc, $servers[$server], $expiration_array);

	# Define a table header
	$colHeaders = array("Feature", "Vendor Daemon", "Total licenses", "Number licenses, Days to expiration, Date of expiration");
	$table->addRow($colHeaders, "", "th");

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
		$current_server = explode(",", $server);
	}

	# We'll need timestamp class to get a human readable time difference


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
				if ( preg_match('/(Users of) (.*)(\(Total of) (\d+) (.*) (Total of) (\d+) /i', $line, $out) && !preg_match("/No such feature exists/i", $line) ) {
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
				if ( preg_match("/, start/i", $line ) ){
					$users[$current_server[$n]][$i][] = $line;
				}
			}

			################################################################################
			# Check whether anyone is using licenses from this particular license server
			################################################################################
			if ( $i > - 1 ) {

				# Create a new table
				$tableStyle = "class='table' width=\"100%\"";

				$table = new HTML_Table($tableStyle);

				# Show a banner with the name of the serve@port plus description
				$colHeaders = array("Server: " . $servers[$current_server[$n]] . " ( " . $description[$current_server[$n]] . " )");
				$table->addRow($colHeaders, "colspan='4'", "TH");


				$colHeaders = array("Feature", "# cur. avail", "Details","Time checked out");
				$table->addRow($colHeaders, "" , "TH");

				# Get current UNIX time stamp
				$now = time ();

				###########################################################################
				# Loop through the used features
				###########################################################################
				for ( $j = 0 ; $j <= $i ; $j++ ) {
					if ( ! isset($_GET['filter_feature']) || in_array($license_array[$j]["feature"], $_GET['filter_feature']) ) {
                                                $feature = $license_array[$j]["feature"] ;

                                                $graph_url = "monitor_detail.php?feature=$feature";

						# How many licenses are currently used
						$licenses_available = $license_array[$j]["num_licenses"] - $license_array[$j]["licenses_used"];
						$license_info = "Total of " . $license_array[$j]["num_licenses"] . " licenses, " .
						$license_array[$j]["licenses_used"] . " currently in use, <b>" . $licenses_available . " available</b>";

                                                $license_info .= "<br/><a href='$graph_url'>Historical Usage</a>";


						$table->addRow(array($license_array[$j]["feature"], "$licenses_available", $license_info), "style=\"background: $color[$j];\"");

						for ( $k = 0; $k < sizeof($users[$current_server[$n]][$j]) ; $k++ ) {
							################################################################
							# I want to know how long a license has been checked out. This
							# helps in case some people forgot to close an application and
							# have licenses checked out for too long.
							# LMstat view will contain a line that says
							# jdoe machine1 /dev/pts/4 (v4.0) (licenserver/27000 444), start Thu 12/5 9:57
							# the date after start is when license was checked out
							################################################################
							$line = explode(", start ", $users[$current_server[$n]][$j][$k]);
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

                                                        $user_line = $users[$current_server[$n]][$j][$k];
                                                        $user_line_parts = explode( ' ', trim($user_line) );

                                                        $user_line_formated = "<span>User: ".$user_line_parts[0]."</span> " ;
                                                        $user_line_formated .= "<span>Computer: ".$user_line_parts[2]."</span> " ;

							$table->addRow(array( "&nbsp;", "" ,$user_line_formated, $time_difference), "style=\"background: $color[$j];\"");


						}
					}


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

} // end if ( $listing == 1 )
?>



<?php print_footer(); ?>

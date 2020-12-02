<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";

if (isset($_GET['refresh']) && $_GET['refresh'] > 0 && ! $disable_autorefresh) {
    echo('<meta http-equiv="refresh" content="' . intval($_GET['refresh']) . '"/>');
}

// This is a hack so we can support both specifying multiple servers as comma
// separated arguments or multiple_servers array.
if ( isset($_GET['multiple_servers']) ) {
    $ids = $_GET['multiple_servers'];
} else {
    $ids = preg_replace("/[^0-9,]+/", "", htmlspecialchars($_GET['server']));
    $ids = explode(",", $ids);
}

db_connect($db);
$servers = db_get_servers($db, array('name', 'label'), $ids);
$db->close();

$html_body = ""; // To be filled by the process function called below.
switch($_GET['listing']) {
case 0:
    list_licenses_in_use($servers, $html_body);
    break;
case 1:
    list_features_and_expirations($servers, $html_body);
    break;
default:
    return null;
}

// Show view.
print_header();
print "<h1>Flexlm Licenses in Detail</h1><hr>";
print $html_body;
print_footer();
exit;

// List available features and their expiration dates
function list_features_and_expirations($servers, &$html_body) {
    global $lead_time; // from config.php

    // Only one server is used, here.  Assume it's index [0].
    $server = $servers[0];
    unset ($servers);

    $html_body .= <<<HTML
<p>This is a list of licenses (features) available on this particular license server.
If there are multiple entries under "Expiration dates" it means there are different entries for the same license.
If expiration is in red it means expiration is within {$lead_time} days.</p>

HTML;

    $today = mktime(0,0,0,date("m"),date("d"),date("Y"));

    // Create a new table object
    $table = new html_table(array('class'=>"table"));

    // First row should be the name of the license server and it's description
    $colHeaders = array("Server: {$server['name']} ( {$server['label']} )");
    $table->add_row($colHeaders, array(), "th");
    $table->update_cell(0, 0, array('colspan'=>"4"));

    build_license_expiration_array($server['name'], $expiration_array);

    // Define a table header
    $colHeaders = array("Feature", "Vendor Daemon", "Total licenses", "Number licenses, Days to expiration, Date of expiration");
    $table->add_row($colHeaders, array(), "th");

    // We should have gotten an array with features
    // their expiration dates etc.
    foreach ($expiration_array as $key => $feature_array) {
        $total_licenses = 0;
        for ($p = 0; $p < count($feature_array); $p++) {
            // Keep track of total number of licenses for a particular feature
            // this is since you can have licenses with different expiration
            $total_licenses += intval($feature_array[$p]["num_licenses"]);

            // Set row class if license is close to the expiration date.
            $row_attributes = array();
            if ( $feature_array[$p]["days_to_expiration"] < 4000 ) {
                if ( $feature_array[$p]["days_to_expiration"] <= $lead_time && $feature_array[$p]["days_to_expiration"] >= 0 ) {
                    $row_attributes['class'] = "expires_soon";
                }

                if ( $feature_array[$p]["days_to_expiration"] < 0 ) {
                    $row_attributes['class'] = "already_expired";
                }
            }

            $license_msg  = "{$feature_array[$p]['num_licenses']} license(s) expire(s) in ";
            $license_msg .= "{$feature_array[$p]['days_to_expiration']} day(s) Date of expiration: ";
            $license_msg .= "{$feature_array[$p]['expiration_date']}";
        }

        $table->add_row(array($key, $feature_array[0]["vendor_daemon"], $total_licenses, $license_msg), $row_attributes);
        $table->update_cell(($table->get_rows_count()-1), 1, array('style'=>"text-align:center;"));
    }

    // Center columns 2. Columns start with 0 index
    $html_body .= $table->get_html();
} // END function list_features_and_expirations()

/**
 * Determine licenses in use and build a data view for display.
 *
 * @param $servers list of servers to poll
 * @param &$html_body data view to build
 */
function list_licenses_in_use($servers, &$html_body) {
    global $lmutil_binary; // from config.php

    $html_body .= <<<HTML
<p>Following is the list of licenses currently being used.
Licenses that are currently not in use are not shown.</p>

HTML;

    // If person is filtering for certain features
    if ( isset($_GET['filter_feature']) ) {
        $html_body .= "<p>You are currently filtering these features: <span style='color: blue;'>";
        foreach ( $_GET['filter_feature'] as $key ) {
            $html_body .= "(" . str_replace(":","", $key) . ") ";
        }

        $html_body .= ("</span></p>");
    }

    // We'll need timestamp class to get a human readable time difference

    // Get names of different colors
    $colors = array("lavender", "transparent");
    $num_colors = count($colors);

    $licenses = array();

    // Loop through the available servers
    foreach ($servers as $server) {
        // Execute lmutil lmstat -A -c port@server_fqdn
        $fp = popen("{$lmutil_binary} lmstat -A -c {$server['name']}", "r");

        // License counter
        $i = -1;

        // Loop through the output. Look for lines starting with Users. Then look for any
        // consecutive entries showing who is using it
        while (!feof($fp)) {
            $line = fgets ($fp, 1024);

            // Look for features in a $line.  Example $line:
            // Users of Allegro_Viewer:  (Total of 5 licenses issued;  Total of 2 licenses in use)
            switch (1) {
            case preg_match('/^Users of ([\w\- ]+):  \(Total of (\d+) licenses issued;  Total of (\d+)/i', $line, $matches):
                $i++;
                $licenses[$i]['feature'] = $matches[1];
                $licenses[$i]['num_licenses'] = $matches[2];
                $licenses[$i]['licenses_used'] = $matches[3];
                break;
            case preg_match('/^Users of ([\w\- ]+):  \(Uncounted, node-locked/i', $line, $matches):
                $i++;
                $licenses[$i]['feature'] = $matches[1];
                $licenses[$i]['num_licenses'] = "uncounted";
                $licenses[$i]['licenses_used'] = "uncounted";
                break;
            }

            // Count the number of licenses. Each used license has ", start" string in it
            if ( preg_match("/, start/i", $line ) ){
                $users[$server['name']][$i][] = $line;
            }
        }

        // Check whether anyone is using licenses from this particular license server
        if ( $i > -1 ) {
            // Create a new table
            $table = new html_table(array('class'=>"table"));

            // Show a banner with the name of the serve@port plus description
            $colHeaders = array("Server: {$server['name']} ({$server['label']})");
            $table->add_row($colHeaders, array(), "th");
            $table->update_cell(0, 0, array('colspan'=>"4"));

            $colHeaders = array("Feature", "# Cur. Avail", "Details", "Time Checked Out");
            $table->add_row($colHeaders, array(), "th");

            // Get current UNIX time stamp
            $now = time();

            // Loop through the used features
            for ( $j = 0 ; $j <= $i ; $j++ ) {
                if ( ! isset($_GET['filter_feature']) || in_array($licenses[$j]['feature'], $_GET['filter_feature']) ) {
                    $feature = $licenses[$j]['feature'] ;
                    $graph_url = "monitor_detail.php?feature={$feature}";

                    // How many licenses are currently used
                    $licenses_available = $licenses[$j]['num_licenses'] - $licenses[$j]['licenses_used'];
                    $license_info = "Total of {$licenses[$j]['num_licenses']} licenses, ";
                    $license_info .= "{$licenses[$j]['licenses_used']} currently in use, ";
                    $license_info .= "<span style='font-weight: bold'>{$licenses_available} available</span>";
                    $license_info .= "<br/><a href='{$graph_url}'>Historical Usage</a>";
                    $color = $colors[$j%$num_colors];
                    $table->add_row(array($licenses[$j]['feature'], $licenses_available, $license_info, ""), array('style'=>"background:{$color};"));

                    for ( $k = 0; $k < sizeof($users[$server['name']][$j]); $k++ ) {
                        /* ---------------------------------------------------------------------------
                         * I want to know how long a license has been checked out. This
                         * helps in case some people forgot to close an application and
                         * have licenses checked out for too long.
                         * LMstat view will contain a line that says
                         * jdoe machine1 /dev/pts/4 (v4.0) (licenserver/27000 444), start Thu 12/5 9:57
                         * the date after start is when license was checked out
                         * ---------------------------------------------------------------------------- */
                        $line = explode(", start ", $users[$server['name']][$j][$k]);
                        preg_match("/(.+?) (.+?) (\d+):(\d+)/i", $line[1], $line2);

                        // Convert the date and time ie 12/5 9:57 to UNIX time stamp
                        $time_checkedout = strtotime ($line2[2] . " " . $line2[3] . ":" . $line2[4]);
                        $time_difference = "";

                        /* ---------------------------------------------------------------------------
                         * This is what I am not very clear on but let's say a license has been
                         * checked out on 12/31 and today is 1/2. It is unclear to me whether
                         * strotime will handle the conversion correctly ie. 12/31 will actually
                         * be 12/31 of previous year and not the current. Thus I will make a
                         * little check here. Will just append the previous year if now is less
                         * then time_checked_out
                         * ---------------------------------------------------------------------------- */
                        if ( $now < $time_checkedout ) {
                            $time_checkedout = strtotime ($line2[2] . "/" . (date("Y") - 1) . " " . $line2[3]);
                        } else {
                            // Get the time difference
                            $t = new timespan( $now, $time_checkedout );

                            // Format the date string
                            if ( $t->years > 0) $time_difference .= $t->years . " years(s), ";
                            if ( $t->months > 0) $time_difference .= $t->months . " month(s), ";
                            if ( $t->weeks > 0) $time_difference .= $t->weeks . " week(s), ";
                            if ( $t->days > 0) $time_difference .= " " . $t->days . " day(s), ";
                            if ( $t->hours > 0) $time_difference .= " " . $t->hours . " hour(s), ";
                            $time_difference .= $t->minutes . " minute(s)";
                        }

                        // Output the user line
                        $user_line = $users[$server['name']][$j][$k];
                        $user_line_parts = explode( ' ', trim($user_line) );
                        $user_line_formated = "<span>User: ".$user_line_parts[0]."</span> ";
                        $user_line_formated .= "<span>Computer: ".$user_line_parts[2]."</span> ";
                        $table->add_row(array("", "", $user_line_formated, $time_difference), array('style'=>"background:{$color};"));
                    }
                }
            }

            // Display the table
            if ($table->get_rows_count() > 2 ) {
                $html_body .= $table->get_html();
            }

        } else {
            // color #dc143c is "crimson", which is better than "red" for contrast ratio against white background.
            $html_body .= "<p style='color: crimson;'>No licenses are currently being used on {$server['name']} ({$server['label']})</p>";
        }
        pclose($fp);
    } // END foreach ($servers as $server)
} // END function list_licenses_for_use()
?>

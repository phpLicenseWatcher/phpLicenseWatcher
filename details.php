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
$servers = db_get_servers($db, array('name', 'label', 'id'), $ids);
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
            $row_attributes = array();
            if ($feature_array[$p]['expiration_date'] === "permanent") {
                $license_msg = "{$feature_array[$p]['num_licenses']} license(s) are permanent.";
            } else {
                // Set row class if license is close to the expiration date.
                $dte = $feature_array[$p]["days_to_expiration"];
                if ($dte <= $lead_time && $dte >= 0) {
                    $row_attributes['class'] = "warning"; //bootstrap class
                } else if ($dte < 0) {
                    $row_attributes['class'] = "danger"; //bootstrap class
                }

                $license_msg = <<<MSG
                {$feature_array[$p]['num_licenses']} license(s) expire(s) in {$feature_array[$p]['days_to_expiration']} day(s).
                Date of expiration: {$feature_array[$p]['expiration_date']}
                MSG;
            }
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

    // Loop through the available servers
    foreach ($servers as $server) {
        // Execute lmutil lmstat -A -c port@server_fqdn
        // switch -A reports only licenses in use.
        $fp = popen("{$lmutil_binary} lmstat -A -c {$server['name']}", "r");
        $line = fgets($fp);

        $no_licenses_in_use_warning = true;
        $used_licenses = array();
        $i = 0;

        while (!feof($fp)) {
            // Look for features in a $line.  Example $line:
            // Users of Allegro_Viewer:  (Total of 5 licenses issued;  Total of 2 licenses in use)
            switch (1) {
            case preg_match('/^Users of ([\w\- ]+):  \(Total of (\d+) licenses? issued;  Total of (\d+)/i', $line, $matches):
                $used_licenses[$i]['feature_name'] = $matches[1];
                $used_licenses[$i]['num_licenses'] = $matches[2];
                $used_licenses[$i]['num_licenses_used'] = $matches[3];
                $num_licenses_used = (int) $matches[3];

                $no_licenses_in_use_warning = false;
                $j = 0;
                while ($j < $num_licenses_used && !feof($fp)) {
                    $line = fgets($fp);
                    if (preg_match("/^ *([^ ]+) ([^ ]+) .+, start \w{3} ([0-9]{1,2}\/[0-9]{1,2}) ([0-9]{1,2}:[0-9]{2})/i", $line, $matches) === 1) {
                        $used_licenses[$i]['checkouts'][$j]['user'] = $matches[1];
                        $used_licenses[$i]['checkouts'][$j]['host'] = $matches[2];
                        $used_licenses[$i]['checkouts'][$j]['date'] = $matches[3];
                        $used_licenses[$i]['checkouts'][$j]['time'] = $matches[4];
                        $j++;
                    }
                }
                $i++;
                break;
            case preg_match('/^Users of ([\w\- ]+):  \(Uncounted/i', $line, $matches):
                $used_licenses[$i]['feature_name'] = $matches[1];
                $used_licenses[$i]['num_licenses'] = "uncounted";
                $used_licenses[$i]['num_licenses_used'] = "uncounted";
                $i++;
                break;
            }

            $line = fgets($fp);
        }

        usort($used_licenses, function($a, $b) {
            return strcasecmp($a['feature_name'], $b['feature_name']);
        });

        $unused_licenses = array_udiff(get_features_and_licenses($server['id']), $used_licenses, function($a, $b) {
            return strcasecmp($a['feature_name'], $b['feature_name']);
        });

        if ($no_licenses_in_use_warning) {
            $html_body .= get_alert_html("No licenses are currently being used on {$server['name']} ({$server['label']})", "info");
        }

        // Create a new table
        $table = new html_table(array('class'=>"table"));

        // Show a banner with the name of the port@server plus description
        $colHeaders = array("Server: {$server['name']} ({$server['label']})");
        $table->add_row($colHeaders, array(), "th");
        $table->update_cell(0, 0, array('colspan'=>"4"));

        $colHeaders = array("Feature", "# Cur. Avail", "Details", "Time Checked Out");
        $table->add_row($colHeaders, array(), "th");

        // Get current UNIX time stamp
        $now = time();

        foreach(array_merge($used_licenses, $unused_licenses) as $i => $license) {
            if (!isset($_GET['filter_feature']) || in_array($license['feature_name'], $_GET['filter_feature'])) {
                $feature = $license['feature_name'];
                $graph_url = "monitor_detail.php?feature={$feature}";

                // How many licenses are currently used
                $licenses_available = $license['num_licenses'] - $license['num_licenses_used'];

                $license_info = <<<HTML
                Total of {$license['num_licenses']} licenses, {$license['num_licenses_used']} currently in use,
                <span style='font-weight: bold'>{$licenses_available} available</span>
                <br/><a href='{$graph_url}'>Historical Usage</a>
                HTML;

                // Checked out licenses have a blue tinted background to
                // differentiate from licenses with no checkouts.
                if (isset($license['checkouts']) && count($license['checkouts']) > 0) {
                    $class = $i % 2 === 0 ? array('class'=>"alt-even-blue-bgcolor") : array('class'=>"alt-odd-blue-bgcolor");
                } else {
                    $class = $i % 2 === 0 ? array('class'=>"alt-bgcolor") : array();
                }

                $table->add_row(array($license['feature_name'], $licenses_available, $license_info, ""), $class);

                // Not all used features have checkout-time data.  Skip over those that don't.
                if (isset($license['checkouts']) && is_countable($license['checkouts'])) {
                    foreach ($license['checkouts'] as $checkout) {
                        /* ---------------------------------------------------------------------------
                         * I want to know how long a license has been checked out. This
                         * helps in case some people forgot to close an application and
                         * have licenses checked out for too long.
                         * LMstat view will contain a line that says
                         * jdoe machine1 /dev/pts/4 (v4.0) (licenserver/27000 444), start Thu 12/5 9:57
                         * the date after start is when license was checked out
                         * ---------------------------------------------------------------------------- */

                         // Convert the date and time ie 12/5 9:57 to UNIX time stamp
                         $time_checkedout = strtotime("{$checkout['date']} {$checkout['time']}");
                         $time_difference = "";

                         /* ---------------------------------------------------------------------------
                          * This is what I am not very clear on but let's say a license has been
                          * checked out on 12/31 and today is 1/2. It is unclear to me whether
                          * strotime will handle the conversion correctly ie. 12/31 will actually
                          * be 12/31 of previous year and not the current. Thus I will make a
                          * little check here. Will just append the previous year if now is less
                          * then time_checked_out
                          * ---------------------------------------------------------------------------- */
                         if ($now < $time_checkedout) {
                             $year = date("Y") - 1;
                             $time_checkedout = strtotime("{$checkout['date']}/{$year} {$checkout['time']}");
                         }

                         // Get the time difference
                         $t = new timespan($now, $time_checkedout);

                         // Format the date string
                         if ($t->years > 0)  $time_difference .= "{$t->years} years(s), ";
                         if ($t->months > 0) $time_difference .= "{$t->months} month(s), ";
                         if ($t->weeks > 0)  $time_difference .= "{$t->weeks} week(s), ";
                         if ($t->days > 0)   $time_difference .= " {$t->days} day(s), ";
                         if ($t->hours > 0)  $time_difference .= " {$t->hours} hour(s), ";
                         $time_difference .= "{$t->minutes} minute(s)";

                         // Output the user line
                         $html = "<span>User: {$checkout['user']}</span> ";
                         $html .= "<span>Computer: {$checkout['host']}</span> ";
                         $table->add_row(array("", "", $html, $time_difference), $class);
                     } // END foreach ($license['checkouts'] as $checkout)
                 } // END if (isset($license['checkouts']) && is_countable($license['checkouts']))
             } // END if (!isset($_GET['filter_feature']) || in_array($license['feature_name'], $_GET['filter_feature']))
         } // END foreach(array_merge($used_licenses, $unused_licenses) as $i => $license)

        // Display the table
        $html_body .= $table->get_html();

    } // END foreach ($servers as $server)
} // END function list_licenses_for_use()

function get_features_and_licenses($server_id) {
    // The left outer join ensures that every feature in the result is its most
    // recent entry.  Otherwise, there would be very many rows per feature.
    // (one for every date of entry)
    $sql = <<<SQL
    SELECT f.`name`, a1.`num_licenses` FROM `available` a1
    JOIN `licenses` l ON a1.`license_id` = l.`id`
    JOIN `features` f ON l.`feature_id` = f.`id`
    LEFT OUTER JOIN `available` a2 ON a1.`license_id` = a2.`license_id` AND a1.`date` < a2.`date`
    WHERE a2.`license_id` IS NULL AND l.`server_id` = ?
    ORDER BY f.`name` ASC
    SQL;

    $params = array('i', $server_id);
    $results = array();

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();
    $query->bind_result($feature_name, $num_licenses);

    for ($i = 0; $query->fetch(); $i++) {
        $results[$i]['feature_name'] = $feature_name;
        $results[$i]['num_licenses'] = $num_licenses;
        $results[$i]['num_licenses_used'] = 0; // needed by function caller
    }

    if (!empty($db->error_list)) {
        $err_msg = htmlspecialchars($db->error);
        return "DB Error: {$err_msg}.";
    }

    $query->close();
    $db->close();

    return $results;
}
?>

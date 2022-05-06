<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";
require_once __DIR__ . "/lmtools.php";

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
$servers = db_get_servers($db, array('name', 'label', 'id', 'count_reserve_tokens_as_used', 'license_manager'), $ids);
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
print "<h1>Licenses in Detail</h1><hr>";
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
    If expiration is in yellow, it means expiration is within {$lead_time} days.  Red indicates expired licenses.</p>
HTML;

    $today = mktime(0,0,0,date("m"),date("d"),date("Y"));

    // Create a new table object
    $table = new html_table(array('class'=>"table"));

    // First row should be the name of the license server and it's description
    $colHeaders = array("Server: {$server['name']} ( {$server['label']} )");
    $table->add_row($colHeaders, array(), "th");
    $table->update_cell(0, 0, array('colspan'=>"4"));

    build_license_expiration_array($server, $expiration_array);

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
            $total_licenses += (int) ($feature_array[$p]["num_licenses"]);
            $row_attributes = array();
            if ($feature_array[$p]['expiration_date'] === "permanent") {
                $license_msg = "{$feature_array[$p]['num_licenses']} license(s) are permanent.";
            } else {
                $license_msg = <<<MSG
                {$feature_array[$p]['num_licenses']} license(s) expire(s) in {$feature_array[$p]['days_to_expiration']} day(s).
                Date of expiration: {$feature_array[$p]['expiration_date']}.
                MSG;

                // Set row class if license is close to the expiration date.
                $dte = $feature_array[$p]["days_to_expiration"];
                if (is_numeric($dte) && $dte <= $lead_time && $dte >= 0) {
                    $row_attributes['class'] = "warning"; //bootstrap class
                } else if (is_numeric($dte) && $dte < 0) {
                    $row_attributes['class'] = "danger"; //bootstrap class
                    $days_expired = abs($feature_array[$p]['days_to_expiration']);
                    $license_msg = <<<MSG
                    {$feature_array[$p]['num_licenses']} license(s) have expired {$days_expired} day(s) ago.
                    Date of expiration: {$feature_array[$p]['expiration_date']}.
                    MSG;
                }
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
    $html_body .= "<p>Following is the list of licenses currently being used.";

    // If person is filtering for certain features
    if ( isset($_GET['filter_feature']) ) {
        $html_body .= "<p>You are currently filtering these features: <span style='color: blue;'>";
        foreach ( $_GET['filter_feature'] as $key ) {
            $html_body .= "(" . str_replace(":","", $key) . ") ";
        }

        $html_body .= "</span>";
    }

    // Loop through the available servers
    foreach ($servers as $server) {
        $used_licenses = lmtools::get_license_usage_array($server['license_manager'], $server['name']);
        if (empty($used_licenses)) {
            // when empty, no licenses are in use (boolean true)
            $html_body .= get_alert_html("No licenses are currently being used on {$server['name']} ({$server['label']})", "info");
        } else {
            usort($used_licenses, function($a, $b) {
                return strcasecmp($a['feature_name'], $b['feature_name']);
            });
        }

        $unused_licenses = array_udiff(get_features_and_licenses($server['id']), $used_licenses, function($a, $b) {
            return strcasecmp($a['feature_name'], $b['feature_name']);
        });

        // Create a new table
        $table = new html_table(array('class'=>"table"));

        // Show a banner with the name of the port@server plus description
        $colHeaders = array("Server: {$server['name']} ({$server['label']})");
        $table->add_row($colHeaders, array(), "th");
        $table->update_cell(0, 0, array('colspan'=>"4"));

        $colHeaders = array("Feature", "# Cur. Avail", "Details", "Time Checked Out");
        $table->add_row($colHeaders, array(), "th");

        foreach(array_merge($used_licenses, $unused_licenses) as $i => $license) {
            if (!array_key_exists('filter_feature', $_GET) || in_array($license['feature_name'], $_GET['filter_feature'])) {
                $feature = $license['feature_name'];
                $graph_url = "monitor_detail.php?feature={$feature}";

                // $license['num_licenses_used'] is the value reported by the license manager, which can include reserved tokens.
                // $license['num_checkouts'] is the accumulated count of licenses reported to be checked out by all individual users.
                // 'num_checkouts' will not include reserved tokens.
                $licenses_used = $server['count_reserve_tokens_as_used'] === "1"
                    ? $license['num_licenses_used']
                    : $license['num_checkouts'];

                $licenses_available = $license['num_licenses'] - $licenses_used;

                $license_info = <<<HTML
                Total of {$license['num_licenses']} licenses, {$licenses_used} currently in use, {$license['num_queued']} queued,<br>
                {$license['num_reservations']} reserved,
                <span class="bold-text">{$licenses_available} available</span><br>
                <a href='{$graph_url}'>Historical Usage</a>
                HTML;

                // Used licenses have a blue tinted background to differentiate from unused licenses.
                if ($licenses_used > 0) {
                    $class = $i % 2 === 0 ? array('class'=>"alt-even-blue-bgcolor") : array('class'=>"alt-odd-blue-bgcolor");
                } else {
                    $class = $i % 2 === 0 ? array('class'=>"alt-bgcolor") : array();
                }

                $table->add_row(array($license['feature_name'], $licenses_available, $license_info, ""), $class);

                // Not all used features have checkout data.  Skip over those that don't.
                if (array_key_exists('checkouts', $license) && is_countable($license['checkouts'])) {
                    foreach ($license['checkouts'] as $checkout) {
                        /* --------------------------------------------------------
                         * I want to know how long a license has been checked out.
                         * This helps in case some people forgot to close an
                         * application and have licenses checked out for too long.
                         * ----------------------------------------------------- */
                        $time_difference = get_readable_timespan($checkout['timespan']);

                        // Output the user line
                        $html = "User: {$checkout['user']}<br>";
                        $html .= "Computer: {$checkout['host']}<br>";
                        $html .= "Licenses: {$checkout['num_licenses']}";
                        $table->add_row(array("", "", $html, $time_difference), $class);
                    } // END foreach ($license['checkouts'] as $checkout)
                } // END if (array_key_exists('checkouts', $license) && is_countable($license['checkouts']))

                if (array_key_exists('queued', $license) && is_countable($license['queued'])) {
                    foreach ($license['queued'] as $queued) {
                        $html = "User: {$queued['user']}<br>";
                        $html .= "Computer: {$queued['host']}<br>";
                        $html .= "Licenses queued: {$queued['num_queued']}";
                        $table->add_row(array("", "", $html, ""), $class);
                    } // END foreach ($license['queued'] as $queued)
                } // END if (array_key_exists('queued', $license) && is_countable($license['queued']))

                if (array_key_exists('reservations', $license) && is_countable($license['reservations'])) {
                    foreach ($license['reservations'] as $reservation) {
                        $html = "{$reservation['reserved_for']}<br>";
                        $html .= "Tokens reserved: {$reservation['num_reserved']}";
                        $table->add_row(array("", "", $html, ""), $class);
                    } // END foreach ($license['reservations'] as $reservation)
                } // END if (array_key_exists('reservations', $license) && is_countable($license['reservations']))
             } // END if (!array_key_exists('filter_feature', $_GET) || in_array($license['feature_name'], $_GET['filter_feature']))
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
        $results[$i]['num_licenses_used'] = "0";  // needed by function caller
        $results[$i]['num_checkouts'] = "0";      // needed by function caller
        $results[$i]['num_queued'] = "0";         // needed by function caller
        $results[$i]['num_reservations'] = "0";   // needed by function caller
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

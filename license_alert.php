<?php
############################################################################
# Purpose: This script is used to e-mail alerts on licenses that are
#          due to expire some time in the future. This script should
#          be run out of cron preferably every day. Check config.php
#          to configure e-mail address reports should be sent to
#          as well as how much ahead should the user be warned about
#          expiration ie. 10 days before license expires.
############################################################################

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";

db_connect($db);
$servers = db_get_servers($db, array('name', 'label'));
$db->close();

// Date when the licenses will expire
$expire_date = mktime(0, 0, 0, date("m"), date("d") + $lead_time, date("Y"));
$today = mktime (0, 0, 0, date("m"), date("d"), date("Y"));

foreach ($servers as $i => $server) {
    build_license_expiration_array($server['name'], $expiration_array[$i]);
}

$table = new html_table(array('class'=>"table alt-rows-bgcolor"));

$colHeaders = array("Server", "Server label", "Feature expiring", "Expiration date",
                    "Days to expiration", "Number of license(s) expiring");

$table->add_row($colHeaders, array(), "th");

// Now after the expiration has been built loop through all the fileservers
for ($i = 0; $i < count($expiration_array); $i++) {
    if (isset($expiration_array[$i])) {
        foreach ($expiration_array[$i] as $key => $myarray) {
            for ($j = 0; $j < sizeof($myarray); $j++) {
                $bgcolor_class = "";
                if ((strcmp($myarray[$j]["days_to_expiration"], "permanent") != 0) && ($myarray[$j]["days_to_expiration"] <= $lead_time)) {
                    if ($myarray[$j]["days_to_expiration"] < 0) {
                        $myarray[$j]["days_to_expiration"] = "<span class='bold-text'>Already expired</span>";
                        $bgcolor_class = " bg-danger"; // change cell background to light red via bootstrap
                    }
                    $table->add_row(array(
                        $servers[$i]['name'],
                        $servers[$i]['label'],
                        $key,
                        $myarray[$j]["expiration_date"],
                        $myarray[$j]["days_to_expiration"],
                        $myarray[$j]["num_licenses"]
                    ));
                    $table->update_cell(($table->get_rows_count()-1), 1, array('class'=>"center-text"));
                    $table->update_cell(($table->get_rows_count()-1), 3, array('class'=>"center-text"));
                    $table->update_cell(($table->get_rows_count()-1), 4, array('class'=>"center-text{$bgcolor_class}"));
                    $table->update_cell(($table->get_rows_count()-1), 5, array('class'=>"center-text"));
                }
            }
        }
    }
}

// Dump the table HTML into a variable
$table_html = $table->get_html();

// Top of view.
$message = <<<HTML
These licenses will expire within {$lead_time} days.
Licenses will expire at 23:59 on the day of expiration.
<p>
HTML;

// If the table has more than one row (header row will be one) there are
// expiring licenses alerts to be emailed.
if ($table->get_rows_count() > 1) {
    if ($notify_address && $do_not_reply_address && !isset($_GET['nomail'])) {
        $message .= "Emailing to {$notify_address}<p>\n";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = "From: {$do_not_reply_email}";
        $headers[] = "Reply-To: {$do_not_reply_email}";
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        mail($notify_address, "ALERT: License expiration within {$lead_time} days", $message , implode("\r\n", $headers));
    }
}

// Print View
print_header();
print "<h1>License Alert</h1>\n";
print $message;
print $table_html;
print_footer();
?>

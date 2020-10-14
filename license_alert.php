<?php
############################################################################
# Purpose: This script is used to e-mail alerts on licenses that are
# 	   due to expire some time in the future. This script should
#	   be run out of cron preferably every day. Check config.php
#	   to configure e-mail address reports should be sent to
#	   as well as how much ahead should the user be warned about
#	   expiration ie. 10 days before license expires.
############################################################################

require_once "common.php";
require_once "tools.php";
require_once "HTML/Table.php";

// Date when the licenses will expire
$expire_date = mktime(0, 0, 0, date("m"), date("d") + $lead_time, date("Y"));
$today = mktime (0, 0, 0, date("m"), date("d"), date("Y"));

for ($i = 0; $i < sizeof($servers); $i++) {
    build_license_expiration_array($lmutil_loc, $servers[$i], $expiration_array[$i]);
}

$table = new HTML_Table("class='table' style='width:100%;' ");

$colHeaders = array("Server", "Server description", "Feature expiring", "Expiration date",
                    "Days to expiration", "Number of license(s) expiring");

$table->addRow($colHeaders, "", "TH");

// Get names of different colors. These will be used to group visually
// licenses from the same license server
$color = explode(",", $colors);

// Now after the expiration has been built loop through all the fileservers
for ($i = 0; $i < sizeof($expiration_array); $i++) {
    if (isset($expiration_array[$i])) {
        foreach ($expiration_array[$i] as $key => $myarray) {
            for ($j = 0; $j < sizeof($myarray); $j++) {
                if ((strcmp($myarray[$j]["days_to_expiration"], "permanent") != 0) && ($myarray[$j]["days_to_expiration"] <= $lead_time)) {
                    if ($myarray[$j]["days_to_expiration"] < 0) {
                        $myarray[$j]["days_to_expiration"] = "<span style='weight: bold;'>Already expired</span>";
                        $table->addRow(array(
                             $servers[$i],
                             $description[$i],
                             $key,
                             $myarray[$j]["expiration_date"],
                             $myarray[$j]["days_to_expiration"],
                             $myarray[$j]["num_licenses"]
                        ), "bgcolor='{$color[$i]}'");
                    }
                }
            }
        }
    }
}

// Center columns 2,4,5and 6. Columns start with 0 index
$table->updateColAttributes(1, "align=center");
$table->updateColAttributes(4, "align=center");
$table->updateColAttributes(5, "align=center");
$table->updateColAttributes(3, "align=center");

// Dump the table HTML into a variable
$table_html = $table->toHTML();

// Top of view.
$message = <<<HTML
These licenses will expire within {$lead_time} days.
Licenses will expire at 23:59 on the day of expiration.
<p>
HTML;

// If the table has more than one row (header row will be one) there are
// expiring licenses.  We may also add a message to the view.
if ($table->getRowCount() > 1) {
    if ($notify_address && !isset($_GET['nomail'])) {
        $message .= "Emailing to {$notify_address}<p>\n";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = 'From: arc-donotreply@rpi.edu' ;
        $headers[] = 'Reply-To: arc-donotreply@rpi.edu' ;
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

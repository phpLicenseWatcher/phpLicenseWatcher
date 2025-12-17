<?php
############################################################################
# Purpose: This script is used to e-mail alerts on licenses that are
#          due to expire some time in the future. This script should
#          be run out of cron preferably every day. Check config.php
#          to configure e-mail address reports should be sent to
#          as well as how much ahead should the user be warned about
#          expiration ie. 10 days before license expires.
############################################################################

require_once __DIR__ . "/../../code/common.php";
require_once __DIR__ . "/../../code/tools.php";
require_once __DIR__ . "/../../code/html_table.php";

if (file_exists(__DIR__ . "/../../code/vendor/autoload.php")) {
    require_once __DIR__ . "/../../code/vendor/autoload.php";
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

db_connect($db);
$servers = db_get_servers($db, array('name', 'label', 'license_manager'));
$db->close();

// Date when the licenses will expire
$expire_date = mktime(0, 0, 0, date("m"), date("d") + $lead_time, date("Y"));
$today = mktime (0, 0, 0, date("m"), date("d"), date("Y"));

foreach ($servers as $i => $server) {
    build_license_expiration_array($server, $expiration_array[$i]);
}

$table = new html_table(array('class'=>"table alt-rows-bgcolor"));

$colHeaders = array("Server", "Server label", "Feature expiring", "Expiration date",
                    "Days to expiration", "Number of license(s) expiring");

$table->add_row($colHeaders, array(), "th");

$warn_count = 0;

// Now after the expiration has been built loop through all the fileservers
$max_expiration_array = count($expiration_array);
for ($i = 0; $i < $max_expiration_array; $i++) {
    if (array_key_exists($i, $expiration_array) && !is_null($expiration_array[$i]) ) {
        foreach ($expiration_array[$i] as $key => $myarray) {
            $max_myarray = count($myarray);
            for ($j = 0; $j < $max_myarray; $j++) {
                $bgcolor_class = "";
                switch (true) {
                case $myarray[$j]["days_to_expiration"] === "permanent":
                case $myarray[$j]["days_to_expiration"] === "N/A":
                case $myarray[$j]["days_to_expiration"] > $lead_time:
                    continue 2;
                }

                if ($myarray[$j]["days_to_expiration"] < 0) {
                    $myarray[$j]["days_to_expiration"] = "<span class='bold-text'>Already expired</span>";
                    $bgcolor_class = " bg-danger"; // change cell background to light red via bootstrap
                }

                $table->add_row(array(
                    $servers[$i]['name'],
                    $servers[$i]['label'],
                    $key,
                    $myarray[$j]['expiration_date'],
                    $myarray[$j]['days_to_expiration'],
                    $myarray[$j]['num_licenses']
                ));

                $table->update_cell(($table->get_rows_count()-1), 1, array('class'=>"center-text"));
                $table->update_cell(($table->get_rows_count()-1), 3, array('class'=>"center-text"));
                $table->update_cell(($table->get_rows_count()-1), 4, array('class'=>"center-text{$bgcolor_class}"));
                $table->update_cell(($table->get_rows_count()-1), 5, array('class'=>"center-text"));
                $warn_count++;
            }
        }
    }
}

// Dump the table HTML into a variable
$table_html = $table->get_html();

// Message body for either browser view or email.
$message = <<<HTML
These {$warn_count} licenses will expire within {$lead_time} days.
Licenses will expire at 23:59 on the day of expiration.
<p>
{$table_html}
HTML;

// More reliable check to determine if we're running on CLI than php_sapi_name()
if (empty(preg_grep("/^HTTP_/", array_keys($_SERVER)))) {
    
    $opts = getopt('dt',['debug','test']);
    foreach( array_keys($opts) as $opt ){
        if( $opt==='test' || $opt ==='t' ){
            fprintf(STDOUT, "Forcing test email message." . PHP_EOL );
            $warn_count = 1;
            $message .= "TEST EMAIL MESSAGE";
        }
        
        if( $opt==='debug' || $opt ==='d' ){
            fprintf(STDOUT, "Enabling debug for this execution." . PHP_EOL );
            global $smtp_debug;
            $smtp_debug = true;
        }
        
    }
    
    if (!$send_email_notifications) {
        fprintf(STDOUT, "Sending email notifications is disabled, see \$send_email_notifications; defined in config.php" . PHP_EOL);
        exit(0);
    }
    
    if( $warn_count === 0 ){
        fprintf(STDOUT, "No expiring licenses to warn about." . PHP_EOL );
        exit(0);
    }
    
    // Script run from command line.  Send license alerts via email.
    global $send_email_notifications;  // defined in config.php
    if ($send_email_notifications) {
        send_email($message);
    }
} else {
    print_view($message);
}

exit(0);

// FUNCTIONS ------------------------------------------------------------------

function send_email($message) {
    // Check for PHPMailer library before proceeding.
    if (!class_exists("PHPMailer\PHPMailer\PHPMailer", true)) {
        fprintf(STDERR, "Cannot mail license alerts.  PHPMailer library not found.". PHP_EOL);
        exit(1);
    }

    // globals are defined in config.php
    global $smtp_host, $smtp_login, $smtp_password, $smtp_tls, $smtp_port, $notify_address, $reply_address, $lead_time, $smtp_debug;

    $mail = new PHPMailer();
    $mail->isSMTP();
    if( $smtp_debug ){
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    $mail->SMTPAuth  = true;
    $mail->Host      = $smtp_host;
    $mail->Port      = $smtp_port;
    $mail->Username  = $smtp_login;
    $mail->Password  = $smtp_password;

    switch ($smtp_tls) {
    case "smtps":
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        break;
    case "starttls":
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        break;
    case "none":
        fprintf(STDERR, "No encryption of sent email.". PHP_EOL);
        $mail->SMTPAuth  = false;
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        break;
    default:
        fprintf(STDERR, "Cannot mail license alerts.\n\$smtp_tls not properly set in config.php". PHP_EOL);
        exit(1);
    }

    $mail->setFrom($reply_address, 'phpLicenseWatcher');
    $mail->addReplyTo($reply_address);
    $mail->addAddress($notify_address);
    $mail->isHTML(true);

    $mail->Subject = "ALERT: License expiration within {$lead_time} days";
    $mail->Body    = $message;

    if (!$mail->send()) {
        fprintf(STDERR, "Cannot mail license alerts.\nMailer Error: %s". PHP_EOL, $mail->ErrorInfo);
        exit(1);
    }
    
    fprintf(STDOUT, "Email Sent". PHP_EOL );
}

function print_view($message) {
    print_header();
    print "<h1>License Alert</h1>\n";
    print $message;
    print_footer();
}
?>

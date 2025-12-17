<?php
// This is a sample config file that can be copied to webroot
// (e.g. /var/www/html) as "config.php" and adjusted as needed.

// $*_binary is the full path & file where a license manager binary is located.
// They must have execute permissions for the web server user.
// FlexLM (lmutil) and Mathematica (monitorlm) are currently supported.
$lmutil_binary="/usr/local/bin/lmutil";
$monitorlm_binary="/usr/local/bin/monitorlm";

// $cache_dir specifies what directory is used to store/process cache files.
// /tmp is not advised.  $cache_lifetime is how long a cache file is retained
// in seconds. e.g. 7200 = 2 hours.
$cache_dir="/var/cache/phplw/";
$cache_lifetime=7200;

// License expiration alerts are sent to $notify_address.
// $reply_address is filled in the from and reply fields.
// Consider NOT using a "do not reply" address so that errors can be received.
// $smtp_tls is either "smtps", "starttls" or "none" to disable encryption.
// $smtp_port is usually either "465" (smtps) or "587" (starttls).
// License alert emails are disabled when $send_email_notifications is set 0.
// Licenses expiring within $lead_time (in days) are included in the alerts.
$smtp_host="";
$smtp_login="";
$smtp_password="";
$smtp_tls="";
$smtp_port="";
$notify_address="";
$reply_address="";
$send_email_notifications=0;
$lead_time=30;
$smtp_debug=false;

// $disable_autorefresh, when set to 1, will halt automatic refresh of certain
// page views.  Make sure $collection_interval matches, in minutes, the cron
// schedule for license_util.php.
$disable_autorefresh=0;
$collection_interval=10;

// Database information.  Note that only the mysqli driver is suppoprted.
$db_hostname="localhost";
$db_username="phplw";
$db_password="phplw_password";
$db_database="licenses";

// IMPORTANT: Change this to 0 when used in production!
// Allows debug logging when set to 1.
$debug=0;

//Uncomment line below to change the timezone.  List of possible values can be found at https://www.php.net/manual/en/timezones.php
//date_default_timezone_set("PROPER_TZ_HERE");

?>

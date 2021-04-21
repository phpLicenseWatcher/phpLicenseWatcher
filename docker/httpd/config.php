<?php
// This config file is used by the dockler image.

// $lmutil_binary is the full path & file where lmutil is located.
// lmutil must have execute permissions for the web server user.
$lmutil_binary="/usr/local/bin/lmutil";

// $cache_dir specifies what directory is used to store/process cache files.
// '/tmp' is not advised.  $cache_lifetime is how long a cache file is retained
// in seconds. e.g. 7200 = 2 hours.
$cache_dir="/var/cache/phplw/";
$cache_lifetime=7200;

// License expiration alerts are sent to $notify_address.
// $do_not_reply_address is filled in the reply field.
// License alert emails are disabled when either is left blank.
// Licenses expiring within $lead_time (in days) are included in the alerts.
$notify_address="";
$do_not_reply_address="";
$lead_time=30;

// $disable_autorefresh, when set to 1, will halt automatic refresh of certain
// page views.  Make sure $collection_interval matches, in minutes, the cron
// schedule for license_util.php.
$disable_autorefresh=0;
$collection_interval=15;

// Database information.  Note that only the mysqli driver is suppoprted.
// Docker: Please set $db_password.  Do not change other values.
$db_hostname="mariadb";
$db_username="lmutilmon";
$db_password="";
$db_database="licenses";

// IMPORTANT: Change this to 0 when used in production!
// Allows debug logging when set to 1.
$debug=0;
?>

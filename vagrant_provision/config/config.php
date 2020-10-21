<?php
// Config file for Vagrant VM
$lmutil_loc="/opt/flexnetserver/lmutil";
$lmstat_loc="{$lmutil_loc} lmstat";
$notify_address="";
$lead_time=30;
$disable_autorefresh=0;
$disable_license_removal=1;
$collection_interval=10;

$db_type="mysqli";
$db_hostname="localhost";
$db_username="vagrant";
$db_password="vagrant";
$db_database="vagrant";
$dsn="{$db_type}://{$db_username}:{$db_password}@{$db_hostname}/{$db_database}";

$colors="#ffffdd,#ff9966,#ffffaa,#ccccff,#cccccc,#ffcc66,#99ff99,#eeeeee,#66ffff,#ccffff,#ffff66,#ffccff,#ff66ff,yellow,lightgreen,lightblue";
$smallgraph="100,100";
$largegraph="300,200";
$legendpoints="";

// IMPORTANT: Change this to 0 when used in production!
$debug = 1;

// License servers are now in the database.
// e.g. INSERT INTO `servers` (`name`, `label`, `is_active`) VALUES ('port@domain.tld', 'label/description', 1);
?>

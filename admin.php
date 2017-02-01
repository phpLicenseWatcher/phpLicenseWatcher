<?php

##################################################
# We need authentication on this page
##################################################
include_once('./auth.php');


require_once("common.php");
print_header("PHP Licensewatcher Admin Page");

?>
</head><body>
<h1>PHP Licensewatcher Admin Page</h1>
<p>This page contains links to pages that are usually unavailable to general population</p>
<ul>
	<li><a href="index.php">License server health/utilization (Start Page)</a></li>
	<li><a href="license_alert.php?nomail=1">License Alert</a> - this page will show you what licenses are expiring within the specified time frame. Default is 10 days.</li>

<?php

if ( is_array($license_feature) ){
	echo('<li><a href="utilization.php">Today\'s license utilization</a> - this page shows you graphs of the usage of a particular license.</li>');
}

if ( isset($license_feature) && sizeof($license_feature) > 0 ){
	echo('<li><a href="monitor.php">Daily/Weekly/Monthly license utilization trends</a> - this page shows you graphs of the usage of a particular license.</li>');
}

if ( isset($log_file) && sizeof($log_file) > 0 ){
	echo('<li><a href="denials.php">FlexLM denials</a> - this is the aggregate number of denials for particular feature.</li>');	
	echo('<li><a href="checkouts.php">FlexLM checkouts</a> - how many time a particular license has been checked out by a user.</li>');	
}

?>
<li><a href="config/index.php">PHP Llicensewatcher configuration: Web interface</a></li>
	
</ul>

<?php
include_once('./version.php');

?>

</body>
</html>

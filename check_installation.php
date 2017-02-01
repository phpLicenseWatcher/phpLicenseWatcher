<?php

require_once("common.php");
print_header("Check PHPlicensewatcher installation");

?>
</head><body>
<h1>Check PHPlicensewatcher installation</h1>
<hr/>

<p>This will check whether PHPlicensewatcher has been properly installed. This is not an exhaustive check but checks for common installation and configuration issues.</p>
<p style="color: red;">When the script is finished with the check it will print out a congratulation message. If there is no congratulatory message a problem appeared that this script didn't anticipate :-(.</p>

<?php

# Check PHP version and strip all the .
$phpversion = (int) (str_replace(".","",phpversion()));

if ( $phpversion >= 410 ) {
	echo("<p class=\"ok\">PHP version you are using <b>" . phpversion() ."</b> GOOD</p>");
}else{
	echo("<p class=\"error\">ERROR: PHP version you are using ie. <b>" . phpversion() ."</b> is not supported by PHPLicensewatcher. Supported versions of PHP are only 4.1.0 and up. Please upgrade and re-run the script.</p>");
}

###########################################################################
# Check for GD
###########################################################################

echo("<p>Checking whether GD support (required for graphs) is installed</p> ");
if ( function_exists('imagecreate')) {
	echo("<p class=\"ok\"><b>GOOD</b>. GD support is enabled.</p>");
 } else {
  echo("<p class=\"error\">ERROR: It doesn't seem you have GD support compiled in. Please look at <a href=\"http://at.php.net/gd\">http://at.php.net/gd</a> how to correct this problem.</p>");
 } ;

# TODO: Check for Database-support

###########################################################################
# Checking permissions of critical files
###########################################################################
#$p_files[] = 'graph/chart.php';
$p_files[] = 'config.php';
#$p_files[] = 'details.php';  // Why should we check that file?
#$p_files[] = 'generate_graph.php'; // Why should we check that file?
#$p_files[] = 'HTML/Table.php';

echo("<p>Checking config file.</p>\n");

	if ( is_readable("config.php") ){
		echo("<p class=\"ok\"> Config File config.php is readable. I hope you have configured it correctly.</p>");
	}else{
		echo("<p class=\"error\"> Config File config.php is not readable or does not exist. You can find an example configuration in sample-config.php. Copy that file to config.php and configure phplicensewatch there.</p>");
	} ;



include_once('HTML/Table.php');

if ( class_exists("HTML_Table") ) {
	echo("<p class=\"ok\">Great PEAR Table class exists. GOOD</p>");
} else{
	echo("<p class=\"error\">ERROR: PEAR Table class does not exist. Most likely you don\'t have PEAR installed. If you do your php.ini may not be have the path variable set to the PEAR libraries. Please check <a href=\"http://pear.php.net/manual/en/installation.getting.php\">http://pear.php.net/manual/en/installation.getting.php</a> on how to install PEAR.</p>");
}

echo("<p>Verifying whether the Flexlm lmutil (<b>$lmutil_loc</b>) is executable:</p>\n ");

if ( is_executable( $lmutil_loc) ){
	echo("<p class=\"ok\"><b>$lmutil_loc</b> exists and is executable.</p>\n");
}else{
	echo("<p class=\"warning\">WARNING: $lmutil_loc It is not executable or there is some kind of issue.  This is not a fatal error, but you will not be able to monitor Flexlm Servers. Please fix the \$lmutil_loc variable, check permissions or check whether file exists. (You can download a binary from <a href=\"http://www.macrovision.com/services/support/flexlm/lmgrd.shtml\">http://www.macrovision.com/services/support/flexlm/lmgrd.shtml</a>)</p>\n");
}


echo("<p>Verifying whether RRDtool is available:</p>\n ");

if ( isset($rrdtool_bin) && is_executable($rrdtool_bin) ){
	echo("<p class=\"ok\"><b>$rrdtool_bin</b> exists and is executable.</p>\n");
} else {
	echo("<p class=\"warning\">WARNING: RRDtool is not installed. If you want support please install RRDtool and set <tt>\$rrdtool_bin</tt> and <tt>\$rrd_dir</tt>.</p>\n");
}

echo("<p>Verifying whether the LUM i4blt (<b>$i4blt_loc</b>) is executable:</p>\n ");

if ( is_executable( $i4blt_loc ."a") ){
	echo("<p class=\"ok\"><b>$i4blt_loc</b> exists and is executable.</p>\n");
}else{
	echo("<p class=\"warning\">WARNING: $i4blt_loc It is not executable or there is some kind of issue. This is not a fatal error, but you will not be able to monitor LUM Servers. Please fix the <tt>\$i4blt_loc</tt> variable, check permissions or check whether file exists. (You can download a binary from <a href=\"http://www-306.ibm.com/software/awdtools/lum/component.html\">http://www-306.ibm.com/software/awdtools/lum/component.html</a>)</p>\n");
}

###########################################################################
# Check whether DB connection works
###########################################################################
if ( isset($db_hostname) && isset($db_username) && isset($db_password) ) {

	require_once 'DB.php';
	
	echo("<p>Checking DB connection: </p>");
	
	$db = DB::connect($dsn, true);
	
	if (DB::isError($db)) {
	
		echo("<p class=\"warning\">WARNING: DB connection failed because: " . $db->getMessage() . "<br/> Phplicensewatcher will work without a database connection, but you can only look at the current status - no long term statistics will be available.</p>
		<p>Your current settings are</p>
		<pre>
		\$db_hostname = $db_hostname;
		\$db_username = $db_username;
		\$db_password = $db_password;
		\$db_database = $db_database;
		\$dsn = $dsn
		</pre>\n");
	} else {
		echo("<p class=\"ok\">DB connect succeeded.</p>\n");
	}	
	
} else {
		echo("<p class=\"warning\">WARNING: None of the DB settings set. You need to set \$db_hostname, \$db_username and \$db_password.</p>\n");
	
}

echo("<h2 style=\"color: green;\">Congratulations all tests passed. This hopefully means :-) that PHPlicensewatcher is ready for use</h2>");

?>
</body></html>

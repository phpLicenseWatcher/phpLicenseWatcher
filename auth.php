<?php

################################################################################################
# This script is used for authenticating users with PHPLicensewatcher. It is implemented
# using Basic HTTP authentication. In order for this script to do anything both admin
# username and password have to be set. If they are not we assume that no authentication
# is needed
################################################################################################

if ( isset($adminusername) && isset($adminpassword) ) {
    
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="PHPLicenseWatcher Admin"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Sorry. This feature requires authorization';
		exit;
	} else {
		if ( ! ($adminusername == $_SERVER['PHP_AUTH_USER'] && $adminpassword == $_SERVER['PHP_AUTH_PW'])  ){
			header('WWW-Authenticate: Basic realm="PHPLicenseWatcher Admin"');
	        	header('HTTP/1.0 401 Unauthorized');
		}
	}
}

?>

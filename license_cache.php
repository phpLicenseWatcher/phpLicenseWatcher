<?php

if ( ! is_readable('./config.php') ) {
    echo("<H2>Error: Configuration file config.php does not exist. Please
         notify your system administrator.</H2>");
    exit;
} else
    include_once('./config.php');

require_once("./common.php");

###################################################
# We are using PEAR's DB abstraction library
###################################################
require_once("DB.php");    


################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
    die ($db->getMessage());
}

$today = date("Y-m-d");

for ( $i = 0 ; $i < sizeof($servers); $i++ ) {
  
   $fp = popen($lmutil_loc . " lmstat -a -c " . $servers[$i], "r");

   while ( !feof ($fp) ) {

        $line = fgets ($fp, 1024);

        # Look for features in the output. You will see stuff like
        # Users of Allegro_Viewer: (Total of 5 licenses available
        if ( eregi("^Users of (.*)Total ", $line ) )  {
                $out = explode(" ", $line);
		# Remove the : in the end of the string
		$feature = str_replace(":", "", $out[2]);
                # Return the number
		$sql = "INSERT INTO licenses_available (flmavailable_server,flmavailable_date,flmavailable_product,flmavailable_num_licenses) VALUES ('$servers[$i]','" . 
			$today . "','" . $feature . "'," . $out[6] . ")";
                
                if ( isset($debug) && $debug == 1 )
	              	print_sql ($sql);

                $recordset = $db->query($sql);

                if (DB::isError($recordset)) {
		    die ($recordset->getMessage());
		}
	}

   }

    pclose($fp);

}

$db->disconnect();

?>

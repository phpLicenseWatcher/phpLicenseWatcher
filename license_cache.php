<?php

if ( ! is_readable(__DIR__.'/config.php') ) {
    echo("<H2>Error: Configuration file config.php does not exist. Please
         notify your system administrator.</H2>");
    exit;
} else
    include_once(__DIR__.'/config.php');

require_once(__DIR__."/common.php");

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
        if ( preg_match("/^Users of (.*)Total /i", $line ) )  {
                $out = explode(" ", $line);
		# Remove the : in the end of the string
		$feature = str_replace(":", "", $out[2]);
                # Return the number

        $sql = <<<SQL
INSERT INTO `available` (`license_id`, `date`, `num_licenses`)
    SELECT `licenses`.`id`, '{$today}', {$out[6]}
    FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`='{$servers[$i]}' AND `features`.`name`='{$feature}';
SQL;

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

<?php

if ( ! is_readable('./config.php') ) {
    echo("<H2>Error: Configuration file config.php does not exist. Please
         notify your system administrator.</H2>");
    exit;
} else
    include_once('./config.php');

if ( ! is_readable('./tools.php') ) {
    echo("<H2>Error: Tools.php does not exist. Please
         notify your system administrator.</H2>");
    exit;
} else
    include_once('./tools.php');

require_once("./common.php");

##################################################
# Load PEAR DB abstraction library
##################################################
require_once("DB.php");


################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
     die ($db->getMessage());
}


#########################################################
# This may take a long time depending on the size of your log files
# so we tell PHP not to limit the time of execution.
#########################################################
set_time_limit(0);

$mydate[] = date("Y-m-d", mktime (0,0,0,date("m"),date("d"),  date("Y")));
$mydate[] = date("Y-m-d", mktime (0,0,0,date("m"),date("d")-1,  date("Y")));


#########################################################
# Loop through the log files specified in config.php
#########################################################
for ( $k = 0 ; $k < sizeof($log_file) ; $k++ ) { 

    # Open the log file
    $file = fopen ( $log_file[$k], "r");

        if (!$file) {
            echo "Unable to open file " . $log_file[$k] . "\n
            Exiting ............";
            exit;
        } else {
            echo "Processing file " . $log_file[$k] . "\n-------------------------\n";
        }  

        #####################################################
        # Loop through the file
        #####################################################
        while (!feof ($file)) {
    
            $line = fgets ($file, 1024);
	#	print $line;
            ###################################################
            # Look for the time stamp. Time stamp is actually current date
            # which is logged only when FlexLM starts the log or at midnight.
            ###################################################            
            if (eregi ("TIMESTAMP (.*)", $line, $out2)) {

                $timestamp_date =  convert_to_mysql_date($out2[1]);

            } else 

            # if ($timestamp_date && in_array($timestamp_date, $mydate) && eregi('(.*) \((.*)\) DENIED: "(.*)" (.*)  (.*)', $line, $out2)) {
            if (isset($timestamp_date) && eregi('(.*) \((.*)\) (IN:|OUT:|DENIED:) "(.*)" (.*)', $line, $out2)) {
            
                # Strip :
                $log_event = substr($out2[3],0, strpos($out2[3],":"));
                # Strip username from the username@hostname
                $username = substr($out2[5],0, strpos($out2[5],"@"));
                
				eregi('(.*) \((.*)\) (OUT:|DENIED:) "(.*)" (.*)  (.*)', $line, $out3);
					
				if ( !isset( $out3[6] ) ) 
						$out2[6] = "";
				else 
						$out2[6] = $out3[6];

				unset($out3);
								
                $sql = "INSERT IGNORE INTO flexlm_events (flmevent_date, flmevent_time, flmevent_type, flmevent_feature, flmevent_user, flmevent_reason) 
                VALUES ('" . $timestamp_date . "','" . trim($out2[1]) . "','" . $log_event ."','" . $out2[4] . "','" . $username . "','" . trim($out2[6]) . "')";

		unset($out2);
                
                if ( isset($debug) && $debug == 1 )
                	print_sql ($sql);
                
                $recordset = $db->query($sql);

                 if (DB::isError($recordset)) {
 		    die ($recordset->getMessage());
 		}

		unset($recordset);

            }


        }
    
# Close the log file
fclose($file);

}

$db->disconnect();

?>

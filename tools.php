<?php

/**
 * @param $server Server name being queried.  "{port}@{domain}.{tld}".
 * @param &$expiration_array
 * @return integer 1
 */
function build_license_expiration_array($server, &$expiration_array) {
    global $lmutil_binary; // from config.php

    $total_licenses = 0;
    $file = popen("{$lmutil_binary} lmcksum -c {$server}", "r");
    $today = time();

    // Let's read in the file line by line
    while (!feof ($file)) {
        $line = fgets ($file, 1024);
        if ( preg_match("/INCREMENT .*/i", $line, $out ) || preg_match("/FEATURE .*/i", $line, $out ) ) {
            $license = explode(" ", $out[0]);

        	if ( $license[4] )  {
            	// UNIX time stamps go only till year 2038 (or so) so convert
            	// any license dates 9999 or 0000 (which means infinity) to
            	// an acceptable year. 2036 seems like a good number
                $license[4] = strtolower($license[4]);
                $license[4] = str_replace("-9999", "-2036", $license[4]);
                $license[4] = str_replace("-0000", "-2036", $license[4]);
                $license[4] = str_replace("-2099", "-2036", $license[4]);
                $license[4] = str_replace("-2242", "-2036", $license[4]);
                $license[4] = str_replace("-jan-00", "-jan-2036", $license[4]);
                $license[4] = str_replace("-jan-0", "-jan-2036", $license[4]);
                $license[4] = str_replace("-0", "-2036", $license[4]);
                $license[4] = str_replace("permanent", "05-jul-2036", $license[4]);
                $unixdate2 = strtotime($license[4]);

                // Convert the date you got into UNIX time
                $unixdate2 = strtotime($license[4]);
        	}

            $days_to_expiration = ceil ((1 + strtotime($license[4]) - $today) / 86400);

            // If there is more than 4000 days (10 years+) until expiration, I
            // will consider the license to be permanent
            if ( $days_to_expiration > 4000 ) {
                $license[4] = "permanent";
                // $days_to_expiration = "permanent";
            }


            // Add to the expiration array
            $expiration_array[$license[1]][] = array (
        	    "vendor_daemon"      => $license[2],
                "expiration_date"    => $license[4],
                "num_licenses"       => $license[5],
                "days_to_expiration" => (int) $days_to_expiration
            );
        }
    }

    pclose($file);
    return 1;
} // END function build_license_expiration_array()

/**
 * Compare two dates
 *
 * @param $date2
 * @param $date2
 * @return integer 1 when $date1 > $date2, -1 when $date < $date2, 0 when equal.
 */
function compare_dates ($date1, $date2) {
    // Convert any "english" looking date into UNIX time stamp
    $unixdate1 = strtotime($date1);
    $unixdate2 = strtotime($date2);

    return ($unixdate1 <=> $unixdate2);
    // if ($unixdate1 > $unixdate2) {
    //     return 1;
    // } elseif ($unixdate1 < $unxidate2) {
    //     return -1;
    // } elseif ($unixdate1 === $unxidate2) {
    //     return 0;
    // }
}

/**
 * Convert a MySQL date ie. 2001-05-20 to the US date format ie. 05/20/2001
 *
 * @param $date MySQL date
 * @return string US date
 */
function convert_from_mysql_date($date) {
    $stringArray = explode("-", $string);
    $date = mktime(0, 0, 0, $stringArray[1], $stringArray[2], $stringArray[0]);
    return date("m/d/Y", $date);
}

/**
 * Convert a US date ie. 05/20/2001 to the MySQL date format ie. 2001-05-20
 *
 * @param $date US Date
 * @return string MySQl date
 */
function convert_to_mysql_date($date) {
    $stringArray = explode("/", $date);
    $date = mktime(0, 0, 0, $stringArray[0], $stringArray[1], $stringArray[2]);
    return date("Y-m-d", $date);
}

// Do we need this?  Remove build_select_box() function if it is not needed.

/**
 * Takes a result set, with the first column being the "id" or value and the
 * second column being the text you want displayed
 *
 * @param $array
 * @param $name name you want assigned to this form element
 * @param $checked_val value of the item that should be checked (optional)
 */
function build_select_box ($array, $name, $checked_val="xzxz") {
    print "<select name=\"{$name}\">";
    for ($i = 0; $i < sizeof($array); $i++) {
        print "<option value=\"{$array[$i]}\"";
        if ($array[$i] == $checked_val) {
            print " selected";
        }

        print ">{$array[$i]}</option>";
    }
    print "</select>";
}

/**
 * Get the number of available licenses for a particular feature.
 *
 * @param string $myFeature
 * @return string number of licenses available as string
 */
function num_licenses_available($myfeature) {
    global $lmutil_binary;

    db_connect($db);
    $servers = db_get_servers($db);
    $db->disconnect();

    $license_file = "";

    // Build LM_LICENSE_FILE
    foreach ($servers as $server) {
        $license_file .= "{$server['name']}:";
    }

    $fp = popen("{$lmutil_binary} lmstat -f {$myfeature} -c {$license_file}", "r");
        while ( !feof ($fp) ) {
    	$line = fgets ($fp, 1024);

        // Look for features in the output. You will see stuff like
    	// Users of Allegro_Viewer: (Total of 5 licenses available
    	if ( preg_match("/^Users of/i", $line ) )  {
    		$out = explode(" ", $line);
            pclose($fp);
            // Return the number
    		return $out[6];
    	}
    }
}

/**
 * Get the number of used licenses for a particular feature.
 *
 * @param string $myfeature
 * @return integer number of licenses
 */
function num_licenses_used($myfeature) {
    global $lmstat_command;

    db_connect($db);
    $servers = db_get_servers($db);
    $db->disconnect();

    $license_file = "";

    // Build LM_LICENSE_FILE
    foreach ($servers as $server) {
        $license_file .= "{$server['name']}:";
    }

    $fp = popen("{$lmstat_command} -f {$myfeature} -c {$license_file}", "r");
    $num_licenses = 0;

    while ( !feof ($fp) ) {
        $line = fgets ($fp, 1024);

        // Look for features in the output. You will see stuff like
	    // Users of Allegro_Viewer: (Total of 5 licenses available
	    if ( preg_match("/, start/i", $line ) )
            $num_licenses++;
    }

    pclose($fp);
    return $num_licenses;
}

function run_command($command) {
    global $lmutil_binary;
    $data = "";

    if (!cache_check($command, $data)) {
        $fp = popen($command, "r");
        $data = "";
        while (!feof($fp)) {
            $data .= fgets($fp, 1024);
        }

        pclose($fp);
        cache_store($command, $data);
    }

    return $data;
}

function cache_check($command , &$data) {
    $result = false;
    $cache_dir = '/tmp/';
    $hash = md5( $command );
    $cacheFile = $cache_dir . $hash ;

    if (file_exists($cacheFile)) {
        if (time() - filemtime($cacheFile) > 2 * 3600) {
            // file older than 2 hours
        } else {
            // file younger than 2 hours
            $data = file_get_contents($cacheFile);
            $result = true;
        }
    }

    return $result;
}

function cache_store($command , $data) {
    $cache_dir = '/tmp/';
    $hash = md5($command);
    $cacheFile = $cache_dir . $hash;
    file_put_contents($cacheFile, $data);
}

// Do we need this?  Remove timespan class if it is not needed.

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#                                                                             #
# B00zy's timespan script v1.2                                                #
#                                                                             #
# timespan -- get the exact time span between any two moments in time.        #
#                                                                             #
# Description:                                                                #
#                                                                             #
#        class timespan, function calc ( int timestamp1, int timestamp2)      #
#                                                                             #
#        The purpose of this script is to be able to return the time span     #
#        between any two specific moments in time AFTER the Unix Epoch        #
#        (January 1 1970) in a human-readable format. You could, for example, #
#        determine your age, how long you have been married, or the last time #
#        you... you know. ;)                                                  #
#                                                                             #
#        The class, "timespan", will produce variables within the class       #
#        respectively titled years, months, weeks, days, hours, minutes,      #
#        seconds.                                                             #
#                                                                             #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#                                                                             #
# Example 1. B00zy's age.                                                     #
#                                                                             #
#        $t = new timespan( time(), mktime(0,13,0,8,28,1982));                #
#        print "B00zy is $t->years years, $t->months months, ".               #
#                "$t->days days, $t->hours hours, $t->minutes minutes, ".     #
#                "and $t->seconds seconds old.\n";                            #
#                                                                             #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

define('day', 60*60*24 );
define('hour', 60*60 );
define('minute', 60 );

class timespan
{
    var $years;
    var $months;
    var $weeks;
    var $days;
    var $hours;
    var $minutes;
    var $seconds;

    function leap($time) {
        if (date('L',$time) and (date('z',$time) > 58))
            return (double)(60*60*24*366);
        else {
            $de = getdate($time);
            $mkt = mktime(0,0,0,$de['mon'],$de['mday'],($de['year'] - 1));
            if ((date('z',$time) <= 58) and date('L',$mkt))
                return (double)(60*60*24*366);
            else
                return (double)(60*60*24*365);
        }
    }

    function readable() {
        $values = array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds');
        foreach ($values as $k => $v)
            if ($this->{$v})
                $fmt .= ($fmt ? ', ' : '') . $this->{$v} . " $v";
        return $fmt . ($fmt ? '.' : '') ;
    }

    function timespan($after,$before) {
        // Set variables to zero, instead of null.
        $this->years = 0;
        $this->months = 0;
        $this->weeks = 0;
        $this->days = 0;
        $this->hours = 0;
        $this->minutes = 0;
        $this->seconds = 0;

        $duration = $after - $before;

        // 1. Number of years
        $dec = $after;
        $year = $this->leap($dec);

        while (floor($duration / $year) >= 1) {
	       // We don't need this VV
           //print date("F j, Y\n",$dec);
           $this->years += 1;
           $duration -= (int)$year;
           $dec -= (int)$year;
           $year = $this->leap($dec);
        }

        // 2. Number of months
        $dec = $after;
        $m = date('n',$after);
        $d = date('j',$after);

        while (($duration - day) >= 0) {
            $duration -= day;
            $dec -= day;
            $this->days += 1;

            if ( (date('n',$dec) != $m) and (date('j',$dec) <= $d) ) {
                $m = date('n',$dec);
                $d = date('j',$dec);

                $this->months += 1;
                $this->days = 0;
            }
        }

        // 3. Number of weeks.
        $this->weeks = floor($this->days / 7);
        $this->days %= 7;

        // 4. Number of hours, minutes, and seconds.
        $this->hours = floor($duration / (60*60));
        $duration %= (60*60);

        $this->minutes = floor($duration / 60);
        $duration %= 60;

        $this->seconds = $duration;
    }
}

?>

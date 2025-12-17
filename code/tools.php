<?php
require_once __DIR__ . "/lmtools.php";

/**
 * @param array $server Server's 'name' and 'license_manager' being queried.
 * @param array &$expiration_array
 */
function build_license_expiration_array(array $server, &$expiration_array) {
    // Expirations (in days) longer than 10 years are assumed to be permanent.
    $permanent_threshold = 4000; // Nice round number greater than 10 years (in days)
    $today = time();

    $lmtools = new lmtools();
    $lmtools->lm_open($server['license_manager'], 'tools__build_license_expiration_array', $server['name']);
    $lmdata = $lmtools->lm_nextline();

    // Let's read in the file line by line
    while (!is_null($lmdata)) {
        if ($lmdata['expiration_date'] !== "") {
            switch(true) {
            // Indicators that license is perpetual/permanent
            case preg_match("/-0000$/", $lmdata['expiration_date']) === 1:
            case preg_match("/-9999$/", $lmdata['expiration_date']) === 1:
            case preg_match("/-2099$/", $lmdata['expiration_date']) === 1:
            case preg_match("/-2242$/", $lmdata['expiration_date']) === 1:
            case preg_match("/-00$/",   $lmdata['expiration_date']) === 1:
            case preg_match("/-0$/",    $lmdata['expiration_date']) === 1:
            case strtolower($lmdata['expiration_date']) === "permanent":
                $days_to_expiration = PHP_INT_MAX;
                $lmdata['expiration_date'] = "permanent";
                break;
            // License not indicated as permanent.  Calculate days remaining.
            // We are assuming 64-bit Unix time.
            default:
                $days_to_expiration = strtotime($lmdata['expiration_date']) !== false  ?
                    ceil((1 + strtotime($lmdata['expiration_date']) - $today) / 86400) :
                    "N/A";
                // Although licenses more than 10 years old are still considered permanent.
                if ($days_to_expiration > $permanent_threshold) {
                    $days_to_expiration = PHP_INT_MAX;
                    $lmdata['expiration_date'] = "permanent";
                }
                break;
            }
        } else {
            // We didn't find an expiration date, so assume license is permanent.
            $days_to_expiration = PHP_INT_MAX;
            $lmdata['expiration_date'] = "permanent";
        }

        // Add to the expiration array
        $expiration_array[$lmdata['name']][] = array (
            "vendor_daemon"      => $lmdata['vendor_daemon'],
            "expiration_date"    => $lmdata['expiration_date'],
            "num_licenses"       => $lmdata['num_licenses'],
            "days_to_expiration" => $days_to_expiration
        );

        $lmdata = $lmtools->lm_nextline();
    }
} // END function build_license_expiration_array()

/**
 * Compare two dates
 *
 * @param $date1
 * @param $date2
 * @return integer 1 when $date1 > $date2, -1 when $date < $date2, 0 when equal.
 */
function compare_dates ($date1, $date2) {
    // Convert any "english" looking date into UNIX time stamp
    $unixdate1 = strtotime($date1);
    $unixdate2 = strtotime($date2);

    // q.v. https://wiki.php.net/rfc/combined-comparison-operator for '<=>' (spaceship) operater.
    return ($unixdate1 <=> $unixdate2);
}

/**
 * Convert a MySQL date ie. 2001-05-20 to the US date format ie. 05/20/2001
 *
 * @param $date MySQL date
 * @return string US date
 */
function convert_from_mysql_date($date) {
    $stringArray = explode("-", $date);
    $date = mktime(0, 0, 0, $stringArray[1], $stringArray[2], $stringArray[0]);
    return date("m/d/Y", $date);
}

/**
 * Convert a US date ie. 05/20/2001 to the MySQL date format ie. 2001-05-20
 *
 * @param $date US Date
 * @return string MySQL date
 */
function convert_to_mysql_date($date) {
    $stringArray = explode("/", $date);
    $date = mktime(0, 0, 0, $stringArray[0], $stringArray[1], $stringArray[2]);
    return date("Y-m-d", $date);
}

/**
 * Build an html selectbox with given arguments.
 *
 * Needed by server admin edit form, but available anywhere.
 *
 * @param $options Options for selectbox.
 * @param $properties Properties for the selectbox.  e.g. name, class, id
 * @param $checked_value Value of the item that should be checked (optional)
 * @return string HTML code for selectbox.
 */
function build_select_box (array $options, array $properties=array(), $checked_value=null) {
    $checked_value = is_string($checked_value) ? strtolower($checked_value) : "";
    $html_properties = "";
    array_walk($properties, function($val, $key) use (&$html_properties) {
        $html_properties .= " {$key}='{$val}'";
    });

    $html = "<select{$html_properties}>\n";
    foreach ($options as $option) {
        $option_value = strtolower($option);
        $option_selectable = ucwords($option);
        $is_selected = ($option_value === $checked_value) ? " selected" : "";
        $html .= "<option value='{$option_value}'{$is_selected}>{$option_selectable}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}

/**
 * Get the number of available licenses for a particular feature.
 *
 * TO DO: popen()/fgets() converted to lmtools::lm_open()/lmtools::lm_nextline()
 *        Not currently used by system.
 *
 * @param string $myFeature
 * @return string number of licenses available as string
 */
function num_licenses_available($feature) {
    global $lmutil_binary; // from config.php

    db_connect($db);
    $servers = db_get_servers($db, array('name'));
    $db->disconnect();

    $license_file = "";

    // Build LM_LICENSE_FILE
    foreach ($servers as $server) {
        $license_file .= "{$server['name']}:";
    }

    $fp = popen("{$lmutil_binary} lmstat -f {$feature} -c {$license_file}", "r");
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
 * TO DO: popen()/fgets() converted to lmtools::lm_open()/lmtools::lm_nextline()
 *        Not currently used by system.
 *
 * @param string $myfeature
 * @return integer number of licenses
 */
function num_licenses_used($feature) {
    global $lmutil_binary;  // from config.php

    db_connect($db);
    $servers = db_get_servers($db, array('name'));
    $db->disconnect();

    $license_file = "";

    // Build LM_LICENSE_FILE
    foreach ($servers as $server) {
        $license_file .= "{$server['name']}:";
    }

    $fp = popen("{$lmutil_binary} lmstat -f {$feature} -c {$license_file}", "r");
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

/**
 * Run cli command.  Use disk cache.
 *
 * TO DO: Disk cache isn't currently used.  This should be moved to lmtools.php
 *        class
 *
 * @param string $command  path and exectuable of cli command to run.
 * @return string Output from $command, possibly from disk cache.
 */
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

/**
 * Check if $data from $command is cached.
 *
 * Return result from disk cache if cache is less than two hours old.
 *
 * @param string $command Command run to check cache against.
 * @param string &$data Data retrieved from disk cache.
 * @return boolean true when $data is retrieved from cache, false otherwise.
 */
function cache_check($command, &$data) {
    global $cache_dir, $cache_lifetime; // from config.php
    $result = false;
    $hash = md5($command);
    $cacheFile = "{$cache_dir}{$hash}.cache";

    if (file_exists($cacheFile)) {
        if (time() - filemtime($cacheFile) <= $cache_lifetime) {
            // Cache file younger than 2 hours.  Read data from cache.
            $data = file_get_contents($cacheFile);
            $result = true;
        }
    }

    return $result;
}

/**
 * Write data to disk cache.
 *
 * @param $command Command run (used to hash cache file)
 * @param $data Data written to disk cache.
 */
function cache_store($command, $data) {
    global $cache_dir; // from config.php
    $hash = md5($command);
    $cacheFile = "{$cache_dir}{$hash}.cache";
    file_put_contents($cacheFile, $data);
}

/**
 * Interpret a DateInterval object into words.
 *
 * DateInterval's format function doesn't give us the power to exclude a
 * zero value.  e.g. $dti->format("%y year(s), %m month(s), %d day(s).") will
 * always include years, months, and days even when they are zero.  Same
 * goes for hours, minutes, seconds.  This function will exclude zero values.
 * e.g. years = 1, months = 0, days = 5 is interpeted as "1 year(s), 5 day(s)"
 *
 * @param DateInterval $dti
 * @return string Readable sentence describing DateInterval object.
 */
function get_readable_timespan(DateInterval $dti) {
    // Break days into weeks.  As of PHP 8.0, DateInterval does not have a
    // weeks property, so we'll record weeks as $w.  Note that when
    // $dti->d (represents days) < 7, $w is 0 and $dti->d is unchanged.
    $w = intdiv($dti->d, 7);
    $dti->d = $dti->d % 7;

    // Build readable duration string
    $readable = array();
    $vals = array($dti->y, $dti->m, $w, $dti->d, $dti->h, $dti->i);
    $units = array('years', 'months', 'weeks', 'days', 'hours', 'minutes');
    foreach ($vals as $i => $val) {
        if ($val > 0) $readable[] = "{$val} {$units[$i]}";
    }
    $readable = implode(", ", $readable);
    return $readable;
}

?>

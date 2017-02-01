<?php

if ( ! is_readable('./config.php') ) {
    echo("<H2>Error: Configuration file config.php does not exist. Please
         notify your system administrator.</H2>");
    exit;
} else
    include_once('./config.php');

if ( isset($disable_license_removal) && $disable_license_removal == 1 ) {

    die("Sorry this feature is not enabled. If you need it talk to the maintainer of this
        page on how to enable it.");

}


?>

<HTML>
<TITLE>License Removal Status</TITLE>
<HEAD>
<STYLE>
BODY { background: #ffffff }
TH { background: #CCCCCC }
TD { font-size: 14 pt; text-align: center }
</STYLE>

<?php


$args=split(" ", $_GET['arg']);

######################################################################
# Due to laziness feature contains colon (:) at the end. We need
# to strip it 
######################################################################
$featurename = escapeshellcmd(substr($_GET['feature'],0,strpos($_GET['feature'],":")));

if (ereg("^[a-zA-Z0-9\-]+", $featurename)) { 
    echo("OK");
    echo($featurename);
} else {
    
    die("Security Alert: Feature name you supplied has illegal characters that
        could possibly be used for security compromise.");
}

exit;

######################################################################
# For security purposes and other reasons before we remove a license
# we'll try to make sure whether it is actually used
######################################################################
$fp = popen($lmutil_loc . " lmstat -f " . $featurename . " -c " . $servers[$_GET['server']] , "r");


while ( !feof ($fp) ) {
    
    $line = fgets ($fp, 1024);
    
    
    if ( eregi ("$args[0] $args[1] $args[2] ", $line, $matchedline ) ) {

        # Make 
        $commandline = ($lmutil_loc . " lmremove -c " . $servers[$_GET['server']] . " " . $featurename 
            . " " .$matchedline[0] );

 
        echo("Output of your removal command<p><PRE>");

        $fp2 = popen($commandline , "r");

        while ( !feof ($fp2) ) {
    
            $line = fgets ($fp2, 1024);

            echo($line);

        }

        break;

    }
}

# Close pipes
fclose($fp);
fclose($fp2);

?>

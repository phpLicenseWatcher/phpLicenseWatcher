<?php
require_once(__DIR__."/common.php");
require_once("DB.php");

print"<pre>";
print_r(__DIR__."/common.php", true);
print"</pre>"; die;

//$db = db_connnect();
$sql = "SELECT `name`, `label` FROM `servers` WHERE `is_active` = 1;";
$servers = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
$today = date("Y-m-d");

for ( $i = 0 ; $i < sizeof($servers); $i++ ) {
    $fp = popen($lmutil_loc . " lmstat -a -c " . $servers[$i], "r");
    while ( !feof ($fp) ) {
        $line = fgets ($fp, 1024);

        // Look for features in the output. You will see stuff like
        // Users of Allegro_Viewer: (Total of 5 licenses available
        if ( preg_match("/^Users of (.*)Total /i", $line ) )  {
            $out = explode(" ", $line);
            // Remove the : in the end of the string
            $feature = str_replace(":", "", $out[2]);

            $sql = <<<SQL
INSERT INTO `available` (`license_id`, `date`, `num_licenses`)
    SELECT `licenses`.`id`, '{$today}', {$out[6]}
    FROM `licenses`
    JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
    JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
    WHERE `servers`.`name`='{$servers[$i]["name"]}' AND `features`.`name`='{$feature}';
SQL;

            $recordset = $db->query($sql);
            print_sql ($sql);
            if (DB::isError($recordset)) {
                die ($recordset->getMessage());
            }
        }
    }
    pclose($fp);
}

$db->disconnect();

?>

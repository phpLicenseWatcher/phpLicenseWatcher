<?php

require_once("./common.php");
require_once('./tools.php');
require_once('./config.php');


require_once("DB.php");


$feature = preg_replace("/[^a-zA-Z0-9_]+/", "", htmlspecialchars($_GET['feature'])) ;
$days = intval($_GET['days']);


if( !$days > 0 ){
    $days= 7;
}

################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
  die ($db->getMessage());
}

    $result = array( "cols"=>array() , "rows"=>array() );

    $result["cols"][] = array("id"=>"","label"=>"Feature","pattern"=>"","type"=>"string");
    $result["cols"][] = array("id"=>"","label"=>"Users","pattern"=>"","type"=>"number");

    $sql = "SELECT flmusage_product , CONCAT( flmusage_date , ' ' , flmusage_time ) as flmusage_datetime , SUM(flmusage_users) 
FROM license_usage WHERE flmusage_product='$feature'  
    AND DATE_SUB(NOW(),INTERVAL $days DAY ) <= DATE(flmusage_date)
GROUP BY flmusage_product, flmusage_datetime";

    $recordset = $db->query($sql);
  
    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    while ($row = $recordset->fetchRow()){
       $result['rows'][] = array('c' => array( array('v'=>$row[1]), array('v'=>$row[2])) );        
    }

  
    $recordset->free();

$db->disconnect();


header('Content-Type: application/json');
echo json_encode( $result);

?>
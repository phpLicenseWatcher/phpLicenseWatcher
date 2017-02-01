<?php

if ( ! is_readable('./config.php') ) {
  echo("<H2>Error: Configuration file config.php does not exist. Please
         notify your system administrator.</H2>");
  exit;
} else
include_once('./config.php');


/* ---------------------------------------------------------------------- */
include_once('./tools.php');

##########################################################3
# Make sure cdiagram library is included
##########################################################3
require_once("./cdiagram/class/diagram.php");

require_once("DB.php");

##########################################################
# Create the new graph with the specified size. If smallsize is set use it
# to create small graphs that show up on the main utilization page
##########################################################
if ( isset($_GET['smallsize']) ) {
  $size = explode(",", $smallgraph);
  $image = new CDiagram($size[0],$size[1],"PNG");
} else {
  $size = explode(",", $largegraph);
  $image = new CDiagram($size[0],$size[1],"PNG");
}

$image->setDiagramType("Line"); # Balken");
$image->setBackground(255,255,255);
$image->setTextcolor(0,0,0);
$image->setLinecolor(0,0,0);
$image->setGraphcolor(0,255,0);
$image->setValuecolor(255,0,0);
$image->paintHeader(htmlspecialchars($_GET['feature']),4);


################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
  die ($db->getMessage());
}

################################################################
# When drawing utilization it is useful to know what was the usage in comparison
# to available licenses. Graphing package will try to auto-scale the Y-axis depending
# on the largest available Y value. Unfortunately that is not too useful since we will
# likely show higher usage that the number of available licenses. Thus you need
# to tell the graph script what its limits are

$sql = "SELECT flmavailable_num_licenses FROM licenses_available WHERE flmavailable_product='" .
htmlspecialchars($_GET['feature']) . "' AND flmavailable_date = '" . 
htmlspecialchars($_GET['mydate']) . "'";

$upper_limit = $db->getOne($sql);
$lic_avail = isset($upper_limit);
$i = 0;
while ((!$lic_avail || $upper_limit <= 0 ) && ($i < 30)){
    $i++;
    if (!$lic_avail){
        $current_date = mktime(0,0,0,date("m"),date("d") - $i,  date("Y"));
	$sql = "SELECT flmavailable_num_licenses FROM licenses_available WHERE flmavailable_product='" .
	htmlspecialchars($_GET['feature']) . "' AND flmavailable_date = '" . 
	date("Y-m-d", $current_date) . "'";
	$upper_limit = $db->getOne($sql);
	$lic_avail = isset($upper_limit);
    }
}

if ($i == 30) {
  
  generate_error_image(" license_cache.php isn't run on : " . date("Y-m-d", $current_date) . " " . $upper_limit . " " . $lic_avail );
  
  exit;
}


#if ( !isset($upper_limit) || $upper_limit <= 0 ) {
  
#  generate_error_image("Total num licenses not available. license_cache.php isn't run");
  
#  exit;
#}


$period = htmlspecialchars($_GET['period']);
$i = 1;

# Set some kind of value even if the set is empty
$image->setData(1,0);
$image->setItemData(0,0);

$current_date = mktime (0,0,0,date("m"),date("d"),  date("Y"));

switch ($period) {
	case 'day' :
		$xlimit = 0;
		break;
	case 'week' :
		$xlimit = 6;
		break;
	case 'month' :
		$xlimit = date("t", $current_date) - 1;
		break;
	case 'year' :
		$xlimit = 364;
		break;
}

$daypoint = 1440 / $collection_interval;

$data = array(0);
$j = $xlimit;
$y_max = 0;

while ($j >= 0){

################################################################
# First let's get license usage for the product specified in $feature
##############################################################

    $current_date = mktime (0,0,0,date("m"),date("d") - $j,  date("Y"));
    $today = mktime (0,0,0,date("m"),date("d"),  date("Y"));

    $sql = "SELECT flmusage_server,flmusage_time,SUM(flmusage_users) FROM license_usage WHERE flmusage_product='" .
    htmlspecialchars($_GET['feature']) . "' AND flmusage_date = '" .  date("Y-m-d", $current_date)
    . "' GROUP BY flmusage_time";

    $recordset = $db->query($sql);
  
    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    $i_last = $i;

    while ($row = $recordset->fetchRow()){
        $data[$i] = $row[2];
        if ($row[2] > $y_max)
	    $y_max = $row[2];
        $i++;
    }

    $i_delta = $i - $i_last;

    if (($i_delta < $daypoint) and ($current_date != $today) and ($y_max > 0)){
	$i_limit = $daypoint - $i_delta;
	for ($k = 0; $k < $i_limit; $k++){
	    $data[$i] = 0;
	    $i++;
	}
    }
    
    $j--;
    $recordset->free();
}

#print_r($data);

# Close the connection


# If there is data in the set draw the graph
if ( $i > 1 ) {
    #$daypoint = 1440 / $collection_interval;
    $scalingfactor = $daypoint / $xlegendpoints;

    switch ($period) {
	    case 'day' :
	        $image->setX($i + 0.00001 ,0,1);
	        for ( $j = 1 ; $j <= $xlegendpoints * 2 ; $j++ ) {
     		    $image->setItemData(1+$j*$scalingfactor/2, $j); # *2);
    	    }
	        break;
	    case 'week' :
            # Use first letter format for X labels
	        $image->setX($i + 0.00001 , 0, $daypoint);
	        $xlegendpoint = ceil($i / $daypoint);
	        for ( $j = 1 ; $j <= $xlegendpoint ; $j++ ) {
                $axis_time = mktime (0,0,0,date("m"),date("d") - ($xlegendpoint - $j),  date("Y"));
	            $image->setItemData($j * $daypoint, date("D", $axis_time));
	        }
    	    break;
	    case 'month' :
            # Use dd-mm format for X label (print only monday)
	        $image->setX($i + 0.00001 , 0, $daypoint);
	        $xlegendpoint = ceil($i / $daypoint);
	        for ( $j = 1 ; $j <= $xlegendpoint ; $j++ ) {
	            $axis_time = mktime (0,0,0,date("m"),date("d") - ($xlegendpoint - $j),  date("Y"));
	            $axis_date = date("d-m", $axis_time);
	            if (date("D", $axis_time) == 'Mon')
		            $image->setItemData($j * $daypoint, $axis_date);
	        }
	        break;
	    case 'year' :
            # Only print the end of the month
	        $image->setX($i + 0.00001 , 0, $daypoint); # 30*96
	        $xlegendpoint = ceil($i / $daypoint);
            $nb_day = date("d", $current_date);
            $x = ($xlegendpoint - $nb_day) * $daypoint;
            $x_label = date("M", mktime (0,0,0,date("m") - 1, date("d"), date("Y")));
      	    $image->setItemData($x, $x_label);
	        for ( $j = 1 ; $j <= 11 ; $j++ ) {
		        $nb_day = date("t", mktime (0,0,0,date("m") - $j, date("d"), date("Y")));
		        $x -= $nb_day * $daypoint;
		        $x_label = date("M", mktime (0,0,0,date("m") - ($j + 1), date("d"), date("Y")));
      		    $image->setItemData($x, $x_label);
            }
	        break;
    }
  
    $image->enable("ItemData");
  
    # Upper and lower indices and scale step
    $image->setY($upper_limit + 0.0001,0, ceil ($upper_limit/5));
  
  
#############################################################
# How many data values do we have:
# 24 hrs = 1440 min / collection interval ie.
# 1440 / 15 mins = 96 values
# I want to show legend only every 2 hours ie. 12 points
#############################################################
  
    $image->paintXY();
    # Axis legend
    $image->paintScale("Time", "Number of licenses", "FF_FONT1");
    # Graph color
    $image->setGraphcolor(100,100,200);
    $image->paintData();
  
################################################################################
# We will now draw a line that indicates the number of available licenses ie.
# at the top of the graph we will draw a solid line
################################################################################
  
    # Clear the data from the bar graph
    $image->clearData();
  
    $image->setDiagramType("Line");
    $image->setData(0,$upper_limit);
    $image->setData($i, $upper_limit);
    $image->setGraphcolor(255,0,0);
    $image->paintData();

################################################################################
# We will now draw lines that indicates the scale.
# For all value on Y scale we will draw a solid line
################################################################################

    if ($upper_limit >= 10)
    {
	    for ($j = 1; $j < 10; $j++){
	        $image->clearData();
	        $image->setDiagramType("Line");
	        $image->setData(0, $upper_limit * $j / 10);
	        $image->setData($i, $upper_limit * $j / 10);
            $image->setGraphcolor(222,222,222);
	        $image->paintData();
	    }
    }else{
	    for ($j = 1; $j < $upper_limit; $j++){
	        $image->clearData();
	        $image->setDiagramType("Line");
            $image->setData(0, $j);
	        $image->setData($i, $j);
	        $image->setGraphcolor(222,222,222);
	        $image->paintData();
	    }
    }

    $image->clearData();
    $image->setDiagramType("Line"); # Balken
    for ($j = 0; $j < $i; $j++){
        $image->setData($j, $data[$j]);
    }
    $image->setGraphcolor(100,100,200);
    $image->paintData();
  
    # Should we display debug options
    if ( isset($_GET['debug']) ) {
        echo("If following array is empty your license utilization table is not
            being populated. Most common problem is that license_util.php script is not
            being run periodically ie. every 15 minutes.<p><PRE>"); print_r($image->m_data);
        exit;
    } else {
    # If not draw image
        Header("Content-type: image/png");
        $image->show();
    }

# If there is no data an image should be created indicating the problem.
} else {
  
    generate_error_image("No data available to display for " . $_GET['feature'] . ". license_util.php is not being run");
}



$db->disconnect();


?>

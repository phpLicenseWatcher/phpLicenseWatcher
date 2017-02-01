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
require_once("cdiagram/class/diagram.php");

require_once("DB.php");

##########################################################
# Create the new graph with the specified size. If smallsize is set use it
# to create small graphs that show up on the main utilization page
##########################################################
if ( isset($_GET['smallsize']) && $_GET['smallsize'] == 1) {
   $size = explode(",", $smallgraph);
   $image = new CDiagram($size[0],$size[1],"PNG");
} else {
    $size = explode(",", $largegraph);
   $image = new CDiagram($size[0],$size[1],"PNG");
}

$image->setDiagramType("Balken");
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

if ( !isset($upper_limit) || $upper_limit <= 0 ) {

    generate_error_image("Total num licenses not available. license_cache.php isn't run");

    exit;
}

if ( isset($_GET['time_breakdown']) ) {

    #############################################################################
    # This query gets how many times particular number of users used
    #############################################################################
    $sql = "SELECT flmusage_users,count(*) FROM license_usage WHERE flmusage_product='" .
        htmlspecialchars($_GET['feature']) . "' AND flmusage_date = '" .
        htmlspecialchars($_GET['mydate']) . "' GROUP BY flmusage_users ORDER BY flmusage_users";

    $recordset = $db->query($sql);

    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    while ($row = $recordset->fetchRow()) {

	$data[$row[0]] = $row[1] * ($collection_interval / 60);

    }

    $recordset->free();
    
    # Set some kind of value even if the set is empty otherwise
    # graph function will fail
    $image->setData(1,0);
    $image->setItemData(0,0);

    ###################################################################
    # There may be holes in usage stats ie. only 1 or 3 licenses are
    # used. We loop through the array. If there is no record we set
    # the value to zero
    ###################################################################
    for ( $i = 0; $i <= $upper_limit ; $i++ ) {

        if ( isset($data[$i]) )
	    $image->setData($i+1,$data[$i]);
	else
	    $image->setData($i+1,0);
	
	$image->setItemData($i+1, $i);

    }

    $image->enable("ItemData");
 
    $image->setX($i + 0.00001 ,0, ceil($upper_limit / 15 ));

   # Upper and lower indices and scale step
    $image->setY(max($data) + 0.00001, 0, (int) (100 * max($data)/5) / 100 );
    
    #
    $image->paintXY();
    # Axis legend
    $image->paintScale("Licenses used", "Time used in hours", 1);
    # Graph color
    $image->setGraphcolor(100,200,100);
    $image->paintData();
    
    if ( isset($_GET['debug']) ) {
        echo("If following array is empty your license utilization table is not
            being populated. Most common problem is that license_util.php script is not
            being run periodically ie. every 15 minutes.<p><PRE>"); print_r($image->m_data);
        exit;
    } else
        $image->show();
    

} else {

    ################################################################
    # First let's get license usage for the product specified in $feature
    ##############################################################

    $sql = "SELECT MAX(flmusage_server),flmusage_time,SUM(flmusage_users) FROM license_usage WHERE flmusage_product='" . 
	htmlspecialchars($_GET['feature']) . "' AND flmusage_date = '" . htmlspecialchars($_GET['mydate'])
 	. "' GROUP BY flmusage_time";

    $recordset = $db->query($sql);

    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    $i = 1;

    # Set some kind of value even if the set is empty
    $image->setData(1,0);
    $image->setItemData(0,0);

    while ($row = $recordset->fetchRow()) {

	$image->setData($i,$row[2]);
	$i++;

    }

    $recordset->free();

    # Close the connection


    # If there is data in the set draw the graph
    if ( $i > 1 ) {

        $image->setX($i + 0.00001 ,0,1);

        $image->enable("ItemData");

        # Upper and lower indices and scale step
        $image->setY($upper_limit + 0.0001,0, ceil ($upper_limit/5));


        #############################################################
        # How many data values do we have:
        # 24 hrs = 1440 min / collection interval ie.
        # 1440 / 15 mins = 96 values
        # I want to show legend only every 2 hours ie. 12 points
        #############################################################
        $scalingfactor = (1440 / $collection_interval) / $xlegendpoints;
	
        for ( $j = 1 ; $j <= $xlegendpoints  ; $j++ ) {

            $image->setItemData(1+$j*$scalingfactor, $j*2);

        }

        $image->paintXY();
        # Axis legend
        $image->paintScale("Time", "Number of licenses",2);
        # Graph color
        $image->setGraphcolor(100,200,100);
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
        $image->setGraphcolor(0,0,0);
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

}

$db->disconnect();


?>

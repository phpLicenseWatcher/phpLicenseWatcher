<?php

require_once("./common.php");
print_header("Per Feature License Utilization");

?>
</head><body>

<h1>Per Feature License Utilization</h1>
<p style="a_centre"><a href="utilization.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>

<p>Data is taken every <?php echo($collection_interval); ?> minutes. It shows usage for past 7 days with a graph for each day.</p>

<?php print("<form action=\"".$_SERVER['PHP_SELF']."\">"); ?>
<p>
<input type="hidden" name="feature" value="<?php echo(htmlspecialchars($_GET['feature'])); ?>"/>
<input type="hidden" name="upper_limit" value="<?php if (isset($_GET['upper_limit'])) echo(htmlspecialchars($_GET['upper_limit']));	else echo("") ; ?>"/>
</p>
<p>Set an arbitrary date range for the report:</p>

<table style="background: lightblue;" border="1">

<tr> <td>From:</td>

<?php

/* ---------------------------------------------------------------------- */
include_once('./tools.php');

##################################################
# Load PEAR DB abstraction library
##################################################
require_once("DB.php");

################################################################
#	Connect to the database
#	 Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
	die ($db->getMessage());
}


##################################################################################
# Drop down menus
##################################################################################
$sql = "SELECT DISTINCT flmusage_date FROM license_usage WHERE
	flmusage_product='". htmlspecialchars($_GET['feature']) . "' ORDER BY flmusage_date";

$recordset = $db->query($sql);

while ($row = $recordset->fetchRow()) {
	 $date_array[] = $row[0];
}

$recordset->free();

echo("<td>");

if ( isset($_GET['from_date']) && isset($_GET['to_date']) ){
	build_select_box($date_array, "from_date", htmlspecialchars($_GET['from_date']));
}else{
	build_select_box($date_array, "from_date");
}

echo("</td></tr><tr><td>To:</td><td>");

if ( isset($_GET['from_date']) && isset($_GET['to_date']) ){
	build_select_box($date_array, "to_date", htmlspecialchars($_GET['to_date']));
}else{
	build_select_box($date_array, "to_date");
}

echo("</td></tr><tr><td align=\"center\" colspan=\"2\"><input type=\"submit\" value=\"Submit\"/></td></tr></table></form>");


##################################################################################
# Graphing part
##################################################################################

# Has user supplied its own dates
if ( isset($_GET['from_date']) && isset($_GET['to_date']) ){
	$sql = "SELECT DISTINCT `flmusage_date` FROM `license_usage` WHERE `flmusage_product`='". htmlspecialchars($_GET['feature']) 
	. "' AND `flmusage_date`<='" . $_GET['to_date'] . "' AND `flmusage_date` >= '" .
	$_GET['from_date'] . "' ORDER BY `flmusage_date` DESC";
}else {
	$today = mktime (0,0,0,date("m"),date("d"),	date("Y"));
	$todaylastweek = mktime (0,0,0,date("m"),date("d")-7,	date("Y"));

	$sql = "SELECT DISTINCT `flmusage_date` FROM `license_usage` WHERE `flmusage_product`='". htmlspecialchars($_GET['feature']) 
	 . "' AND `flmusage_date`<='" . date("Y-m-d", $today) . "' AND `flmusage_date` >= '" .
	 date("Y-m-d", $todaylastweek) . "' ORDER BY `flmusage_date` DESC";
}


$recordset = $db->query($sql);

while ($row = $recordset->fetchRow()) {
	$day = date("l", strtotime($row[0])); 

	echo('<h2>Date: ' . $day . ' (' . $row[0] . ')</h2><p>');
	 
	#Build the image URL. Don't close it
	$img_url = '<img src="generate_graph.php?feature=' . htmlspecialchars($_GET['feature'])	. '&amp;mydate=' . $row[0];

	# IF upper_limit is defined append it to the URL ie &upper_limit=10	
	if ( isset($_GET['upper_limit']) ){
		$img_url .=	'&amp;upper_limit=' . htmlspecialchars($_GET['upper_limit']);
	}

	//echo($mg_url . '"/>');
	echo($img_url . "&amp;time_breakdown=1\" alt=\"".htmlspecialchars($_GET['feature'])."\"/></p>");
}

$recordset->free();

$db->disconnect();
?>
</body></html>

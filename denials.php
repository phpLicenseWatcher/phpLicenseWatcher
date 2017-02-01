<?php

require_once("common.php");
print_header("License Denials");
?>
</head><body>

<h1>License Denials</h1>
<p class="a_centre"><a href="admin.php"><img src="back.jpg" alt="up page"/></a></p>
 
<form>
<p>Sort by
<select onChange='this.form.submit();' name="sortby">
	<option value="date">Date</option>
	<option value="feature">Feature</option>
	<option value="numdenials">Number of denials</option>
</select>
<input type="submit" value="Submit"/></p>
</form>
    
<?php

##############################################################
# We are using PHP Pear stuff ie. pear.php.net
##############################################################
require_once ("HTML/Table.php");
require_once 'DB.php';
    
$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\" ";

# Create a new table object
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1,"align=\"right\"");

#  Define a table header
$headerStyle = "style=\"background: yellow;\"";
$colHeaders = array("Date", "Feature", "Total number of denials");
$table->addRow($colHeaders, $headerStyle, "TH");

  
################################################################
# First let's get license usage for the product specified in $feature
##############################################################


################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
	die ($db->getMessage());
}

################################################################
# Get a list of features that have been denied :-)
################################################################
$sql = "SELECT DISTINCT flmevent_feature FROM flexlm_events WHERE flmevent_type='DENIED'";

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
	die ($recordset->getMessage());
}


################################################################
# I would like to color code my features so it is easier for me to group them :-)
# Get a list of different colors
################################################################
$color = explode(",", $colors);

$i = 0;

while ($row = $recordset->fetchRow()) {
	$features_color["$row[0]"] = $color[$i];
	$i++;
}


################################################################
# Check what we want to sort data on
################################################################
if ( $_GET['sortby'] == "feature"){
	$sql = "SELECT `flmevent_date`,`flmevent_feature`,count(*) FROM `flexlm_events` WHERE `flmevent_type`='DENIED' GROUP BY `flmevent_feature`,`flmevent_date` ORDER BY `flmevent_feature`,`flmevent_date` DESC;";
}else if ( $_GET['sortby'] == "numdenials" ){
	$sql = "SELECT `flmevent_date`,`flmevent_feature`,count(*) AS `numdenials` FROM `flexlm_events` WHERE `flmevent_type`='DENIED'  GROUP BY `flmevent_date`,`flmevent_feature` ORDER BY `numdenials` DESC;";    
}else{
	$sql = "SELECT `flmevent_date`,`flmevent_feature`,count(*) FROM `flexlm_events` WHERE `flmevent_type`='DENIED' GROUP BY `flmevent_date`,`flmevent_feature` ORDER BY `flmevent_date` DESC,`flmevent_feature`;";
}

if ( isset($debug) && $debug == 1 )
              	print_sql ($sql);

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
	die ($recordset->getMessage());
}

while ($row = $recordset->fetchRow()) {
	$table->AddRow($row, "style=\"background: " . $features_color[$row[1]].";\"");
}

$recordset->free();    

$db->disconnect();

################################################################
# Right align the 3 column
################################################################
$table->updateColAttributes(2,"align=\"right\""); 

$table->display();

include('./version.php');

?>
</body></html>

<?php

require_once("common.php");
print_header("License Checkouts");

?>
</head><body>

<h1>License Checkouts</h1>
<p class="a_centre"><a href="admin.php"><img src="back.jpg" alt="up page"/></a></p>
   
<form>
<p>Sort by
<select onChange='this.form.submit();' name="sortby">
	<option value="date">Date</option>
	<option value="user">User</option>
	<option value="feature">Feature</option>
</select>
</p>
</form>
    
<?php

##############################################################
# We are using PHP Pear stuff ie. pear.php.net
##############################################################
require_once ("HTML/Table.php");
require_once 'DB.php';
    
$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\"";

# Create a new table object
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1,"align=\"right\"");

#  Define a table header
$headerStyle = "style=\"background: yellow;\"";
$colHeaders = array("Date", "User", "Feature", "Total number of checkouts");
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

$sql = "SELECT DISTINCT `flmevent_feature` FROM `flexlm_events` WHERE `flmevent_type`='OUT'";

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
	die ($recordset->getMessage());
}


################################################################
# I would like to color code my features so it is easier for me to group them :$
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
if ( $_GET['sortby'] == "date"){
	$sql = "SELECT `flmevent_date`,`flmevent_user`,MAX(`flmevent_feature`),count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_date`,`flmevent_user` ORDER BY `flmevent_date`,`flmevent_user`,`flmevent_feature` DESC;";
}else if ( $_GET['sortby'] == "user" ){
	$sql = "SELECT `flmevent_date`,`flmevent_user`,MAX(`flmevent_feature`),count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_user`,`flmevent_date` ORDER BY `flmevent_user`,`flmevent_date`,`flmevent_feature` DESC;";
}else{
	$sql = "SELECT `flmevent_date`,MAX(flmevent_user),`flmevent_feature`,count(*) FROM `flexlm_events` WHERE `flmevent_type`='OUT' GROUP BY `flmevent_feature`,`flmevent_date` ORDER BY `flmevent_feature`,`flmevent_date`,`flmevent_user` DESC;";
}

if ( isset($debug) && $debug == 1 )
              	print_sql ($sql);

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
    die ($recordset->getMessage());
}

while ($row = $recordset->fetchRow()) {
    $table->AddRow($row, "style=\"background: " . $features_color[$row[2]]. ";\"");
}


$table->updateColAttributes(3,"align=\"center\"");

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

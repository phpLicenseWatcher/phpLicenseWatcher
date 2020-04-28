<?php

require_once(__DIR__."/common.php");
print_header("License Checkouts");

?>


<h1>License Checkouts</h1>


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

$sql = "SELECT DISTINCT `feature` FROM `events` WHERE `type`='OUT'";

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
    $sql = <<<SQL
SELECT `events`.`time`, `events`.`user`, `features`.`name`, `events`.count(*)
FROM `events`
JOIN `licenses` ON `events`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `events`.`type`='OUT'

SQL;

switch ($_GET['sortby']) {
case "date":
	$sql .= "GROUP BY `events`.`time`, `events`.`user` ORDER BY `events`.`time`, `events`.`user`, `features`.`name` DESC;";
    break;
case "user":
	$sql .= "GROUP BY `events`.`user`, `events`.`time` ORDER BY `events`.`user`, `events`.`time`, `features`.`name` DESC;";
    break;
default:
	$sql .= "GROUP BY `features`.`name`, `events`.`time` ORDER BY `features`.`name`, `events`.`time`, `events`.`user` DESC;";
}

if ( isset($debug) && $debug == 1 )
    print_sql($sql);

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


?>
<?php print_footer(); ?>

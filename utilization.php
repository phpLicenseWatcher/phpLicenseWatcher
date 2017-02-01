<?php

require_once("common.php");
print_header("License Usage Graphs");

?>
</head><body>
<h1>License Usage</h1>
<p class="a_centre"><a href="admin.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>
<p>The following links will show the license usage for different tools. Data is being collected every <?php echo($collection_interval); ?> minutes.</p>
<p>Features (click on link to show past usage):</p>

<ul>
<?php

#############################################################
# Print out the list of tools we are showing statistics
#############################################################
for ( $i = 0 ; $i < sizeof($license_feature); $i++ ) {
	echo ('<li><a href="feature_graphs.php?feature=' . $license_feature[$i] . '">' . $feature_description[$i] . '</a></li>');
}

?>
</ul>

<h2>Usage since midnight today</h2>
<?php

##########################################################
# Get today's date
##########################################################
$mydate = date("Y-m-d", mktime (0,0,0,date("m"),date("d"),  date("Y")));

print("<p>");
for ( $i = 0 ; $i < sizeof($license_feature); $i++ ) {
	print("<img src=\"generate_graph.php?feature=".$license_feature[$i]."&amp;smallsize=1&amp;mydate=".$mydate."\" alt=\"$license_feature\"/>");
}
print("</p>");

?>

<p>How much time has specified number of licenses been used</p>

<?php

print("<p>");
for ( $i = 0 ; $i < sizeof($license_feature); $i++ ) {
	print("<img src=\"generate_graph.php?feature=".$license_feature[$i]."&amp;smallsize=1&amp;time_breakdown=1&amp;mydate=".$mydate."\" alt=\"$license_feature\"/>");
}
print("</p>");

?>

<?php

include('./version.php');

?>

</body></html>

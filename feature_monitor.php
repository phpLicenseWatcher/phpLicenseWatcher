<?php

require_once("./common.php");
print_header("Per Feature License Monitoring");
?>
</head><body>

<h1>Per Feature License Monitoring</h1>
<p class="a_centre"><a href="monitor.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>
<p class="a_centre">Data is taken every <?php echo($collection_interval); ?> minutes. It shows usage for past day, past week, past month and past year.</p>

<?php

/* ---------------------------------------------------------------------- */
include_once('./tools.php');

$today = mktime (0,0,0,date("m"),date("d"),  date("Y"));

$periods = array("day","week","month","year");

for ( $i = 0 ; $i < sizeof($periods) ; $i++ ) {
	echo('<h2 class="a_centre" style="padding-top: 20pt;"> ' . ucfirst($periods[$i]) . '</h2><p class="a_centre" style="padding-top: 20pt; padding-bottom: 10pt;"><img src="generate_monitor.php?feature=' . htmlspecialchars($_GET['feature']) . '&amp;mydate=' . date("Y-m-d", $today) . '&amp;period=' . $periods[$i] );

	if ( isset($_GET['upper_limit']) ){
		echo('&amp;upper_limit=' .  htmlspecialchars($_GET['upper_limit']) . '">');
	}else{
		echo("\" alt=\"".htmlspecialchars($_GET['feature'])."\"/>");
	}
	echo("</p>");
}

?>
</body></html>

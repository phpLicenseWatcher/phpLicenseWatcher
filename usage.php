<?php

require_once("common.php");
print_header("License Monitor Graphs");
?>

<h1>License monitoring</h1>

<hr/>
<p>Following links will show the license usage for different tools. Data is being collected every <?php echo($collection_interval); ?> minutes.</p>
<p>Features (click on link to show past usage):</p>

<ul>
<?php


require_once("DB.php");
$db = DB::connect($dsn, true);

if (DB::isError($db)) {
  die ($db->getMessage());
}



    $sql = "SELECT feature, label FROM feature WHERE showInLists = 1 ORDER BY feature";

    $recordset = $db->query($sql);

    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    while ($row = $recordset->fetchRow()){

       $label = $row[1];
       if( $label == "" ){
           $label =$row[0];
       }

       echo ('<li><a href="monitor_detail.php?feature=' . $row[0] . '">' . $label . '</a></li>');
    }


    $recordset->free();

$db->disconnect();

    echo ('<li><a href="monitor_detail.php?feature=">All listed above</a></li>');
        echo ('<li><a href="monitor_detail.php?feature=all">Every single feature (slow)</a></li>');
?>
</ul>


<?php print_footer(); ?>

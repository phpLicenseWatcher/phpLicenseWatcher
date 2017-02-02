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

    

    $sql = "SELECT feature FROM feature WHERE showInLists = 1 ORDER BY feature";

    $recordset = $db->query($sql);
  
    if (DB::isError($recordset)) {
        die ($recordset->getMessage());
    }

    while ($row = $recordset->fetchRow()){
 
       echo ('<li><a href="monitor_detail.php?feature=' . $row[0] . '">' . $row[0] . '</a></li>');
    }

  
    $recordset->free();

$db->disconnect();
?>
</ul>


<?php echo footer(); ?>

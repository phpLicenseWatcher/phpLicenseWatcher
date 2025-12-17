<?php


function suggest_common_features() {
    db_connect($db);
    $sql = <<<SQL
    SELECT servers.name as `server_name`, servers.id, features.name , max( num_users ) as `max` FROM `usage` 
        LEFT JOIN licenses ON `usage`.license_id = licenses.id 
        LEFT JOIN servers ON licenses.server_id = servers.id
        LEFT JOIN features ON licenses.feature_id = features.id
    WHERE servers.id = ?
    GROUP BY servers.name, servers.id, features.name  ORDER BY servers.name, features.name;
    SQL;
    
    $num_users =  filter_input(INPUT_GET, 'min_users', FILTER_SANITIZE_NUMBER_INT); 
    $server_id = filter_input(INPUT_GET, 'server', FILTER_SANITIZE_NUMBER_INT); 

    $stmt = $db->prepare($sql); 
    $stmt->bind_param("i", $server_id ); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    
    // Start a new html_table
    $table = new html_table(array('class'=>"table alt-rows-bgcolor"));
    $config_string = "";
    
    // Define the table header
    $col_headers = array("Server", "Feature", "Max Used");
    $table->add_row($col_headers, array(), "th");

    
    while ($row = $result->fetch_assoc()) {
        if( $row['max'] >= $num_users  ){
            $table->add_row(array(
                $row['server_name'],
                $row['name'],
                $row['max']
            ));
            
            $config_string .= "\t\"".$row['name']."\" => [ \"show\" => true, \"track\"=>true ]," . PHP_EOL ;
            
        }else{
            $config_string .= "\t\"".$row['name']."\" => [ \"show\" => false, \"track\"=>true ]," . PHP_EOL ;
        }
        
        
    }

    $db->commit();
    $stmt->close();
    $db->close();
    

    print <<< HTML
    <h1>License features in use</h1>
    <hr />
    <p>Lists features that have ever been used and the max concurrent usage.  Displays in table, and formats for inclusion in community list of features.</p>
    
    {$table->get_html()}
    <pre>""=>[
    {$config_string}],</pre>
    HTML;


    
}
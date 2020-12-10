<?php
require_once __DIR__ . "/common.php";

$feature = preg_replace("/[^a-zA-Z0-9_|]+/", "", htmlspecialchars($_GET['feature'])) ;
$label = $feature;

db_connect($db);

$sql = <<<SQL
SELECT `name`, `label`
FROM `features`
WHERE `show_in_lists`=1 AND `name`='{$feature}'
SQL;

$result = $db->query($sql);
if (!$result) {
    die ($db->error);
}

// $row[0] = `name`, $row[1] = `label`
while ($row = $result->fetch_row()){
    $label = $row[1];
    if (empty($label)) {
        //`label` is NULL, so use `name`, instead.
        $label = $row[0];
    }
}

$result->free();
$db->close();
$label = str_replace('|', ' or ', $label);

// Print View.
print_header();
print <<<HTML
<h1>{$label} Usage</h1>

<hr/>
<p class="a_centre">Data is taken every {$collection_interval} minutes. It shows usage for past day, past week, past month and past year.</p>

<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript">
    // Load the Visualization API and the piechart package.
    google.charts.load('current', {'packages':['corechart']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(draw_charts);

    function draw_charts() {
        var charts = {
            "1"  : "day",
            "7"  : "week",
            "30" : "month",
            "365": "year"
        };

        $.each(charts, function(key, value) {
            var data_url = "graph_data.php?feature={$feature}&days=" + key;
            var data_div = "chart_div_" + value;
            var json_data = $.ajax({
                url: data_url,
                dataType: "json",
            }).responseText;

            $.ajax(data_url).done (
                function(json_data) {
                    // Create our data table out of JSON data loaded from server.
                    var data = new google.visualization.DataTable(json_data);

                    // Instantiate and draw our chart, passing in some options.
                    var chart = new google.visualization.LineChart(document.getElementById(data_div));
                    chart.draw(data, {width: 1000, height: 400});
                }
            );
        });
    }
</script>

<h2>Today</h2>
<div id="chart_div_day"></div>
<h2>Past Week</h2>
<div id="chart_div_week"></div>
<h2>Past Month</h2>
<div id="chart_div_month"></div>
<h2>Past Year</h2>
<div id="chart_div_year"></div>
HTML;

print_footer();
?>

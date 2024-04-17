<?php
require_once __DIR__ . "/common.php";

// URL arg check -- only numeric chars are allowed in $_GET['license']
// Halt immediately if arg check fails.
$license_id = htmlspecialchars($_GET['license'] ?? "");
if (!ctype_digit($license_id)) exit;

// DB lookup for license ID and its associated label
$sql = <<<SQL
SELECT `features`.`name`, `features`.`label`, `servers`.`name`, `servers`.`label`
FROM `licenses`
JOIN `features` ON `licenses`.`feature_id` = `features`.`id`
JOIN `servers` ON `licenses`.`server_id` = `servers`.`id`
WHERE `licenses`.`id` = ?
SQL;

db_connect($db);
$query = $db->prepare($sql);
$query->bind_param("i", $license_id);
$query->execute();
$query->bind_result($feature_name, $feature_label, $server_name, $server_label);
$query->fetch();
$query->close();
$db->close();
$feature = $feature_label ?? $feature_name;


// Print view
$html_body = <<<HTML
<h1>Usage Charts</h1>
<p class="large-text"><span class="bold-text">Feature:</span> {$feature}<br>
<span class="bold-text">Server:</span> {$server_name} ({$server_label})

<hr/>
<p class="a_centre">Data is taken every {$collection_interval} minutes. It shows usage for past day, past week, past month and past year. See the <a href="overview_detail.php?license={$license_id}&days=365">heat map</a> for an hourly overview.

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
            var data_url = "graph_data.php?license={$license_id}&days=" + key;
            var data_div = "chart_div_" + value;
            var json_data = $.ajax({
                url: data_url,
                dataType: "json",
            }).responseText;

            $.ajax(data_url).done (
                function(json_data) {
                    console.log(json_data);
                    // Create our data table out of JSON data loaded from server.
                    var data = new google.visualization.DataTable(json_data);

                    // Instantiate and draw our chart, passing in some options.
                    var chart = new google.visualization.LineChart(document.getElementById(data_div));
                    chart.draw(data, {legend: { position: 'none' }, width: 1000, height: 400});
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

print_header();
print $html_body;
print_footer();
?>

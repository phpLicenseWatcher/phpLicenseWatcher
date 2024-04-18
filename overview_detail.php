<?php
// Strongly inspired by https://developers.google.com/chart/interactive/docs/gallery/vegachart_examples?hl=ja
// select your own colour scheme: https://vega.github.io/vega/docs/schemes/
require_once __DIR__ . "/common.php";

// URL arg check.  Halt immediately if either $_GET['license'] or $_GET['days'] is not all numeric.
$license_id = htmlspecialchars($_GET['license'] ?? "");
$days = htmlspecialchars($_GET['days'] ?? "");
if (!ctype_digit($license_id) || !ctype_digit($days)) exit;

$NEW_LINE="\\n";
$width = 1000;
$width_col = ceil($width / $days);
$width = $width_col * $days;
$offset_scale = max(30, ceil($width_col * 2));

$license = db_get_license_params($license_id);
$feature = $license['feature_label'] ?? $license['feature_name'];
$server_name = $license['server_name'];
if ($license['server_label'] != "") $server_label = "({$license['server_label']})";

$sql = <<<SQL
SELECT `time`, MAX(`num_users`), date_format(`time`, '%Y%m%d%H') as hourly
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `servers` ON `licenses`.`server_id`=`servers`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `licenses`.`id` = ? AND DATE_SUB(NOW(), INTERVAL ? DAY) <= DATE(`time`)
GROUP BY `hourly`, `time`
ORDER BY `time` ASC
SQL;

// Query usage data and build CSV for heatmap.
db_connect($db);
$query = $db->prepare($sql);
$query->bind_param("ii", $license_id, $days);
$query->execute();
$query->bind_result($time, $users, $hourly);

$csv_results = "date,users" . $NEW_LINE;
while ($query->fetch()) {
    $date = date('Y-m-d\TH:00:00', strtotime($time));
    $csv_results .= "{$date},{$users}" . $NEW_LINE;
}

$query->close();
$db->close();
// END DB transactions.

// View
$title_span = $days != 365 ? "{$days}-Days" : "Yearly";

$html_body = <<<HTML
<h1>${title_span} Usage Heatmap</h1>
<p class="large-text"><span class="bold-text">Feature:</span> {$feature}<br>
<span class="bold-text">Server:</span> {$server_name} {$server_label}

<hr/>

<p class="a_centre">Heatmap usage for selected license. Set the
<em>days</em> parameter to control how far in the past to
look.

<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript">
    google.charts.load('upcoming', {'packages': ['vegachart']})
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        const dataTable = new google.visualization.DataTable();

        const options = {
            'vega': {
                "\$schema": "https://vega.github.io/schema/vega/v5.json",
                "width": {$width},
                "height": 1000,
                "padding": 5,

                "title": {
                    "text": "License Usage: {$feature}",
                    "anchor": "middle",
                    "fontSize": 16,
                    "frame": "group",
                    "offset": 4
                },

                "data": [
                    {
                        "name": "users",
                        "values": "{$csv_results}",
                        "format": {"type": "csv", "parse": {"date": "date", "users": "number"}},
                        "transform": [
                            {
                                "type": "formula", "as": "hour",
                                "expr": "hours(datum.date)"
                            },
                            {
                                "type": "formula", "as": "day",
                                "expr": "datetime(year(datum.date), month(datum.date), date(datum.date))"
                            }
                        ]
                    }
                ],

                "scales": [
                    {
                        "name": "x",
                        "type": "time",
                        "domain": {"data": "users", "field": "day"},
                        "range": "width"
                    },
                    {
                        "name": "y",
                        "type": "band",
                        "domain": [
                        0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23
                        ],
                        "range": "height"
                    },
                    {
                        "name": "color",
                        "type": "linear",
                        "range": {"scheme": "lightgreyteal"},
                        "domain": {"data": "users", "field": "users"},
                        "reverse": false,
                        "zero": false, "nice": true
                    }
                ],

                "legends": [
                    {
                        "fill": "color",
                        "type": "gradient",
                        "title": "Users",
                        "titleFontSize": 12,
                        "titlePadding": 4,
                        "gradientLength": {"signal": "height - 16"},
                        "offset": {$offset_scale},
                    }
                ],


                "axes": [
                    {"orient": "bottom", "scale": "x", "domain": false, "title": "Month", "format": "%b"},
                    {
                        "orient": "left", "scale": "y", "domain": false, "title": "Hour",
                        "encode": {
                            "labels": {
                                "update": {
                                    "text": {
                                        "signal": "datum.value === 12 ? 'Noon' : datum.value + ':00'"
                                    }
                                }
                            }
                        }
                    }
                ],

                "marks": [
                    {
                        "type": "rect",
                        "from": {"data": "users"},
                        "encode": {
                            "enter": {
                                "x": {"scale": "x", "field": "day"},
                                "y": {"scale": "y", "field": "hour"},
                                "width": {"value": $width_col},
                                "height": {"scale": "y", "band": 1},
                                "tooltip": {
                                    "signal": "timeFormat(datum.date, '%a %d %b %H:00') + ': ' + datum.users"
                                }
                            },
                            "update": {
                                "fill": {"scale": "color", "field": "users"}
                            }
                        }
                    }
                ]
            }
        };

       const chart = new google.visualization.VegaChart(document.getElementById('chart_div_year'));
       chart.draw(dataTable, options);
    };
</script>

<h2>Past Year</h2>
<div id="chart_div_year" style="margin-bottom:100px"></div>

HTML;

print_header();
print $html_body;
print_footer();
?>

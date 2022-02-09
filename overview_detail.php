<?php
// Strongly inspired by https://developers.google.com/chart/interactive/docs/gallery/vegachart_examples?hl=ja
// select your own colour scheme: https://vega.github.io/vega/docs/schemes/
require_once __DIR__ . "/common.php";

$NEW_LINE="\\n";
$days = isset($_GET['days']) ? intval($_GET['days']) : 365;
$width = 1000;
$width_col = ceil($width / $days);
$width = $width_col * $days;
$offset_scale = max(30, ceil($width_col * 2));
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

$csv_results = "date,users" . $NEW_LINE;

$sql = "
SELECT `features`.`name`, `time`, MAX(`num_users`), date_format( `time`, '%Y%m%d%H' ) as hourly
FROM `usage`
JOIN `licenses` ON `usage`.`license_id`=`licenses`.`id`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `features`.`name` = '{$feature}' AND DATE_SUB(NOW(), INTERVAL {$days} DAY) <= DATE(`time`)
GROUP BY `features`.`name`, `hourly`, `time`
ORDER BY `time` ASC;";

$recordset = $db->query($sql, MYSQLI_STORE_RESULT);
if (!$recordset) {
    die ($db->error);
}

while ($row = $recordset->fetch_row()){
    $date = $row[1];
    $date = date('Y-m-d\TH:00:00', strtotime($date));
    $csv_results .= "{$date},{$row[2]}" . $NEW_LINE;
}


$db->close();
$label = str_replace('|', ' or ', $label);

$title_span = "{$days}-Days";
if ($days == 365) {
    $title_span = "Yearly";
}

// Print View.
print_header();
print <<<HTML
<h1>${title_span} Usage: {$label}</h1>

<hr/>

<p class="a_centre">Heatmap usage for selected license. Set the
<em>days</em> parameter to control how far in the past to
look.</p>

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
             "text": "License Usage: {$label}",
             "anchor": "middle",
             "fontSize": 16,
             "frame": "group",
             "offset": 4
           },

           "data": [{
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
           }],

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
                      "signal":
                        "timeFormat(datum.date, '%a %d %b %H:00') + ': ' + datum.users"
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

print_footer();
?>

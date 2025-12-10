<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;
use html_table;

/** @author Peter Bailie (pbailie@github) */
class view extends controller {
    protected static function show_select_license() {
        // To do: This is navigated from the admin menu.
        // Finishing this is lower priority than the report view.
        $jquery = view::select_license_jquery();
        return <<<HTML
        <div class='row'>
            <div class='col-md-12'>
                <h1>Lookup License</h1>
            </div>
        </div>
        <div class='row rpt-row'>
            <div class='col-md-6'>
                <label for='lookup_server'>Lookup Server</label>
                <input type='text' id='lookup_server'>
                <input type='hidden' id='server_id'>
            </div>
            <div class='col-md-6'>
                <label for='lookup_server'>Lookup Feature</label>
                <input type='text' id='lookup_feature' disabled>
                <input type='hidden' id='feature_id'>
            </div>
        </div>
        <div class='row rpt-row'>
            <div class='col-md-12'>
                <button type='button' class='btn btn-primary'>Get Report</button>
            </div>
        </div>\n
        HTML;
    }

    private static function select_license_jquery() {
        // To do: Create Jquery for select license view.
    }

    protected static function show_report() {
        $views = [];

        // Build data views by term
        foreach (controller::$data as $i => $term_data) {
            $table = new html_table(['class' => "table table-striped"]);
            $table->add_row(["Week", "Min", "Avg", "Max", "PV", "SD"], null, "th");
            $table->update_cell(0, 0, ['colspan' => '2'], null, null);

            if (count($term_data['stats']) > 0) {
                foreach($term_data['stats'] as $j => $stats) {
                    $table->add_row([
                        $stats['week'],
                        $stats['date'],
                        $stats['minimum'],
                        $stats['average'],
                        $stats['maximum'],
                        $stats['population_variance'],
                        $stats['standard_deviation']
                    ]);

                    // 'Week' column should be treated as a header column.
                    $table->update_cell($j+1, 0, null, null, "th");
                }
            } else {
                $table->add_row(["", "", "No data on record"]);
                $table->update_cell(1, 2, ['colspan' => '5'], null, null);
            }

            $table_html = $table->get_html();
            $label = $term_data['label'];

            $views[$i] = <<<HTML
            <div class='row'>
                <div class='col-lg-12'><h2>{$label}</h2></div>
            </div>
            <div class='row rpt-row'>
                <div class='col-lg-6' id='graph_{$label}'></div>
                <div class='col-lg-6'>
                    {$table_html}
                </div>
            </div>\n
            HTML;
        }

        $divider_html = "<div class='row'><div class='col-lg-12'><hr></div></div>\n";
        $all_views = implode($divider_html, $views);

        $subheader = new html_table(['class' => 'table table-bordered large-text']);
        $subheader->add_row(["Feature", controller::$feature]);
        $subheader->add_row(["Tracked By", controller::$server]);
        $subheader->update_cell(0, 0, null, null, "th");
        $subheader->update_cell(1, 0, null, null, "th");
        $subheader = $subheader->get_html();

        $jquery = view::graphs_jquery();

        // Header sets up a container and row with 'col-lg-12'.
        return <<<HTML
        {$jquery}
        <div class='row'>
            <div class='col-md-12'>
                <h1>Academic Reports</h1>
            </div>
        </div>
        <div class='row'>
            <div class='col-md-5'>
        {$subheader}
            </div>
        </div>
        {$all_views}
        HTML;
    }

    private static function graphs_jquery() {
        return <<<JS
        <script type='text/javascript' src='https://www.gstatic.com/charts/loader.js'></script>
        <script type='text/javascript'>
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);

            function drawChart() {
                const license_id = new URLSearchParams(window.location.search).get('license');
                // let data = google.visualization.arrayToDataTable([
                //     ['Year', 'Sales', 'Expenses'],
                //     ['2013', 1000, 400],
                //     ['2014', 1170, 460],
                //     ['2015', 660,  1120],
                //     ['2016', 1030, 540]
                // ]);

                const options = {
                    hAxis: {title: 'Week',  titleTextStyle: {color: '#000'}},
                    vAxis: {minValue: 0}
                };

                $.getJSON('ajax_fetch.php', {a: 'graphs', b: 'academic', license: license_id}, function(data) {
                    // const chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
                    // chart.draw(data, options);
                    console.log("foo");
                    console.log(options);
                    console.log(data);
                });
            }
        </script>
        JS;
    }
}

// EOF

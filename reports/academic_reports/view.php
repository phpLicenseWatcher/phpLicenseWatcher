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
            $label = trim($term_data['label']);
            $id = str_replace(" ", "_", $label);

            if (count($term_data['stats']) > 0) {
                $table = new html_table(['class' => "table table-striped"]);
                $table->add_row(["Week", "Min", "Avg", "Max", "PV", "SD"], null, "th");
                $table->update_cell(0, 0, ['colspan' => '2'], null, null);

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

                $table_html = $table->get_html();
                $data_html = <<<HTML
                <div class='row'>
                    <div class='col-md-12' id='graph_${id}'></div>
                </div>
                <div class='row rpt-row'>
                    <div class='col-md-6 col-md-offset-3'>
                        {$table_html}
                    </div>
                </div>
                HTML;
            } else {
                $data_html = <<<HTML
                <div class='row rpt-row'>
                    <div class='col-md-12 text-center'>
                        <span class='large-text'>No Data On Record</span>
                    </div>
                </div>
                HTML;
            }

            $start = date_format(date_create_immutable_from_format("Y-m-d G:i:s", $term_data['start']), "M j");
            $end = date_format(date_create_immutable_from_format("Y-m-d G:i:s", $term_data['end']), "M j");

            $views[$i] = <<<HTML
            <div class='row'>
                <div class='col-md-12'>
                    <h2>
                        {$label}<br>
                        <small>{$start} &mdash; {$end}</small>
                    </h2>
                </div>
            </div>
            {$data_html}
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
        return <<<HTML
        <script type='text/javascript' src='https://www.gstatic.com/charts/loader.js'></script>
        <script type='text/javascript'>
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(draw_charts);

            function draw_charts() {
                const license_id = new URLSearchParams(window.location.search).get('license');
                let data, graph_div, graph_data, week
                let options = {
                    hAxis: {title: 'Week',  titleTextStyle: {color: '#333'}},
                    vAxis: {minValue: 0},
                    height: 480
                };
                $.getJSON('ajax_fetch.php', {a: 'graphs', b: 'academic', license: license_id}, function(data) {
                    for (const subset of data) {
                        if (subset['stats'].length > 0) {
                            graph_div = 'graph_' + subset['label'].trim().replace(" ", "_");
                            graph_data = [['Week', 'Mininum', 'Average', 'Maximum', 'Population Variance', 'Standard Deviation']];

                            for (const rows of subset['stats']) {
                                week = '(' + rows['week'] + ') ' + rows['date'].substring(4);
                                graph_data.push([
                                    week,
                                    +rows['minimum'],
                                    +rows['average'],
                                    +rows['maximum'],
                                    +rows['population_variance'],
                                    +rows['standard_deviation']
                                ]);
                            }

                            graph_data = google.visualization.arrayToDataTable(graph_data);
                            new google.visualization.LineChart(document.getElementById(graph_div)).draw(graph_data, options);
                        }
                    }
                });
            }
        </script>
        HTML;
    }
}

// EOF

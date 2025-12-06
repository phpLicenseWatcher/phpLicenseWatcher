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
        <div class='container'>
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

            if (count($term_data['stats']) > 0) {
                foreach($term_data['stats'] as $j => $stats) {
                    $tables[$i]->add_row([
                        $stats['week'],
                        $stats['minimum'],
                        $stats['average'],
                        $stats['maximum'],
                        $stats['population_variance'],
                        $stats['standard_deviation']
                    ]);

                    // 'Week' column should be treated as a header column.
                    $table->update_cell($j, 0, null, null, "th");
                }
            } else {
                $table->add_row(["", "No data on record"]);
                $table->update_cell(0, 1, ['colspan' => '5'], null, null);
            }

            $table_html = $table->get_html();
            $label = $term_data['label'];

            $views[$i] = <<<HTML
            <div class='row rpt-row'>
                <div class='col-md-12'><h2>{$label}</h2></div>
            </div>
            <div class='row rpt-row'>
                <div class='col-md-6' id='graph_{$label}'></div>
                <div class='col-md-6'>
                    {$table_html}
                </div>
            </div>\n
            HTML;
        }

        $divider_html = "<div class='row rpt-row'><div class='col-md-12'><hr></div></div>\n";
        $all_views = implode($divider_html, $views);

        $server = controller::$server;
        $feature = controller::$feature;

        return <<<HTML
        <div class='container'>
            <div class='row rpt-row'>
                <div class='col-md-12'>
                    <h1>Reports For {$feature} Tracked By {$server}</h1>
                </div>
            <div>
            {$all_views}
        </div>\n
        HTML;
    }
}

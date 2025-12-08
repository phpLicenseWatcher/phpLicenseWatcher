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
            $table->add_row(["Week", "Date", "Min", "Avg", "Max", "PV", "SD"], null, "th");

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
                $table->add_row(["", "No data on record"]);
                $table->update_cell(0, 1, ['colspan' => '5'], null, null);
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

        $subheader = new html_table(['class' => 'table table-bordered']);
        $subheader->add_row(["Feature", controller::$feature]);
        $subheader->add_row(["Tracked By", controller::$server]);
        $subheader->update_cell(0, 0, null, null, "th");
        $subheader->update_cell(1, 0, null, null, "th");
        $subheader = $subheader->get_html();

        // Header sets up a container and row with 'col-lg-12'.
        return <<<HTML
        <h1>Academic Reports</h1></div></div>
        <div class='row'><div class='col-md-4'>
        {$subheader}
        </div></div>
        {$all_views}
        </div>
        HTML;
    }
}

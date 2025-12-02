<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;

/** @author Peter Bailie (pbailie@github) */
class view extends controller {
    protected static function show_select_license() {
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
        </div>
        HTML;
    }

    private static function select_license_jquery() {
    }
}

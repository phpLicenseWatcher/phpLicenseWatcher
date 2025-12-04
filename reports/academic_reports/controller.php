<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;
require_once __DIR__ . "/model.php";
require_once __DIR__ . "/view.php";
require_once __DIR__ . "/../utils/database.php";

/** @author Peter Bailie (pbailie@github) */
class controller {
    private static $license_id = -1;
    private static $data = [];

    public static function select_license() {
        $view = file_get_contents(__DIR__ . "/../../header.html");
        $view .= view::show_select_license();
        $view .= file_get_contents(__DIR__ . "/../../footer.html");
        print $view;
    }

    public static function get_and_show_report(int $license_id) {
        controller::$license_id = $license_id;
        controller::$data = model::get_report_data();

        $view = file_get_contents(__DIR__ . "/../../header.html");
        $view .= view::show_report();
        $view .= file_get_contents(__DIR__ . "/../../footer.html");
        print $view;
    }
}

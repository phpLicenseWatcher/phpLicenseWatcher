<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;
require_once __DIR__ . "/model.php";
require_once __DIR__ . "/view.php";
require_once __DIR__ . "/../utils/database.php";
require_once __DIR__ . "/../../html_table.php";

/** @author Peter Bailie (pbailie@github) */
class controller {
    protected static $license_id = -1;
    protected static $server = "";
    protected static $feature = "";
    protected static $data = [];

    protected const TERM_SPRING_START = "01-01";
    protected const TERM_SPRING_END = "05-31";
    protected const TERM_SUMMER_START = "06-01";
    protected const TERM_SUMMER_END = "08-31";
    protected const TERM_FALL_START = "09-01";
    protected const TERM_FALL_END = "12-31";

    public static function select_license() {
        $view = file_get_contents(__DIR__ . "/../../header.html");
        $view .= view::show_select_license();
        $view .= file_get_contents(__DIR__ . "/../../footer.html");
        print $view;
    }

    public static function get_and_show_report(int $license_id) {
        if ($license_id < 1) {
            $msg = "Academic Report recieved improper license id: " . var_export($license_id, true);
            throw new \Exception($msg);
        }

        controller::$license_id = $license_id;
        model::get_report_data();

        $view = file_get_contents(__DIR__ . "/../../header.html");
        $view .= view::show_report();
        $view .= file_get_contents(__DIR__ . "/../../footer.html");
        print $view;
    }
}

<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;
require_once __DIR__ . "/model.php";
require_once __DIR__ . "/view.php";
require_once __DIR__ . "/../utils/database.php";
use PhpLicenseWatcher\Reports\Utils\db;

/** @author Peter Bailie (pbailie@github) */
class controller {
    private static $license_id = -1;

    public static function select_license() {
        $view = file_get_contents(__DIR__ . "/../../header.html");
        $view .= view::show_select_license();
        $view .= file_get_contents(__DIR__ . "/../../footer.html");
        return $view;
    }

    public static function show_report(int $license_id) {
        controller::$license_id = $license_id;
    }



}

<?php
namespace PhpLicenseWatcher\Reports\AcademicReports;
use PhpLicenseWatcher\Reports\Utils\db;

/** @author Peter Bailie (pbailie@github) */
class model extends controller {
    protected static function get_report_data() {
        // Protoype:  Let's get data for every term between 2021 and 2025.
        // Terms are Fall: Sept 1 - Dec 31, Spring: Jan 1 - May 31, and Summer: Jun 1 - Aug 31.

        controller::$data = [];
        db::open();

        $terms = ["fall", "spring", "summer"];
        $years = [2021, 2022, 2023, 2024, 2025];
        foreach ($years as $year) {
            foreach ($terms as $term) {
                controller::$data[] = lookup_term_data($year, $term);
            }
        }

        db::close();
    }

    /** `$term` should be one of "spring", "summer", or "fall" */
    private static function lookup_term_data(string $year, string $term) {
        if (preg_match("/^20\d{2}$/", $year) !== 1) {
            error_log("Bad year: " . var_export($year, true));
            die();
        }

        switch ($term) {
        case "spring":
            $start = "{$year}-01-01 00:00:00";
            $end = "{$year}-05-31 23:59:59";
            break;

        case "fall":
            $start = "{$year}-09-01 00:00:00";
            $end = "{$year}-12-31 23:59:59";
            break;

        case "summer":
            $start = "{$year}-06-01 00:00:00";
            $end = "{$year}-08-31 23:59:59 ";
            break;

        default:
            error_log("Improper term: " . var_export($term, true));
            die();
        }

        $sql = <<<SQL
        SELECT
            MIN(num_users) AS minimum,
            ROUND(AVG(num_users), 2) AS average,
            MAX(num_users) AS maximum,
            ROUND(STD(num_users), 2) AS standard_deviation
        FROM `usage`
        WHERE `license_id` = ? AND `time` BETWEEN ? AND ?
        SQL;

        $param_map = "iss";
        $params = [$license_id, $start, $end];
        $stats = db::query($sql, $param_map, $params);
        $label = "{$term} {$year}";
        return ['label' => $label, 'stats' => $stats];
    }









}

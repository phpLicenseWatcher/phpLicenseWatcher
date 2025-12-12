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

        $terms = ["Spring", "Summer", "Fall"];
        $years = [2021, 2022, 2023, 2024, 2025];
        foreach ($years as $year) {
            foreach ($terms as $term) {
                controller::$data[] = model::lookup_term_data($year, $term);
            }
        }

        $license = model::lookup_server_and_feature();
        controller::$server = $license[0]['server'];
        controller::$feature = $license[0]['feature'];

        db::close();
    }

    /** `$term` should be one of "spring", "summer", or "fall" (case insensitive) */
    private static function lookup_term_data(string $year, string $term) {
        if (preg_match("/^20\d{2}$/", $year) !== 1) {
            $msg = "Report year fails validation: " . var_export($year, true);
            throw new \UnexpectedValueException($msg);
        }

        switch (strtolower($term)) {
        case "spring":
            $start = controller::TERM_SPRING_START;
            $end = controller::TERM_SPRING_END;
            break;

        case "fall":
            $start = controller::TERM_FALL_START;
            $end = controller::TERM_FALL_END;
            break;

        case "summer":
            $start = controller::TERM_SUMMER_START;
            $end = controller::TERM_SUMMER_END;
            break;

        default:
            $msg = "Improper term: " . var_export($term, true);
            throw new \UnexpectedValueException($msg);
        }

        // These "return values" are reference parameters.
        $start = "{$year}-{$start} 00:00:00";
        $end = "{$year}-{$end} 23:59:59";

        $sql = <<<SQL
        SELECT
            WEEK(time, 6) AS week,
            DATE_FORMAT(MIN(time), '%a %b %d') AS date,
            MIN(num_users) AS minimum,
            ROUND(AVG(num_users), 2) AS average,
            MAX(num_users) AS maximum,
            ROUND(VAR_POP(num_users), 2) AS population_variance,
            ROUND(STDDEV_POP(num_users), 2) AS standard_deviation
        FROM `usage`
        WHERE `license_id` = ?
            AND `time` BETWEEN ? AND ?
        GROUP BY week
        SQL;

        $typedefs = "iss";
        $params = [controller::$license_id, $start, $end];
        $stats = db::query($sql, $typedefs, $params);
        $label = "{$term} {$year}";
        return ['label' => $label, 'stats' => $stats, 'start' => $start, 'end' => $end];
    }

    private static function lookup_server_and_feature() {
        $sql = <<<SQL
        SELECT s.`name` AS server, f.`name` AS feature
        FROM `licenses` l
        INNER JOIN `servers` s ON l.`server_id` = s.`id`
        INNER JOIN `features` f ON l.`feature_id` = f.`id`
        WHERE l.`id` = ?
        SQL;

        $typedef = "i";
        $params = [controller::$license_id];
        return db::query($sql, $typedef, $params);
    }
}

// EOF

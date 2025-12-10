<?php
namespace PhpLicenseWatcher\Reports\Utils;

/**
 * `mysqli` database wrapper class.
 *
 * @author Peter Bailie (pbailie@github)
 */
final class db {
    static private $mysqli = null;
    static private $stmt = null;
    static private $result = null;

    public static function query(string $sql, string $params_typedef, array $params_values) {
        try {
            self::$stmt = mysqli_stmt_init(self::$mysqli);
            mysqli_stmt_prepare(self::$stmt, $sql);
            mysqli_stmt_bind_param(self::$stmt, $params_typedef, ...$params_values);
            mysqli_stmt_execute(self::$stmt);

            if (mysqli_stmt_result_metadata(self::$stmt) !== false) {
                self::$result = mysqli_stmt_get_result(self::$stmt);
                $data = mysqli_fetch_all(self::$result, MYSQLI_ASSOC);
                mysqli_free_result(self::$result);
            }

            mysqli_stmt_close(self::$stmt);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            error_log($msg);
            error_log("SQL: {$sql}");
            error_log("Params: " . var_export($params_values, true));
            die("DB query error: {$msg}");
        }

        return $data;
    }

    public static function open() {
        // From config.php
        global $db_hostname, $db_username, $db_password, $db_database;

        switch(true) {
        case is_null($db_hostname):
        case is_null($db_username):
        case is_null($db_password):
        case is_null($db_database):
            $msg = "Missing database connection parameters.  Check config.php.";
            error_log($msg);
            die($msg);
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            self::$mysqli = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
            mysqli_set_charset(self::$mysqli, "utf8mb4");
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            error_log($msg);
            die("DB Connection Error: {$msg}");
        }
    }

    public static function close() {
        mysqli_close(self::$mysqli);
    }
}

// EOF

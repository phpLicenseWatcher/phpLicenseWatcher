<?php
namespace PhpLicenseWatcher\Reports\Utils;
use mysqli;
use mysqli_stmt;
use mysqli_sql_exception;

/**
 * `mysqli` database wrapper class.
 *
 * @author Peter Bailie (pbailie@github)
 */
final class db {
    private static $db = null;
    private static $stmt = null;

    public static function query(string $sql, string $param_map, ...$params) {
        self::connect();
        $fetch = null;
        $data = [];

        try {
            self::$stmt = mysqli_prepare(self::$db, $sql);
            mysqli_stmt_bind_result(self::$stmt, $fetch);
            mysqli_stmt_bind_param(self::$stmt, $param_map, $params);
            mysqli_stmt_execute(self::$stmt);
            mysqli_stmt_store_result(self::$stmt);

            while (mysqli_stmt_fetch(self::$stmt))
                $data[] = $fetch;
        } catch (mysqli_sql_exception $e) {
            $msg = $e->getMessage();
            error_log($msg);
            error_log("SQL: {$sql}");
            error_log("Params: " . print_r($params, true));
            die("DB query error: {$msg}");
        }

        self::close();
        return $data;
    }

    private static function connect() {
        // From config.php
        global $db_hostname, $db_username, $db_password, $db_database;

        if (!(self::$db instanceof mysqli)) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$db = mysqli_connect("{$db_hostname}", $db_username, $db_password, $db_database);
                mysqli_set_charset(self::$db, "utf8mb4");
            } catch (mysqli_sql_exception $e) {
                $msg = $e->getMessage();
                error_log($msg);
                die("DB Connection Error: {$msg}");
            }
        }
    }

    private static function close() {
        if (self::$stmt instanceof mysqli_stmt) mysqli_stmt_close(self::$stmt);
        if (self::$db instanceof mysqli) mysqli_close(self::$db);
    }
}

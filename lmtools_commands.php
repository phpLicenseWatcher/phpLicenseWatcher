<?php
/* --------------------------------------------------------------------------- *
STRUCTURE
static public $command (array)
                  |---- license_manager (array)
                               |---- cli (string)
                               |---- regex (array)
                                       |---- pattern_0 (string)
                                       |---- pattern_1 (string)
                                       |---- pattern_2 (string)
                                       |---- pattern_n (string)

lmtools.php will lookup the command and license_manager to discover what
cli string and regex patterns to use to retrieve data from a licensing server.

Command naming convention is $"{$code_file}__{function called from}"
e.g. static public $tools__build_license_expiration_array
  Called from "tools.php", called from function build_license_expiration_array()

STRING CONSTANTS
%CLI_BINARY%  = path/to/license_manager_binary (in config.php)
                i.e. what is being run on the command line.
%CLI_SERVER%  = "license file" being read.  Often a server.
%CLI_FEATURE% = feature being read via license_manager_binary.
* --------------------------------------------------------------------------- */

class lmtools_cmd {
    static public $tools__build_license_expiration_array = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmcksum -c %CLI_SERVER%",
            'regex' => ["/(?:INCREMENT|FEATURE) (?<name>[^ ]+) (?<vendor_daemon>[^ ]+) [^ ]+ (?<expiration_date>[^ ]+) (?<num_licenses>[^ ]+)/i"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/tools__build_license_expiration_array.template",
            'regex' => [""]]]; // placeholder

    static public $license_cache = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => ["/^Users of (?<feature>[^ ]+):  \(Total of (?<num_licenses>\d+)/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/license_cache.template",
            'regex' => [""]]]; // placeholder

    static public $license_util__update_servers = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -c %CLI_SERVER%",
            'regex' => [
                'server_up'          => "/license server UP (?:\(MASTER\) )?(?<server_version>v\d+[\d\.]*)$/im",
                'server_vendor_down' => "/vendor daemon is down/im"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/license_util__update_servers.template",
            'regex' => [
                'server_up'          => "",  // placeholders
                'server_vendor_down' => ""]]];

    static public $license_util__update_licenses = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => ["/^Users of (?<feature>[^ ]+):  \(Total of \d+ licenses? issued;  Total of (?<licenses_used>\d+)/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/license_util__update_servers.template",
            'regex' => [""]]]; // placeholder

    static public $details__list_licenses_in_use = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -A -c %CLI_SERVER%",
            'regex' => [
                'users_counted'   => "/^Users of (?<feature>[\w\- ]+):  \(Total of (?<total_licenses>\d+) licenses? issued;  Total of (?<used_licenses>\d+)/i",
                'details'         => "/^ *(?<user>[^ ]+) (?<host>[^ ]+) .+, start \w{3} (?<date>[0-9]{1,2}\/[0-9]{1,2}) (?<time>[0-9]{1,2}:[0-9]{2})/i",
                'users_uncounted' => "/^Users of (?<feature>[\w\- ]+):  \(Uncounted/i"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/details__list_licenses_in_use.template",
            'regex' => [
                'users_counted'   => "",  // placeholders
                'details'         => "",
                'users_uncounted' => ""]]];
}

?>

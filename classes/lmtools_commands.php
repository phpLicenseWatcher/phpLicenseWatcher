<?php

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
            'cli'   => "%BINARY% lmstat -c %SERVER%",
            'regex' => [
                0             => "/license server UP (?:\(MASTER\) )?(?<server_version>v\d+[\d\.]*)$/im",
                'server_up'   => "/license server UP (?:\(MASTER\) )?(?<server_version>v\d+[\d\.]*)$/im",
                1             => "/vendor daemon is down/im",
                'server_down' => "/vendor daemon is down/im"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/license_util__update_servers.template",
            'regex' => [
                0             => "", // placeholders
                'server_up'   => "",
                1             => "",
                'server_down' => ""]]];

    static public $license_util__update_licenses = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => ["/^Users of (?<feature>[^ ]+):  \(Total of \d+ licenses? issued;  Total of (?<licenses_used>\d+)/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template mathematica/license_util__update_servers.template",
            'regex' => [""]]]; // placeholder
}

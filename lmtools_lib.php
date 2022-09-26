<?php
class lmtools_lib {
    // Add more tools here when expanding
    protected const LM_SUPPORTED = array(
        array('lm' => "flexlm",      'bin' => "lmutil_binary"),
        array('lm' => "mathematica", 'bin' => "monitorlm_binary")
    );

/* --------------------------------------------------------------------------- *
lmtools_lib command lookup structure
------------------------------------
static public $command (array)
                  |---- license_manager (array)
                               |---- 'cli' (string)
                               |---- 'regex' (array)
                                        |---- pattern_0 (string)
                                        |---- pattern_1 (string)
                                        |---- pattern_2 (string)
                                        |---- pattern_n (string)

lmtools.php will lookup the command and license_manager to discover what
cli string and regex patterns to use to retrieve data from a licensing server.

Command naming convention is $"{code_file}__{calling_function}"
e.g. static public $tools__build_license_expiration_array
  Called from "tools.php", called from function build_license_expiration_array()

STRING CONSTANTS
%CLI_BINARY%  = path/to/license_manager_binary (in config.php)
                i.e. what is being run on the command line.
%CLI_SERVER%  = "license file" being read.  Often a server.
%CLI_FEATURE% = feature being read via license_manager_binary.
* --------------------------------------------------------------------------- */

    static protected $tools__build_license_expiration_array = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmcksum -c %CLI_SERVER%",
            'regex' => ["/(?:INCREMENT|FEATURE) (?<name>[^ ]+) (?<vendor_daemon>[^ ]+) [^ ]+ (?<expiration_date>[^ ]+) (?<num_licenses>[^ ]+)/i"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -template " . __DIR__ . "/mathematica/tools__build_license_expiration_array.template",
            'regex' => ["/^(?<name>[^:]+):\s+(?<vendor_daemon>[^ ]+)\s+(?<expiration_date>[^ ]+)\s+(?<num_licenses>[^ ]+)\s*$/i"]]];

    static protected $license_cache = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => ["/^Users of (?<feature>[^ ]+):  \(Total of (?<num_licenses>\d+)/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -template " . __DIR__ . "/mathematica/license_cache.template",
            'regex' => ["/^(?<feature>[^:]+):\s+(?<num_licenses>\d+)\s*$/"]]];

    static protected $license_util__update_servers = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -c %CLI_SERVER%",
            'regex' => [
                'server_up'          => "/license server UP (?:\(MASTER\) )?(?<server_version>v\d+(?:\.\d+)+)$/im",
                'server_vendor_down' => "/vendor daemon is down/im"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -template " . __DIR__ . "/mathematica/license_util__update_servers.template",
            'regex' => [
                'server_up' => "/^(?<server_version>v\d+(?:\.\d+)+)\s*$/"]]];

    static protected $license_util__update_licenses = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => [
                'feature_and_counts' => "/^Users of (?<feature>[^ ]+):  (?:\(Total of \d+ licenses? issued;  Total of (?<licenses_used>\d+)|(?:\(Uncounted))/m",
                'reservations'       => "/^ *(?<num_reservations>\d+) RESERVATIONs?/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -template " . __DIR__ . "/mathematica/license_util__update_licenses.template",
            'regex' => [
                'feature_and_counts' => "/^(?<feature>[^:]+):\s+(?<licenses_used>\d+)\s*$/",
                'reservations'       => "/^TOKEN (?<num_reservations>TOKEN) TOKEN$/"]]];  // Doesn't really exist, but a not null and not matched placeholder is necessary.

    static protected $self__get_license_usage_array = [
        'flexlm' => [
            'cli'   => "%CLI_BINARY% lmstat -a -c %CLI_SERVER%",
            'regex' => [
                'users_counted'   => "/^Users of (?<feature>[\w\- ]+):  \(Total of (?<total_licenses>\d+) licenses? issued;  Total of (?<used_licenses>\d+)/i",
                'details'         => "/^ *(?<user>[^ ]+(?: [^ ]+)?) (?<host>[^ ]+) [^ ]+ \([^ ]+\) \([^ ]+ \d+\), start \w{3} (?<date>[0-9]{1,2}\/[0-9]{1,2}) (?<time>[0-9]{1,2}:[0-9]{2})(?:, (?<num_licenses>\d+) licenses)?/i",
                'queued'          => "/^ *(?<user>[^ ]+(?: [^ ]+)?) (?<host>[^ ]+) [^ ]+ \([^ ]+\) \([^ ]+ \d+\) queued for (?<num_queued>\d+)/i",
                'users_uncounted' => "/^Users of (?<feature>[\w\- ]+):  \(Uncounted/i",
                'reservations'    => "/ *(?<num_reservations>\d+) RESERVATIONs? for (?<reserved_for>[^ ]+ [^ ]+)/"]],
        'mathematica' => [
            'cli'   => "%CLI_BINARY% %CLI_SERVER% -localtime -template " . __DIR__ . "/mathematica/details__list_licenses_in_use.template",
            'regex' => [
                'users_counted'   => "/^COUNTS (?<feature>[^:]+):\s+(?<used_licenses>\d+)\s+(?<total_licenses>\d+)\s*$/",
                'details'         => "/^DETAILS (?<feature>[^:]+): (?<user>[^~]+)~(?<host>[^~]*)~(?<duration>\d+(?::\d+)+)\s*$/"]]];


/* --------------------------------------------------------------------------- *
lmtools_lib::$_namecheck_regex
------------------------------
This is to lookup what regex pattern to use to validate an appropriate server
name string for a license manager.  e.g. Flexlm requires a port number, while
mathematica does not.

Regex pattern "/(?:[a-z\d\-]+\.)+[a-z\-]{2,}/i" should well represent most FQDNs.

***This has not yet been implemented elsewhere in phplw.
* --------------------------------------------------------------------------- */

    /* Currently unused and needs updating to permit hostname and ipv4        */
    // static protected $_namecheck_regex = [
    //     'flexlm' => [
    //         'format'  => "port@doman.tld",
    //         'pattern' => "/^\d{1,5}@(?:[a-z\d\-]+\.)+[a-z\-]{2,}$/i"],
    //     'mathematica' => [
    //         'format'  => "domain.tld",
    //         'pattern' => "/^(?:[a-z\d\-]+\.)+[a-z\-]{2,}$/i"]];

    static protected function get_dateinterval($date = null, $time = null, $duration = null) {
        // Create DateInterval based on either $date/$time or $duration.
        // q.v. https://www.php.net/manual/en/class.dateinterval.php
        switch (true) {
        case is_null($date) && is_null($time) && !is_null($duration):
            // Expected: $duration will be 'hours:minutes:seconds'
            $duration = explode(":", $duration);
            $dti = count($duration) < 3 ?
                new DateInterval("PT{$duration[0]}M{$duration[1]}S") :
                new DateInterval("PT{$duration[0]}H{$duration[1]}M{$duration[2]}S");

            // Unlikely, but in case hours >= 24 we'll convert 24 hour blocks into days.
            // $dti->d and $dti->h are unchanged when hours < 24.
            $dti->d = intdiv($dti->h, 24);
            $dti->h = $dti->h % 24;
            return $dti;

        case !is_null($date) && !is_null($time) && is_null($duration):
            // Expected: $date will be 'month/date', $time will be 'hour:minutes'.
            $now = new DateTime("now");
            $dt = DateTime::createFromFormat("n/j G:i", "{$date} {$time}");

            // Year is not in the date string, and therefore API assumes it to
            // be "this year".  But at around Jan 1, it is possible that a
            // license was checked out last year (Dec 31 or earlier), in which
            // case the year needs to be rolled back by 1 for proper timespan
            // calculation.
            if ($dt->getTimestamp() > $now->getTimeStamp()) $dt->sub(new DateInterval("P1Y"));

            // DateInterval difference between now and license checkout.
            $dti = $dt->diff($now);
            return $dti;

        default:
            return false;
        }
    }
}

?>

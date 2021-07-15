<?php
require_once __DIR__ . "/config.php";

// Currently supported: "flexlm", "mathematica"
class lmtools {
    // Add more tools here when expanding
    private const LM_SUPPORTED = array(
        array('lm' => "flexlm",      'bin' => "lmutil_binary"),
        array('lm' => "mathematica", 'bin' => "monitorlm_binary")
    );

    private const CLI_BINARY = "%CLI_BINARY%";
    private const CLI_SERVER = "%CLI_SERVER%";
    private $lm_binaries;
    private $fp;
    private $cli;
    private $regex;
    public $err;

    public function __construct() {
        clearstatcache();
        foreach (self::LM_SUPPORTED as $supported) {
            global ${$supported['bin']}; // expected to be defined in config.php
            if (isset($supported['lm']) && isset(${$supported['bin']}) && is_executable(${$supported['bin']})) {
                $this->lm_binaries[$supported['lm']] = ${$supported['bin']};
            }
        }

        $this->fp    = null;
        $this->cli   = null;
        $this->regex = null;
        $this->err   = null;
    }

    public function __destruct() {
        $this->cli_close();
    }

    public function is_available(string $lm) {
        return isset($this->lm_binaries[$lm]);
    }

    public function list_all_available() {
        $all_lm_available = array_keys($this->lm_binaries);
        sort($all_lm_available, SORT_STRING | SORT_FLAG_CASE);
        return $all_lm_available;
    }

    public function lm_open(string $lm, string $cmd, string $server) {
        switch (false) {
        case $this->lm_check($lm):
        case $this->set_command($cmd, $lm):
            return false;
        }

        $binary = $this->lm_binaries[$lm];
        $this->cli = str_replace(self::CLI_BINARY, $binary, $this->cli);
        $this->cli = str_replace(self::CLI_SERVER, $server, $this->cli);
        $this->fp = popen($this->cli, "r");

        if ($this->fp === false) {
            $this->cli = null;
            $this->regex = null;
            $this->err = "Cannot open \"{$cli}\"";
            return false;
        }

        $this->err = null;
        return true;
    }

    public function lm_nextline(int $pattern=0) {
        switch (true) {
        case !is_resource($this->fp) || get_resource_type($this->fp) !== "stream":
            $this->err = "No license manager is open.";
            return false;
        case is_null($this->cli):
            $this->err = "LMtool object CLI not set.";
            return false;
        case is_null($this->regex) || !isset($this->regex[$pattern]):
            $this->err = "Regex pattern #{$pattern} not defined for current license manager or command.";
            return false;
        }

        $line = fgets($this->fp);
        while (true) {
            switch (true) {
            case preg_match($this->regex[$pattern], $line, $matches) === 1:
                return array_filter($matches, function($key) {return is_string($key);}, ARRAY_FILTER_USE_KEY);
            case feof($this->fp):
                $this->cli_close();
                $this->cli   = null;
                $this->regex = null;
                $this->err   = null;
                return null;
            }

            $line = fgets($this->fp);
        }
    }

    private function lm_check($lm) {
        if (!isset($this->lm_binaries[$lm])) {
            $this->err = "License Manager \"{$tool}\" not available.";
            return false;
        }

        $this->err = null;
        return true;
    }

    private function cli_close() {
        if (is_resource($this->fp) && get_resource_type($this->fp) === "stream") pclose($this->fp);
    }

    private function set_command($cmd, $lm) {
        $this->cli   = null;
        $this->regex = null;

        switch ($cmd) {
        case 'license_cache':
            switch ($lm) {
            case 'flexlm':
                $this->cli   = self::CLI_BINARY . " lmstat -a -c " . self::CLI_SERVER;
                $this->regex = array("/^Users of (?<feature>[^ ]+):  \(Total of (?<num_licenses>\d+)/");
                break;
            case 'mathematica':
                $this->cli   = self::CLI_BINARY. " " . self::CLI_SERVER . " -localtime -template mathematica/license_cache.template";
                $this->regex = array("");  // placeholder
                break;
            }
        }

        if (is_null($this->cli) || is_null($this->regex)) {
            $this->err = "Either license manager or command requested is unknown.";
            return false;
        }

        $this->err = null;
        return true;
    }
}
?>

<?php
require_once __DIR__ . "/config.php";

// Currently supported: "flexlm", "mathematica"
class lmtools {

    // Add more tools here when expanding
    private static $lmsupported = array(
        array('lmtool' => "flexlm",      'binary' => "lmutil_binary"),
        array('lmtool' => "mathematica", 'binary' => "monitorlm_binary")
    );

    private static $command = array(
        'license_cache' => array(
            'flexlm' => array(
                'cli'         => "%LMBINARY% lmstat -a -c %SERVER%",
                'regex'       => "/^Users of (.*)Total /i",
                'num_matches' => 1),
            'mathematica' => array(
                'cli'         => "%LMBINARY% %SERVER% -localtime -template mathematica/license_cache.template",
                'regex'       => null,  // placeholder
                'num_matches' => null)) // placeholder

        // 'get_all_features' => array(
        //     'flexlm'      => "%LMBINARY% lmstat -a -c %SERVER%",
        //     'mathematica' => "%LMBINARY% %SERVER% -localtime -template mathematica/get_all_features.template"),
        // 'get_single_feature' => array(
        //     'flexlm'      => "%LMBINARY% lmstat -c %SERVER%",
        //     'mathematica' => "%LMBINARY% %SERVER% -localtime -template mathematica/get_single_feature.template"),
        // 'get_checkouts' => array(
        //     'flexlm'      => "%LMBINARY% lmstat -A -c %SERVER",
        //     'mathematica' => "%LMBINARY% %SERVER% -localtime -template mathematica/get_checkouts.template");
    );

    private $lmavailable;
    private $fp;
    private $cmd;
    public $err;

    public function __construct() {
        clearstatcache();
        foreach (self::$lmsupported as $supported) {
            global ${$supported['binary']}; // expected to be defined in config.php
            if (isset($supported['lmtool']) && isset(${$supported['binary']}) && is_executable(${$supported['binary']})) {
                $this->lmavailable[$supported['lmtool']] = ${$supported['binary']};
            }
        }
        $this->cmd = null;
        $this->err = null;
        $this->fp = null;
    }

    public function __destruct() {
        $this->tool_close();
    }

    public function is_availale($tool) {
        return isset($this->lmavailable[$tool]);
    }

    public function list_all_available() {
        $all_lmavailable = array_keys($this->lmavailable);
        sort($all_lmavailable, SORT_STRING | SORT_FLAG_CASE);
        return $all_lmavailable;
    }

    // public function open_get_all_info(string $tool, string $server) {
    //     $this->tool_close();
    //     $tool = strtolower($tool);
    //     if (!tool_check($tool)) return false;
    //     $this->tool = $tool;
    //
    //     switch($tool) {
    //     case "flexlm":
    //         $this->fp = popen("{$this->lmtools[$tool]} lmstat -a -c {$server}", "r");
    //         break;
    //     case "mathematica":
    //         break;
    //     // Add more tools here when expanding
    //     }
    //
    //     $this->cmd = "get_all_info";
    //     return true;
    // }
    //
    // public function open_get_usage_info(string $tool, string $server) {
    //     $this->tool_close();
    //     $tool = strtolower($tool);
    //     if (!tool_check($tool)) return false;
    //     $this->tool = $tool;
    //
    //     switch($tool) {
    //     case "flexlm":
    //         $this->fp = popen("{$this->lmtools[$tool]} lmstat -A -c {$server}", "r");
    //         break;
    //     case "mathematica":
    //         break;
    //     // Add more tools here when expanding
    //     }
    //
    //     $this->cmd = "get_usage_info";
    //     return true;
    // }
    //
    // public function open_get_feature_info(string $tool, string $server, string $feature) {
    //     $this->tool_close();
    //     $tool = strtolower($tool);
    //     if (!tool_check($tool)) return false;
    //     $this->tool = $tool;
    //
    //     switch($this->tool) {
    //     case "flexlm":
    //         $this->fp = popen("{$this->lmtools[$this->tool]} lmstat -f {$feature} -c {$server}", "r");
    //         break;
    //     case "mathematica":
    //         break;
    //     // Add more tools here when expanding
    //     }
    //
    //     $this->cmd = "get_feature_info";
    //     return true;
    // }
    //
    // public function next_line() {
    //     switch (true) {
    //     case !is_resource($this->fp):
    //     case get_resource_type($this->fp) !== "stream":
    //         return false;
    //     case feof($this->fp):
    //         pclose($this->fp);
    //         return false;
    //     }
    //
    //     $line = fgets($this->fp);
    //     switch ($this->cmd) {
    //     case "get_all_info":
    //     }
    // }

    private function tool_check($tool) {
        if (!isset($this->lmtools[$tool])) {
            $this->err = "LMtool {$tool} not available.";
            return false;
        }

        $this->err = null;
        return true;
    }

    private function tool_close() {
        $this->tool = null;
        if (is_resource($this->fp) && get_resource_type($this->fp) === "stream") pclose($this->fp);
    }
}
?>

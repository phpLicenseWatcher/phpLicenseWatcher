<?php
require_once __DIR__ . "/config.php";

// Currently supported: "flexlm", "mathematica"

class lmtools {

    public static $lmsupported = array(
        array("flexlm", "lmutil_binary"),
        array("mathematica", "monitorlm_binary")
    );

    private $lmtools;
    private $fp;
    private $cmd;
    private $tool;

    public $err;

    public function __construct() {
        clearstatcache();
        foreach (self::$lmsupported as $supported) {
            global ${$supported[1]}; // defined in config.php
            $this->lmtools[$supported[0]] = isset(${$supported[1]}) && is_executable(${$supported[1]}) ? ${$supported[1]} : null;
        }
        $this->err = null;
        $this->fp = null;
    }

    public function __destruct() {
        $this->tool_close();
    }

    public function open_get_all_info(string $tool, string $server) {
        $this->tool_close();
        $tool = strtolower($tool);
        if (!tool_check($tool)) return false;
        $this->tool = $tool;

        switch($tool) {
        case "flexlm":
            $this->fp = popen("{$this->lmtools[$tool]} lmstat -a -c {$server}", "r");
            break;
        case "mathematica":
            break;
        // Add more tools here when expanding
        }

        $this->cmd = "get_all_info";
        return true;
    }

    public function open_get_usage_info(string $tool, string $server) {
        $this->tool_close();
        $tool = strtolower($tool);
        if (!tool_check($tool)) return false;
        $this->tool = $tool;

        switch($tool) {
        case "flexlm":
            $this->fp = popen("{$this->lmtools[$tool]} lmstat -A -c {$server}", "r");
            break;
        case "mathematica":
            break;
        // Add more tools here when expanding
        }

        $this->cmd = "get_usage_info";
        return true;
    }

    public function open_get_feature_info(string $tool, string $server, string $feature) {
        $this->tool_close();
        $tool = strtolower($tool);
        if (!tool_check($tool)) return false;
        $this->tool = $tool;

        switch($tool) {
        case "flexlm":
            $this->fp = popen("{$this->lmtools[$tool]} lmstat -f {$feature} -c {$server}", "r");
            break;
        case "mathematica":
            break;
        // Add more tools here when expanding
        }

        $this->cmd = "get_feature_info";
        return true;
    }

    public function next_line() {
        switch (true) {
        case !is_resource($this->fp):
        case get_resource_type($this->fp) !== "stream":
            return false;
        case feof($this->fp):
            pclose($this->fp);
            return false;
        }

        $line = fgets($this->fp);
        switch ($this->cmd) {
        case "get_all_info":
        }
    }


    private function tool_check($tool) {
        if (!isset($this->lmtools[$tool])) {
            $this->err = "LMtool {$tool} not supported.";
            return false;
        }

        if (is_null($this->lmtools[$tool])) {
            $this->err = "LMtool {$tool} not executable.";
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

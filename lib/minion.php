<?php

require('lib/config.base.php');
require('lib/plugin.php');

try {
    require('config.php');
} catch (Exception $e) {
    die("Please copy config.php-dist to config.php, set your configuration variables, and re-run this script.");
}

class Minion {

    public $config;
    private $log;
    private $socket;
    private $plugins = array();
    private $triggers = array();

    public function __construct () {
        $this->config = new Config();

        foreach (glob("{$this->config->PluginDirectory}/*.php") as $pluginFile) {
            // Instantiate plugin.
            $plugin = include($pluginFile);
            
            // Configure plugin.
            if (isset($this->config[$plugin->Name])) {
                $plugin->configure($this->config[$plugin->Name]);
            }
            array_push($this->plugins, $plugin);
            
            // Capture plugin's triggers locally so that we can access them quickly.
            foreach ($plugin->On as $trigger) {
                if (!is_array($triggers[$trigger])) {
                    $triggers[$trigger] = array();
                }
                $triggers[$trigger][$plugin->Name] =& $plugin->On[$trigger];
            }
        }
    }

    public function run () {
        $this->socket = new Socket($this->config->Host, $this->config->Port);
        $this->trigger('connect');

        while (true) {
            $this->trigger('loop-start');

            $data = $this->socket->read();
            $this->trigger('after-read', $data);

            if (preg_match('^(?:[:@]([^\\s]+) )?([^\\s]+)(?: ((?:[^:\\s][^\\s]* ?)*))?(?: ?:(.*))?$', $data, $matches)) {
                $parsed = array(
                    'source' => $matches[0],
                    'command' => $matches[1],
                    'target' => $matches[2],
                    'params' => $matches[3]
                );
                log("[Source: {$parsed['source']}] [Command: {$parsed['command']}] [Target: {$parsed['target']}] [Params: {$parsed['params']}]", 'INFO');
                $this->trigger('parsed', $parsed);
                $this->trigger($parsed['command'], $parsed);
            } else {
                $this->trigger('parse-failed', $data);
                log("Failed to parse data: $data");
            }

            $this->trigger('loop-end');
        }
    }

    private function trigger ($event, &$data = null) {
        if (isset($this->triggers[$event])) {
            foreach ($this->triggers[$event] as $pluginName => $trigger) {
                $trigger($this, &$data);
            }
        }
    }

    public function send ($message) {
        $this->trigger('before-respond', $message);
        $this->socket->write($message);
        $this->trigger('after-response', $message);
    }

    public function msg ($message, $target) {
        $this->send("PRIVMSG $target :$message");
    }

    public function __destruct () {
        $this->trigger('disconnect');
        $this->socket->disconnect();
        fclose($this->log);
    }

    public function log ($message, $level = 'INFO') {
        if (!is_resource($this->log)) {
            $this->log = fopen($this->config->MinionLog, 'a');
        }

        fwrite($this->log, date('Y-m-d H:i:s') . " [$level] $message\n");
    }

}

?>

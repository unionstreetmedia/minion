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

    public function __construct () {
        $this->config = new Config();

        foreach (glob("{$this->config->PluginDirectory}/*.php") as $pluginFile) {
            array_push($this->plugins, include($pluginFile));
        }
    }

    public function run () {
        $this->socket = new Socket($this->config->Host, $this->config->Port);
        $this->trigger('connect');

        while (true) {
            $this->trigger('loop-start');

            $this->trigger('before-read');
            $data = $this->socket->read();
            $this->trigger('after-read', $data);

            try {
                $parsed = $this->parse($data);
            } catch (UnexpectedValueException $e) {
                log("Failed to parse data: $data");
                continue;
            }

            $response = $this->respond($parsed);

            if (!empty($response)) {
                $this->trigger('before-write', $response);
                $this->socket->write($response);
                $this->trigger('after-write', $response);
            }

            $this->trigger('loop-end');
        }
    }

    private function parse ($data) {
        $this->trigger('before-parse', $data);
        $parsed = array();
        if (preg_match('^(?:[:@]([^\\s]+) )?([^\\s]+)(?: ((?:[^:\\s][^\\s]* ?)*))?(?: ?:(.*))?$', $data, $matches)) {
            $parsed = array(
                'source' => $matches[0],
                'command' => $matches[1],
                'target' => $matches[2],
                'params' => $matches[3]
            );
            log("[Source: {$parsed['source']}] [Command: {$parsed['command']}] [Target: {$parsed['target']}] [Params: {$parsed['params']}]", 'INFO');
            $this->trigger('parsed', $parsed);
        } else {
            $this->trigger('parse-failed', $data);
            throw new UnexpectedValueException("Failed to parse data: $data");
        }
        $this->trigger('after-parse', $parsed);
        return $parsed;
    }

    private function respond ($data) {
        $this->trigger('before-respond', $data);
        $this->trigger($data['command'], $data);
        $response = '';
        if (isset($data['response'])) {
            $response = $data['response'];
        }
        $this->trigger('after-respond', $response);
        return $response;
    }

    private function trigger ($event, &$data) {
        foreach ($this->plugins as $plugin) {
            if (isset($plugin->on[$event]) and is_callable($plugin->on[$event])) {
                $plugin->on[$event]($this, &$data);
            }
        }
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

<?php

require('lib/socket.php');
require('lib/plugin.php');

class Minion {

    public $config;
    private $log;
    private $socket;
    private $plugins = array();
    private $triggers = array();

    public function __construct (Config $config = null) {
        $this->config = is_null($config) ? new Config() : $config;

        // Load plugins based on configuration.
        foreach ($this->config->PluginConfig as $pluginName => $pluginConfig) {
            $pluginFile = $this->config->PluginDirectory . '/' . strtolower($pluginName) . '.php';
            if (file_exists($pluginFile)) {
                // Instantiate plugin.
                $plugin = include($pluginFile);
                
                // Configure plugin.
                $plugin->configure($this->config->PluginConfig[$plugin->Name]);
                array_push($this->plugins, $plugin);
                
                // Capture plugin's triggers locally so that we can access them quickly.
                foreach ($plugin->On as $event => $trigger) {
                    if (!isset($this->triggers[$event]) or !is_array($this->triggers[$event])) {
                        $this->triggers[$event] = array();
                    }
                    $this->triggers[$event][$plugin->Name] =& $plugin->On[$event];
                }
            }
        }
    }

    public function run (Socket $socket = null) {
        $this->socket = is_null($socket) ? new Socket($this->config->Host, $this->config->Port) : $socket;

        while (true) {
            $this->trigger('loop-start');
            if ($this->socket->connect()) {
                $this->trigger('connect');
            }

            $data = $this->socket->read();
            $this->trigger('after-read', $data);

            if ($data) {
                $parsed = $this->parse($data);
                if (!is_null($parsed['command'])) {
                    $this->log("[Source: {$parsed['source']}] [Command: {$parsed['command']}] [Arguments: " . implode(' ', $parsed['arguments']) . "] [Message: {$parsed['message']}]", 'INFO');
                    $this->trigger('parsed', $parsed);
                    $this->trigger($parsed['command'], $parsed);
                } else {
                    $this->trigger('parse-failed', $data);
                    $this->log("Failed to parse data: $data");
                }
            }

            $this->trigger('loop-end');
        }
    }

    private function trigger ($event, &$data = null) {
        if (isset($this->triggers[$event])) {
            foreach ($this->triggers[$event] as $pluginName => $trigger) {
                $this->log("Triggering $pluginName:$event.");
                $trigger($this, &$data);
            }
        }
    }

    public function send ($message) {
        $this->trigger('before-send', $message);
        $this->socket->write($message);
        $this->log("Sent: $message");
        $this->trigger('after-send', $message);
    }

    public function msg ($message, $target) {
        $this->send("PRIVMSG $target :$message");
    }

    public function ctcp ($message, $target) {
        $this->send("PRIVMSG $target :" . chr(1) . $message . chr(1));
    }

    private function parse ($data) {
        $source = null;
        $command = null;
        $message = null;
        $arguments = array();

        if ($data = trim($data)) {
            if (strpos($data, ' :') !== false) {
                list ($data, $message) = explode(' :', $data, 2);
            }

            $parts = explode(' ', $data);
            if ($parts[0][0] == ':') {
                $source = preg_replace('/^:/', '', array_shift($parts));
            }

            $command = array_shift($parts);
            $arguments = $parts;
        }

        return array('source' => $source, 'command' => $command, 'arguments' => $arguments, 'message' => $message);
    }

    public function updateNickname ($nickname) {
        foreach ($this->plugins as $plugin) {
            $plugin->updateNickname($nickname);
        }
    }

    public function __destruct () {
        $this->trigger('disconnect');
        $this->socket->disconnect();
        fclose($this->log);
    }

    public function log ($message, $level = 'INFO') {
        if (!is_resource($this->log)) {
            $this->log = fopen($this->config->MinionLogFile, 'a');
        }

        fwrite($this->log, date('Y-m-d H:i:s') . " [$level] $message\n");
    }

}

?>

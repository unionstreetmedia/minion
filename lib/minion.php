<?php

namespace Minion;

require('lib/socket.php');
require('lib/plugin.php');

class Minion {

    public $config;
    private $log;
    private $socket;
    private $plugins = array();
    private $triggers = array();
    public $state = array();
    private $exit = false;

    public function __construct (Config $config = null) {
        $this->config = is_null($config) ? new Config() : $config;

        // Load plugins based on configuration.
        foreach ($this->config->PluginConfig as $pluginName => $pluginConfig) {
            try {
                $this->loadPlugin($pluginName, $pluginConfig);
            } catch (RuntimeException $e) {
                $this->log($e, 'WARNING');
            }
        }
    }

    public function loadPlugin ($pluginName, Array $pluginConfig) {
        $pluginFile = $this->config->PluginDirectory . '/' . strtolower($pluginName) . '.php';
        if (file_exists($pluginFile)) {
            $this->addPlugin(include($pluginFile), $pluginConfig);
            return true;
        }

        throw new \RuntimeException("Couldn't load plugin $pluginName: file $pluginFile doesn't exist.");
    }

    public function addPlugin (Plugin $plugin, Array $pluginConfig) {
        array_push($this->plugins, $plugin);
        
        // Configure plugin.
        $plugin->configure($pluginConfig);

        // Capture plugin's triggers locally so that we can access them quickly.
        foreach ($plugin->On as $event => $trigger) {
            if (!isset($this->triggers[$event]) or !is_array($this->triggers[$event])) {
                $this->triggers[$event] = array();
            }
            $this->triggers[$event][$plugin->Name] =& $plugin->On[$event];
        }

        // Give each plugin a reference to this object.
        $plugin->Minion =& $this;

        $this->log("Added {$plugin->Name} plugin.", 'INFO');
    }

    public function run (Socket $socket = null) {
        $this->log('Started on ' . date('Y-m-d') . ' at ' . date('H:i:s'), 'ALL');
        $this->socket = is_null($socket) ? new Socket($this->config->Host, $this->config->Port) : $socket;

        $this->trigger('before-loop');
        while (!$this->exit) {
            $this->trigger('loop-start');
            if ($this->socket->connect()) {
                $this->trigger('connect');
                $this->log("Connected to {$this->config->Host}:{$this->config->Port}.", 'ALL');
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
        $this->trigger('after-loop');
    }

    private function trigger ($event, &$data = null) {
        if (isset($this->triggers[$event])) {
            foreach ($this->triggers[$event] as $pluginName => $trigger) {
                $this->log("Triggering $pluginName:$event.", 'INFO');
                $trigger($this, &$data);
            }
        }
    }

    public function send ($message) {
        $this->trigger('before-send', $message);
        $status = $this->socket->write($message);
        $this->log("Sent: $message", 'INFO');
        $this->trigger('after-send', $message);
        return $status;
    }

    public function msg ($message, $target) {
        return $this->send("PRIVMSG $target :$message");
    }

    public function ctcp ($message, $target) {
        return $this->msg("\1$message\1", $target);
    }

    public function quit ($message) {
        $this->send("QUIT $message");
        return $this->exit = true;
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

    public function __destruct () {
        $this->trigger('disconnect');
        if ($this->socket instanceof Socket) {
            $this->socket->disconnect();
        }
        $this->log('Disconnected.', 'ALL');
        if (is_resource($this->log)) {
            fclose($this->log);
        }
    }

    public function log ($message, $level = 'INFO') {
        if (!is_resource($this->log)) {
            $this->log = fopen($this->config->MinionLogFile, 'a');
        }

        $levels = array('ALL' => 0, 'ERROR' => 1, 'WARNING' => 2, 'INFO' => 3);
        $debugLevel = isset($this->config->Debug) ? $this->config->Debug : false;
        if (isset($levels[$level]) and $this->config->Debug >= $levels[$level]) {
            return fwrite($this->log, date('Y-m-d H:i:s') . " [$level] $message\n");
        }
    }

}

?>

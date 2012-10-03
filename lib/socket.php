<?php

namespace Minion;

class Socket {

    private $socket;
    private $host;
    private $port;

    public function __construct ($host = null, $port = 6667) {
        $this->host = $host;
        $this->port = $port;
        return $this;
    }

    public function __destruct () {
        $this->disconnect();
    }

    public function connect () {
        if (!$this->connected()) {
            $this->socket = fsockopen($this->host, $this->port);
            if (!$this->connected()) {
                throw new RuntimeException("Couldn't connect to {$this->host}:{$this->port}");
            }
            return $this;
        }
        return false;
    }

    private function connected () {
        return is_resource($this->socket);
    }

    public function write ($data) {
        try {
            fwrite($this->socket, $data . "\r\n");
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function read () {
        if (!$this->connected()) {
            $this->connect();
        }
        return fgets($this->socket, 512);
    }

    public function disconnect () {
        if ($this->connected()) {
            fclose($this->socket);
        }
    }

}

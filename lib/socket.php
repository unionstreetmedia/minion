<?php

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
        $this->socket = fsockopen($this->host, $this->port);
        if (!$this->connected) {
            throw new RuntimeException("Couldn't connect to {$this->host}:{$this->port}");
        }
        return $this;
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

    public function read ($data) {
        return fgets($this->socket, 512);
    }

    public function reconnect ($delay = 0) {
        if ($this->connected()) {
            $this->disconnect();
        }
        sleep($delay);
        return $this->connect();
    }

    public function disconnect () {
        if ($this->connected()) {
            fclose($this->socket);
        }
    }

}

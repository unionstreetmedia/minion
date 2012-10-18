<?php

namespace Minion;

class Plugin {

    public $Name;
    public $Description;
    public $Author;
    public $On = array();
    public $Config;
    public $Minion;

    public function __construct ($name, $description, $author) {
        $this->Name = $name;
        $this->Description = $description;
        $this->Author = $author;
    }

    public function on ($event, $callable) {
        $this->On[$event] = $callable;
        return $this;
    }

    public function configure (Array $configuration) {
        $this->Config = $configuration;
    }

    public function conf ($key, $conf = null) {
        if (is_null($conf)) {
            $conf = $this->Config;
        }

        if (!is_array($conf)) {
            return null;
        }

        if (strpos($key, '/') !== false) {
            list ($thisKey, $key) = explode('/', $key, 2);
        } else {
            list ($thisKey, $key) = array($key, false);
        }

        if (isset($conf[$thisKey])) {
            if ($key) {
                if (is_array($conf[$thisKey])) {
                    return $this->conf($key, $conf[$thisKey]);
                } else {
                    return null;
                }
            } else {
                return $conf[$thisKey];
            }
        }
    }

    public function simpleCommand ($data) {
        $words = explode(' ', $data['message']);
        $command = array_shift($words);
        
        if ($command == $this->Minion->State['Nickname'] . ':') {
            $command = array_shift($words);
        }

        if ($command[0] == '!') {
            $command = ltrim($command, '!');
            return array($command, $words);
        }
        return false;
    }

    public function matchCommand ($data, $regexp) {
        if (preg_match($regexp, $data['message'], $matches)) {
            return $matches;
        }
        return false;
    }
}

?>

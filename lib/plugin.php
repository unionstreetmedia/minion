<?php

class Plugin {

    public $Name;
    public $Description;
    public $Author;
    public $On;
    public $Config;

    public function __construct ($name, $description, $author, $on) {
        $this->Name = $name;
        $this->Description = $description;
        $this->Author = $author;
        $this->On = $on;
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

}

?>

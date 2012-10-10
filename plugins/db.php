<?php

namespace Minion\Plugins;

class DBPlugin extends \Minion\Plugin {

    private $Initialized;
    private $DB = false;

    private function init () {
        if (!$this->DB) {
            $this->DB = new \PDO(
                $this->conf('DSN'),
                $this->conf('Username'),
                $this->conf('Password'),
                array(
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                )
            );
        }
    }

    public function &getDB () {
        $this->init();
        return $this->DB;
    }

}

$DB = new DBPlugin(
    'DB',
    'Database connection manager.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $DB
    ->on('before-loop', function (&$minion, &$data) use ($DB) {
        $minion->state['DB'] = $DB->getDB();
        $minion->state['DBType'] = $DB->conf('DB');
    });

?>

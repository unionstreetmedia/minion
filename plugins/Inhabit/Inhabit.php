<?php

namespace Minion\Plugins;

class InhabitPlugin extends \Minion\Plugin {

    public $Inhabited = array();
    private $Initialized;

    public function init () {
        if (!$this->Initialized) {
            switch (strtolower($this->Minion->State['DBType'])) {
                case 'sqlite':
                    $sql = 'CREATE TABLE IF NOT EXISTS Inhabit (`channel` TEXT NOT NULL UNIQUE)';
                    break;
                case 'mysql':
                    $sql = 'CREATE TABLE IF NOT EXISTS Inhabit (`channel` VARCHAR(64) NOT NULL UNIQUE)';
                    break;
            }
            $this->Minion->State['DB']->query($sql);
            $this->Initialized = true;
        }
        $this->refreshInhabited();
    }

    private function refreshInhabited () {
        $statement = $this->Minion->State['DB']->query('SELECT channel FROM Inhabit');
        $results = $statement->fetchAll();
        foreach ($results as $row) {
            $this->Inhabited[$row['channel']] = true;
        }
    }

    public function add ($channel) {
        $statement = $this->Minion->State['DB']->prepare('INSERT INTO Inhabit (channel) VALUES (?)');
        try {
            $statement->execute(array($channel));
            $this->Inhabited[$channel] = true;
        } catch (\PDOException $e) {
            $minion->log("Told to inhabit $channel, which is already inhabited.", 'INFO');
        }
    }

    public function delete ($channel) {
        $statement = $this->Minion->State['DB']->prepare('DELETE FROM Inhabit WHERE channel = ?');
        $statement->execute(array($channel));
        unset($this->Inhabited[$channel]);
    }

}

$Inhabit = new InhabitPlugin(
    'Inhabit',
    'Dynamic channel auto-join.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Inhabit
    ->on('loop-start', function (&$data) use ($Inhabit) {
        $Inhabit->init();
    })
    ->on('376', function (&$data) use ($Inhabit) {
        foreach (array_keys($Inhabit->Inhabited) as $channel) {
            $Inhabit->Minion->send("JOIN $channel");
        }
    })
    ->on('PRIVMSG', function (&$data) use ($Inhabit) {
        list ($command, $arguments) = $Inhabit->simpleCommand($data);
        if (count($arguments)) {
            $channel = $arguments[0];
        } elseif (count($data['arguments'])) {
            $channel = $data['arguments'][0];
        }

        if (preg_match('/^[#&][^ \a\0\012\015,:]+/', $channel)) {
            switch ($command) {
                case 'inhabit':
                    $Inhabit->add($channel);
                    $Inhabit->Minion->send("JOIN $channel");
                    break;
                case 'evict':
                    $Inhabit->delete($channel);
                    list ($nickname, $ident) = explode('!', $data['source']);
                    $Inhabit->Minion->send("PART $channel Evicted by $nickname");
                    break;
            }
        }
    })
    ->on('KICK', function (&$data) use ($Inhabit) {
        list ($channel, $nickname) = $data['arguments'];
        if ($Inhabit->Minion->State['Nickname'] == $nickname and isset($Inhabit->Inhabited[$channel])) {
            $Inhabit->Minion->send("JOIN $channel");
        }
    });

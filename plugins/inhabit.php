<?php

namespace Minion\Plugins;

class InhabitPlugin extends \Minion\Plugin {

    public $Inhabited = array();
    private $Initialized;

    public function init () {
        if (!$this->Initialized) {
            switch (strtolower($this->Minion->state['DBType'])) {
                case 'sqlite':
                    $sql = 'CREATE TABLE IF NOT EXISTS Inhabit (`channel` TEXT NOT NULL UNIQUE)';
                    break;
                case 'mysql':
                    $sql = 'CREATE TABLE IF NOT EXISTS Inhabit (`channel` VARCHAR(64) NOT NULL UNIQUE)';
                    break;
            }
            $this->Minion->state['DB']->query($sql);
            $this->Initialized = true;
        }
        $this->refreshInhabited();
    }

    private function refreshInhabited () {
        $statement = $this->Minion->state['DB']->query('SELECT channel FROM Inhabit');
        $results = $statement->fetchAll();
        foreach ($results as $row) {
            $this->Inhabited[$row['channel']] = true;
        }
    }

    public function add ($channel) {
        $statement = $this->Minion->state['DB']->prepare('INSERT INTO Inhabit (channel) VALUES (?)');
        try {
            $statement->execute(array($channel));
            $this->Inhabited[$channel] = true;
        } catch (\PDOException $e) {
            $minion->log("Told to inhabit $channel, which is already inhabited.", 'INFO');
        }
    }

    public function delete ($channel) {
        $statement = $this->Minion->state['DB']->prepare('DELETE FROM Inhabit WHERE channel = ?');
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
    ->on('loop-start', function (&$minion, &$data) use ($Inhabit) {
        $Inhabit->init();
    })
    ->on('376', function (&$minion, &$data) use ($Inhabit) {
        foreach (array_keys($Inhabit->Inhabited) as $channel) {
            $minion->send("JOIN $channel");
        }
    })
    ->on('PRIVMSG', function (&$minion, &$data) use ($Inhabit) {
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
                    $minion->send("JOIN $channel");
                    break;
                case 'evict':
                    $Inhabit->delete($channel);
                    list ($nickname, $ident) = explode('!', $data['source']);
                    $minion->send("PART $channel Evicted by $nickname");
                    break;
            }
        }
    })
    ->on('KICK', function (&$minion, &$data) use ($Inhabit) {
        list ($channel, $nickname) = $data['arguments'];
        if ($Inhabit->Minion->state['Nickname'] == $nickname and isset($Inhabit->Inhabited[$channel])) {
            $minion->send("JOIN $channel");
        }
    });

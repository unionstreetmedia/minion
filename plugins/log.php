<?php

namespace Minion\Plugins;

/*

Example configuration (goes in Config.php):

    class Config extends ConfigBase {

        public function __construct () {
            $this->PluginConfig['Log'] = array(
                'DB' => 'SQLite',
                'DSN' => 'sqlite:./log.sq3',
                'Username' => null,
                'Password' => null
            );
        }

    }

DB can be SQLite, MySQL, or Text.
For SQLite/MySQL, DSN should be a valid PDO DSN.
For Text, DSN should just be a path to a directory in which to store log files.

*/

class LogPlugin extends \Minion\Plugin {

    private $DB;
    private $Initialized = false;

    private function init () {
        if (!$this->Initialized) {
            if (strtolower($this->conf('DB')) !== 'text') {
                $this->DB = new \PDO($this->conf('DSN'), $this->conf('Username'), $this->conf('Password'));
                $this->DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
            $this->createTable();
            $this->Initialized = true;
        }
    }

    private function createTable () {
        switch (strtolower($this->conf('DB'))) {
            case 'sqlite':
                $this->createTableSQLite();
                break;
            case 'mysql':
                $this->createTableMySQL();
                break;
            case 'text':
                $this->createDirectory();
                break;
        }
    }

    private function createTableSQLite () {
        // Ensure the SQLite DB file exists, if possible.
        list ($db, $file) = explode(':', $this->conf('DSN'), 2);
        if ($file !== ':memory:') {
            touch($file);
        }

        $sql = "CREATE TABLE IF NOT EXISTS Log (
            `ts` INTEGER NOT NULL DEFAULT (datetime('now')),
            `type` TEXT NOT NULL,
            `source` TEXT NOT NULL,
            `channel` TEXT NOT NULL,
            `message` TEXT
        )";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS source_index ON Log (source)";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS ts_index ON Log (ts)";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS channel_index ON Log (channel)";
        $this->DB->query($sql);
    }

    private function createTableMySQL () {
        $sql = "CREATE TABLE IF NOT EXISTS Log (
            `ts` TIMESTAMP NOT NULL DEFAULT NOW(),
            `type` VARCHAR(12) NOT NULL,
            `source` VARCHAR(64) NOT NULL,
            `channel` VARCHAR(64) NOT NULL,
            `message` TEXT
        )";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS source_index ON Log (source)";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS ts_index ON Log (ts)";
        $this->DB->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS channel_index ON Log (channel)";
        $this->DB->query($sql);
    }

    private function createDirectory () {
        mkdir($this->conf('DSN'), 0777, true);
    }

    public function log ($from, $channel, $type, $message) {
        $this->init();
        switch (strtolower($this->conf('DB'))) {
            case 'sqlite':
            case 'mysql':
                $this->logSQL($from, $channel, $type, $message);
                break;
            case 'text':
                $this->logText($from, $channel, $type, $message);
                break;
        }
    }

    private function logSQL ($from, $channel, $type, $message) {
        $sql = "INSERT INTO Log (type, source, channel, message) VALUES (?, ?, ?, ?)";
        $statement = $this->DB->prepare($sql);
        $statement->execute(array($type, $from, $channel, $message));
    }

    private function logText ($from, $channel, $type, $message) {
        $line = '[' . date('H:i:s') . "] <$from> $message\n";
        file_put_contents($this->conf('DSN') . "/$channel-" . date('Y-m-d') . '.txt', $line, FILE_APPEND);
    }

}

$Log = new LogPlugin(
    'Log',
    'Logs channel chatter to a database.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Log
    ->on('PRIVMSG', function (&$minion, &$data) use ($Log) {
        // Check for CTCP ACTION
        if (preg_match("/^\1.*\1$/", $data['message'])) {
            $message = trim($data['message'], "\1");
            if (strpos($message, 'ACTION') === 0) {
                $message = str_replace('ACTION ', '', $message);
                $Log->log($data['source'], $data['arguments'][0], 'ACTION', $message);
            }
        } else {
            $Log->log($data['source'], $data['arguments'][0], 'PRIVMSG', $data['message']); 
        }
    })
    ->on('JOIN', function (&$minion, &$data) use ($Log) {
        $Log->log($data['source'], $data['arguments'][0], 'JOIN', $data['message']);
    })
    ->on('PART', function (&$minion, &$data) use ($Log) {
        $Log->log($data['source'], $data['arguments'][0], 'PART', $data['message']);
    })
    ->on('QUIT', function (&$minion, &$data) use ($Log) {
        $Log->log($data['source'], 'global', 'QUIT', $data['message']);
    })
    ->on('NICK', function (&$minion, &$data) use ($Log) {
        $Log->log($data['source'], 'global', 'NICK', $data['message']);
    })
    ->on('TOPIC', function (&$minion, &$data) use ($Log) {
        $Log->log($data['source'], $data['arguments'][0], 'TOPIC', $data['message']);
    });

?>

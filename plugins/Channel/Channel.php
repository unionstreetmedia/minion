<?php

namespace Minion\Plugins;

$Channel = new \Minion\Plugin(
    'Channel',
    'Channel-related functionality.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Channel

// RPL_ENDOFMOTD
->on('376', function (&$data) use ($Channel) {
    foreach ($Channel->conf('AutoJoin') as $channel) {
        $Channel->Minion->send("JOIN $channel");
    }
})

->on('PRIVMSG', function (&$data) use ($Channel) {
    list ($command, $arguments) = $Channel->simpleCommand($data);
    switch ($command) {
        case 'join':
            if (count($arguments)) {
                $Channel->Minion->send("JOIN $arguments[0]");
            }
            break;
        case 'part':
            list($nickname, $ident) = explode('!', $data['source'], 2);
            if (count($arguments)) {
                $channel = $arguments[0];
            } else {
                $channel = $data['arguments'][0];
            }
            $Channel->Minion->send("PART $channel Dismissed by $nickname.");
            break;
    }
});

?>

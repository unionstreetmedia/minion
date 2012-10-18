<?php

namespace Minion\Plugins;

$Core = new \Minion\Plugin(
    'Core',
    'Minion core functionality.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Core

->on('connect', function (&$data) use ($Core) {
    if ($Core->conf('Password')) {
        $Core->Minion->send("PASS {$Core->conf('Password')}");
    }
    $Core->Minion->send("USER {$Core->conf('Nick')} hostname {$Core->conf('RealName')}");
    $Core->Minion->Send("NICK {$Core->conf('Nick')}");
    $Core->Minion->state['Nickname'] = $Core->conf('Nick');
})

->on('PING', function (&$data) {
    $Core->Minion->send("PONG {$data['message']}");
})

// ERR_NICKNAMEINUSE
->on('433', function (&$data) use ($Core) {
    $nick = $Core->conf('Nick') . (string) rand(1,999);
    $Core->Minion->send("NICK {$nick}");
})

->on('PRIVMSG', function (&$data) use ($Core) {
    list ($command, $arguments) = $Core->simpleCommand($data);
    if ($command == 'reload') {
        $Core->Minion->quit('Reloading.');
        $Core->Minion->__destruct();
        pcntl_exec('./minion');
        exit();
    }
});

?>

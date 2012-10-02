<?php

$Core = new Plugin(
    'Core',
    'Minion core functionality.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Core

->on('connect', function (&$minion, &$data) use ($Core) {
    if ($Core->conf('Password')) {
        $minion->send("PASS {$Core->conf('Password')}");
    }
    $minion->send("USER {$Core->conf('Nick')} hostname {$Core->conf('RealName')}");
    $minion->Send("NICK {$Core->conf('Nick')}");
    $minion->updateNickname($Core->conf('Nick'));
})

->on('PING', function (&$minion, &$data) {
    $minion->send("PONG {$data['message']}");
})

// ERR_NICKNAMEINUSE
->on('433', function (&$minion, &$data) {
    $nick = $Core->conf('Nick') . (string) rand(1,999);
    $minion->send("NICK {$nick}");
});

?>

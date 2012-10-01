<?php

return new Plugin(
    'Core',
    'Minion core functionality.',
    'Ryan N. Freebern / ryan@freebern.org',

    array(
        'connect' => function (&$minion, &$data) {
            if ($this->conf('Password')) {
                $minion->send("PASS {$this->conf('Password')}");
            }
            $minion->send("USER {$this->conf('Nick')} hostname {$this->conf('RealName')}");
            $minion->Send("NICK {$this->conf('Nick')}");
        },
        'PING' => function (&$minion, &$data) {
            $minion->send("PONG {$data['target']}");
        }
    )
);

?>

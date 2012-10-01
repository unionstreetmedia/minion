<?php

$Channel = new Plugin(
    'Channel',
    'Channel-related functionality.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Channel

// RPL_ENDOFMOTD
->on('376', function (&$minion, &$data) use ($Channel) {
    foreach ($Channel->conf('AutoJoin') as $channel) {
        $minion->send("JOIN $channel");
    }
})

->on('PRIVMSG', function (&$minion, &$data) {
    if (strpos($data['message'], '!join') === 0) {
        list ($command, $channel) = explode(' ', $data['message']);
        $minion->send("JOIN $channel");
    } elseif (strpos($data['message'], '!part') === 0) {
        $minion->send("PART {$data['arguments'][0]} Dismissed by {$data['source']}");
    }
});

?>

<?php

$stubPlugin = new Minion\Plugin('stubPlugin', 'B', 'C');

return $stubPlugin
    ->on('a', function (&$minion, &$data) { return 'a'; })
    ->on('b', function (&$minion, &$data) { return 'b'; });

?>

<?php

$stubPlugin = new Minion\Plugin('stubPlugin', 'B', 'C');

return $stubPlugin
    ->on('a', function (&$data) { return 'a'; })
    ->on('b', function (&$data) { return 'b'; });

?>

<?php

return new Plugin(
    array(
        'connect' => function (&$minion, &$data) {
            if (isset($minion->config->PluginConfig['Core']['Password']) and !empty($minion->config->PluginConfig['Core']['Password'])) {
                $data['response'] = "PASS {$minion->config->PluginConfig['Core']['Password']}\r\n";
            }
            $data['response'] .= "USER {$minion->config->PluginConfig['Core']['Nick']} hostname {$minion->config->PluginConfig['Core']['RealName']}\r\n";
            $data['response'] .= "NICK {$minion->config->PluginConfig['Core']['Nick']}";
        },
        'PING' => function (&$minion, &$data) {
            $data['response'] = 'PONG';
        }
    )
);

?>

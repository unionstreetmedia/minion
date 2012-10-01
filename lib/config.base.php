<?php

class ConfigBase {

    // Network
    public $Host = 'chat.freenode.net';
    public $Port = 6667;

    // Maintenance
    public $BotLogFile = './minion.log';
    public $Debug = false;

    // Plugins
    public $PluginDirectory = './plugins';
    public $PluginConfig = array(
        'Core' => array(
            // Identity
            'RealName' => 'Minion Bot',
            'Nick' => 'Minion',
            
            // Connection
            'Password' => '',

            // Channels
            'AutoJoin' => array('#Minion')
        )
    );

}

?>

<?php

namespace Minion;

require('lib/minion.php');
require('lib/config.base.php');

class Config extends ConfigBase { }

class ChannelTest extends \PHPUnit_Framework_TestCase {

    private $Channel;

    public function setUp () {
        $this->Channel = include('plugins/Channel/Channel.php');
        $this->Channel->Minion = $this->getMock('Minion\Minion');
        $this->Channel->configure(array(
            'AutoJoin' => array('#niña', '#pinta', '#santa_maria')
        ));
        $this->Channel->Minion->State['Nickname'] = 'Christopher';
    }

    public function testLoad () {
        $this->assertEquals('Channel', $this->Channel->Name);
    }

    public function testOn376 () {
        $this->Channel->Minion->expects($this->at(0))
            ->method('send')
            ->with('JOIN #niña');
        $this->Channel->Minion->expects($this->at(1))
            ->method('send')
            ->with('JOIN #pinta');
        $this->Channel->Minion->expects($this->at(2))
            ->method('send')
            ->with('JOIN #santa_maria');

        $data = false;
        $this->Channel->On['376']($data);
    }

    public function testOnJoin () {
        $this->Channel->Minion->expects($this->once())
            ->method('send')
            ->with('JOIN #new_world');
        
        $data = array('message' => '!join #new_world', 'source' => 'Columbus!cc@explorers.pt');
        $this->Channel->On['PRIVMSG']($data);
    }

    public function testOnPartCurrentChannel () {
        $this->Channel->Minion->expects($this->once())
            ->method('send')
            ->with('PART #portugal Dismissed by Columbus.');
        
        $data = array('message' => 'Christopher: !part', 'source' => 'Columbus!cc@explorers.pt', 'arguments' => array('#portugal'));
        $this->Channel->On['PRIVMSG']($data);
    }

    public function testOnPartOtherChannel () {
        $this->Channel->Minion->expects($this->once())
            ->method('send')
            ->with('PART #atlantic Dismissed by Columbus.');
        
        $data = array('message' => '!part #atlantic', 'source' => 'Columbus!cc@explorers.pt', 'arguments' => array('#new_world'));
        $this->Channel->On['PRIVMSG']($data);
    }

}

?>

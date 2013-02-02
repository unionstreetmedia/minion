<?php

namespace Minion;

require('lib/minion.php');
require('lib/config.base.php');

class Config extends ConfigBase { }

class CoreTest extends \PHPUnit_Framework_TestCase {

    private $Core;

    public function setUp () {
        $this->Core = include('plugins/Core/Core.php');
        $this->Core->Minion = $this->getMock('Minion\Minion');
        $this->Core->configure(array(
            'Password' => 'swordfish',
            'Nick' => 'Groucho',
            'RealName' => 'Groucho Marx'
        ));
    }

    public function testLoad () {
        $this->assertEquals('Core', $this->Core->Name);
    }

    public function testOnConnect () {
        $this->Core->Minion->expects($this->at(0))
            ->method('send')
            ->with("PASS {$this->Core->conf('Password')}");
        $this->Core->Minion->expects($this->at(1))
            ->method('send')
            ->with("USER {$this->Core->conf('Nick')} hostname {$this->Core->conf('RealName')}");
        $this->Core->Minion->expects($this->at(2))
            ->method('send')
            ->with("NICK {$this->Core->Conf('Nick')}");

        $data = false;
        $this->Core->On['connect']($data);
        $this->assertEquals('Groucho', $this->Core->Minion->State['Nickname']);
    }

    public function testOnPing () {
        $this->Core->Minion->expects($this->once())
            ->method('send')
            ->with('PONG Harpo');
        
        $data = array('message' => 'Harpo');
        $this->Core->On['PING']($data);
    }

    public function testOn433 () {
        $this->Core->Minion->expects($this->once())
            ->method('send')
            ->with($this->stringStartsWith('NICK Groucho'));
        
        $data = false;
        $this->Core->On['433']($data);
    }

}

?>

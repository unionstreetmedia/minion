<?php

namespace Minion;

require('lib/minion.php');

class MinionTest extends \PHPUnit_Framework_TestCase {

    public static $Config;
    public static $Socket;
    public static $Plugin;

    public static function setUpBeforeClass () {
        self::$Config = new Config();
        self::$Plugin = new Plugin('A', 'B', 'C');
        self::$Plugin
            ->on('a', function (&$minion, &$data) { $minion->Foo = 'a'; })
            ->on('c', function (&$minion, &$data) { $minion->Foo = 'c'; })
            ->on('loop-end', function (&$minion, &$data) { $minion->quit('bye'); });
    }

    public function setUp () {
        self::$Socket = $this->getMock('Socket', array('__construct', 'read', 'write'));
        self::$Socket->expects($this->any())
            ->method('__construct')
            ->with($this->equalTo('localhost'), $this->equalTo(6667))
            ->will($this->returnValue(true));
        self::$Socket->expects($this->any())
            ->method('read')
            ->will($this->returnValue('...'));
        self::$Socket->expects($this->any())
            ->method('write')
            ->will($this->returnArgument(0));
    }

    public function testConstructor () {
        $minion = new Minion(self::$Config);
        $this->assertInstanceOf('Minion\Minion', $minion);
        $this->setSocket($minion);
        return $minion;
    }

    /**
     * @depends testConstructor
     */
    public function testAddPlugin ($minion) {
        $minion->addPlugin(self::$Plugin, array('A' => array('foo' => 'bar')));
        
        // Reflect the object for inspection.
        $minionReflection = new \ReflectionObject($minion);
        $plugins = $minionReflection->getProperty('plugins');
        $plugins->setAccessible(true);
        $triggers = $minionReflection->getProperty('triggers');
        $triggers->setAccessible(true);
        
        $this->assertEquals($plugins->getValue($minion), array(self::$Plugin));
        $this->assertEquals(count($triggers->getValue($minion)), 3);
    }

    /**
     * @depends testConstructor
     * @depends testAddPlugin
     */
    public function testLoadPlugin ($minion) {
        $minion->loadPlugin('stubPlugin', array());

        // Reflect the object for inspection.
        $minionReflection = new \ReflectionObject($minion);
        $plugins = $minionReflection->getProperty('plugins');
        $plugins->setAccessible(true);
        $triggers = $minionReflection->getProperty('triggers');
        $triggers->setAccessible(true);
        
        $loaded = $plugins->getValue($minion);
        // Because we are using a shared instance of Minion, this is plugin with index 1.
        $this->assertEquals($loaded[1]->Name, 'stubPlugin');
        $this->assertEquals(count($triggers->getValue($minion)), 4);
    }

    /**
     * @depends testConstructor
     */
    public function testParse ($minion) {
        $parse = new \ReflectionMethod($minion, 'parse');
        $parse->setAccessible(true);

        $lines = array(
            ':irc.example.net 251 MinionPHP :There are 236 users and 80314 invisible on 34 servers',
            ':someone!~someone@somewhere.example.com PRIVMSG MinionPHP :VERSION',
            'NOTICE #minion.php :this is a test: it has some colons :that make parsing tricky.'
        );

        $expected = array(
            array('source' => 'irc.example.net', 'command' => '251', 'arguments' => array('MinionPHP'), 'message' => 'There are 236 users and 80314 invisible on 34 servers'),
            array('source' => 'someone!~someone@somewhere.example.com', 'command' => 'PRIVMSG', 'arguments' => array('MinionPHP'), 'message' => 'VERSION'),
            array('source' => null, 'command' => 'NOTICE', 'arguments' => array('#minion.php'), 'message' => 'this is a test: it has some colons :that make parsing tricky.')
        );

        for ($i = 0, $c = count($lines); $i < $c; $i++) {
            $this->assertEquals($parse->invoke($minion, $lines[$i]), $expected[$i]);
        }
    }

    /**
     * @depends testConstructor
     * @depends testAddPlugin
     */
    public function testTrigger ($minion) {
        $trigger = new \ReflectionMethod($minion, 'trigger');
        $trigger->setAccessible(true);

        $trigger->invoke($minion, 'a');
        $this->assertEquals($minion->Foo, 'a');
        unset($minion->Foo);
    }

    /**
     * @depends testConstructor
     */
    public function testSend ($minion) {
        $this->setSocket($minion);
        $this->assertEquals('foo bar baz', $minion->send('foo bar baz'));
    }

    /**
     * @depends testConstructor
     */
    public function testQuit ($minion) {
        $this->assertTrue($minion->quit('bye'));
        $this->assertTrue($this->getPrivate($minion, 'exit'));
    }

    /**
     * @depends testConstructor
     * @depends testParse
     * @depends testTrigger
     * @depends testQuit
     */
    public function testRun ($minion) {
        // TODO: This test is weak.
        $this->assertNull($minion->run());
    }

    /**
     * @depends testConstructor
     * @depends testSend
     */
    public function testMsg ($minion) {
        $this->setSocket($minion);
        $this->assertEquals('PRIVMSG bar :foo', $minion->msg('foo', 'bar'));
    }

    /**
     * @depends testConstructor
     * @depends testSend
     */
    public function testCtcp ($minion) {
        $this->setSocket($minion);
        $this->assertEquals('PRIVMSG bar :' . chr(1) . 'foo' . chr(1), $minion->ctcp('foo', 'bar'));
    }

    // Helpers
    private function setSocket (&$minion) {
        $this->setPrivate($minion, 'socket', self::$Socket);
    }

    private function setPrivate (&$minion, $property, $value) {
        $property = $this->accessPrivate($minion, $property);
        $property->setValue($minion, $value);
        return $minion;
    }

    private function getPrivate (&$minion, $property) {
        $property = $this->accessPrivate($minion, $property);
        return $property->getValue($minion);
    }

    private function accessPrivate (&$minion, $property) {
        $property = new \ReflectionProperty($minion, $property);
        $property->setAccessible(true);
        return $property;
    }

}

class Config {

    public $Host = 'localhost';
    public $Port = 6667;

    public $MinionLogFile = '/dev/null';
    public $Debug = false;

    public $PluginDirectory = './tests/lib';
    public $PluginConfig = array();

}

?>

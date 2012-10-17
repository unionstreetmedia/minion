<?php

namespace Minion;

class PluginTest extends \PHPUnit_Framework_TestCase {

    public function testConstructor () {
        $plugin = new Plugin('War and Peace', 'An epic novel about Tsarist Russia.', 'Leo Tolstoy');
        $this->assertInstanceOf('Minion\Plugin', $plugin);
        $this->assertEquals($plugin->Name, 'War and Peace');
        $this->assertEquals($plugin->Description, 'An epic novel about Tsarist Russia.');
        $this->assertEquals($plugin->Author, 'Leo Tolstoy');
        return $plugin;
    }

    /**
     * @depends testConstructor
     */
    public function testOn () {
        $plugin = new Plugin('A', 'B', 'C');
        $plugin->on('foo', 'bar');
        $this->assertEquals($plugin->On['foo'], 'bar');
    }

    /**
     * @depends testConstructor
     */
    public function testConfigure () {
        $configuration = array('a', 'b', 'c');
        $plugin = new Plugin('A', 'B', 'C');
        $plugin->configure($configuration);
        $this->assertEquals($plugin->Config, $configuration);
    }

    /**
     * @depends testConstructor
     * @depends testConfigure
     */
    public function testConf () {
        $configuration = array('a' => array('b' => 1, 'c' => 2), 'd' => 3, 'e' => array('f' => 4, 'g' => array('h' => 5)));
        $plugin = new Plugin('A', 'B', 'C');
        $plugin->configure($configuration);
        $this->assertEquals($plugin->conf('a/c'), 2);
        $this->assertEquals($plugin->conf('d'), 3);
        $this->assertNull($plugin->conf('e/g/i'));
    }

    /**
     * @depends testConstructor
     */
    public function testSimpleCommand () {
        $plugin = new Plugin('A', 'B', 'C');
        $minion = $this->getMock('Minion\Minion');
        $minion->state['Nickname'] = 'Foo';
        $plugin->Minion = $minion;
        $this->assertFalse($plugin->simpleCommand(array('message' => 'not a command')));
        $this->assertEquals($plugin->simpleCommand(array('message' => '!command argument argument')), array('command', array('argument','argument')));
        $this->assertEquals($plugin->simpleCommand(array('message' => '!cool')), array('cool', array()));
        $this->assertEquals($plugin->simpleCommand(array('message' => 'Foo: !something')), array('something', array()));
    }

    /**
     * @depends testConstructor
     */
    public function testMatchCommand () {
        $plugin = new Plugin('A', 'B', 'C');
        $this->assertEquals($plugin->matchCommand(array('message' => 'foo bar 123 baz'), '/bar/'), array('bar'));
        $this->assertFalse($plugin->matchCommand(array('message' => 'foo bar 123 baz'), '/quux/'));
    }

}

?>

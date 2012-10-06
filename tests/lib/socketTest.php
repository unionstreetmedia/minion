<?php

namespace Minion;

// Override fsockopen within the Minion namespace.
function fsockopen () {
    return fopen('/tmp/minionSocketData.txt', 'c+');
}

class SocketTest extends \PHPUnit_Framework_TestCase {

    private $lineOne = "Now is the time for all good men\r\n";
    private $lineTwo = "to come to the aid of their party.\r\n";
    private $lineThree = " -- Charles E. Weller";

    public function setUp () {
        $testDataFile = fopen('/tmp/minionSocketData.txt', 'w');
        fwrite($testDataFile, $this->lineOne);
        fwrite($testDataFile, $this->lineTwo);
        fclose($testDataFile);
    }

    public function testConstructor () {
        $this->assertInstanceOf('Minion\Socket', new Socket('localhost'));
    }

    /**
     * @depends testConstructor
     */
    public function testConnect () {
        $socket = new Socket('localhost');
        $socket->connect();
        $this->assertInstanceOf('Minion\Socket', $socket);
        $this->assertFalse($socket->connect());
    }

    /**
     * @depends testConnect
     */
    public function testRead () {
        $socket = new Socket('localhost');
        $socket->connect();
        $this->assertEquals($socket->read(), $this->lineOne);
        $this->assertEquals($socket->read(), $this->lineTwo);
    }

    /**
     * @depends testRead
     */
    public function testWrite () {
        $socket = new Socket('localhost');
        $socket->connect();
        $this->assertTrue($socket->write($this->lineThree));
    }

    public function tearDown () {
        unlink('/tmp/minionSocketData.txt');
    }

}

?>

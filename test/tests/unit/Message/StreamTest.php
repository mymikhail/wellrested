<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\Stream;

/**
 * @covers WellRESTed\Message\Stream
 * @group message
 */
class StreamTest extends \PHPUnit_Framework_TestCase
{
    private $resource;
    private $content = "Hello, world!";

    public function setUp()
    {
        $this->resource = fopen("php://memory", "w+");
        fwrite($this->resource, $this->content);
    }

    public function tearDown()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public function testCreatesInstanceWithStreamResource()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertNotNull($stream);
    }

    public function testCreatesInstanceWithString()
    {
        $stream = new \WellRESTed\Message\Stream("Hello, world!");
        $this->assertNotNull($stream);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidResourceProvider
     */
    public function testThrowsExceptiondWithInvalidResource($resource)
    {
        new \WellRESTed\Message\Stream($resource);
    }

    public function invalidResourceProvider()
    {
        return [
            [null],
            [true],
            [4],
            [[]]
        ];
    }

    public function testCastsToString()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals($this->content, (string) $stream);
    }

    public function testClosesHandle()
    {
        $stream = new Stream($this->resource);
        $stream->close();
        $this->assertFalse(is_resource($this->resource));
    }

    public function testDetachReturnsHandle()
    {
        $stream = new Stream($this->resource);
        $this->assertSame($this->resource, $stream->detach());
    }

    public function testDetachUnsetsInstanceVariable()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    public function testReturnsSize()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals(strlen($this->content), $stream->getSize());
    }

    public function testTellReturnsHandlePosition()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        fseek($this->resource, 10);
        $this->assertEquals(10, $stream->tell());
    }

    public function testReturnsOef()
    {
        $stream = new Stream($this->resource);
        $stream->rewind();
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    public function testReadsSeekableStatusFromMetadata()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $seekable = $metadata["seekable"] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    public function testSeeksToPosition()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->resource));
    }

    public function testRewindReturnsToBeginning()
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->resource));
    }

    public function testWritesToHandle()
    {
        $message = "\nThis is a stream.";
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    /** @expectedException \RuntimeException */
    public function testThrowsExceptionOnErrorWriting()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "r");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->write("Hello, world!");
    }

    /** @expectedException \RuntimeException */
    public function testThrowsExceptionOnErrorReading()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "w");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->read(10);
    }

    public function testReadsFromStream()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals("world", $string);
    }

    /** @expectedException \RuntimeException */
    public function testThrowsExceptionOnErrorReadingToEnd()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "w");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->getContents();
    }

    public function testReadsToEnd()
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals("world!", $string);
    }

    public function testReturnsMetadataArray()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals(stream_get_meta_data($this->resource), $stream->getMetadata());
    }

    public function testReturnsMetadataItem()
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $this->assertEquals($metadata["mode"], $stream->getMetadata("mode"));
    }

    /** @dataProvider modeProvider */
    public function testReturnsIsReadableForReadableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new Stream($resource);
        $this->assertEquals($readable, $stream->isReadable());
    }

    /** @dataProvider modeProvider */
    public function testReturnsIsWritableForWritableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new \WellRESTed\Message\Stream($resource);
        $this->assertEquals($writeable, $stream->isWritable());
    }

    public function modeProvider()
    {
        return [
            ["r",  true,  false],
            ["r+", true,  true],
            ["w",  false, true],
            ["w+", true,  true],
            ["a",  false, true],
            ["a+", true,  true],
            ["x",  false, true],
            ["x+", true,  true],
            ["c",  false, true],
            ["c+", true,  true]
        ];
    }
}

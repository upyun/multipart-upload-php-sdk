<?php

class FileTest extends PHPUnit_Framework_TestCase {
    /**
     * @var \Crocodile\File
     */
    public $file;
    public function setUp()
    {
        $this->file = new \Crocodile\File(dirname(__FILE__) . "/assets/bar.txt");
    }

    /**
     * @expectedException \Exception
     */
    public function testConstructWithException()
    {
        new \Crocodile\File("assets/bar.txt");
    }

    public function testGetHandler()
    {
        $this->assertEquals(true, is_resource($this->file->getHandler()));
    }

    public function testGetSize()
    {
        $size = $this->file->getSize();
        $this->assertEquals(447, $size);
    }

    public function testGetRealPath()
    {
        $this->assertEquals(dirname(__FILE__) . "/assets/bar.txt", $this->file->getRealPath());
    }

    public function testDestruct()
    {
        $fh = $this->file->getHandler();

        $this->assertEquals(true, is_resource($fh));
        $this->file = null;
        $this->assertEquals(false, is_resource($fh));
    }

    public function testGetMd5FileHash()
    {
        $this->assertEquals(md5_file(dirname(__FILE__) . "/assets/bar.txt"), $this->file->getMd5FileHash());
    }

    /**
     * @covers \Crocodile\File::readBlock
     */
    public function testReadBlock()
    {
        $data = $this->file->readBlock(0, 447);
        $this->assertEquals(447, strlen($data), 8192);
    }

    /**
     * @covers \Crocodile\File::readBlock
     */
    public function testReadBlockChunk()
    {
        $blocks = 5; $blockSize = 100;$data = '';
        for($blockIndex = 1; $blockIndex <= $blocks; $blockIndex++) {
            $startPosition = ($blockIndex - 1) * 100;
            $endPosition = $blockIndex === $blocks ? $this->file->getSize() : $startPosition + $blockSize;
            $data .= $this->file->readBlock($startPosition, $endPosition, 51);
        }
        $this->assertEquals(file_get_contents(dirname(__FILE__) . "/assets/bar.txt"), $data);
    }
}
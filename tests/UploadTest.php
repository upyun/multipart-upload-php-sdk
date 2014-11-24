<?php


class UploadTest extends PHPUnit_Framework_TestCase{
    /**
     * @var \Crocodile\Signature
     */
    public $sign;

    public function setUp()
    {
        $this->sign = new \Crocodile\Signature(getenv('UPYUN_FORMAPI_KEY'));
    }

    /**
     * @covers \Crocodile\Upload::upload
     * @expectedException \Exception
     */
    public function testUploadWithoutPath()
    {
        $upload = new \Crocodile\Upload($this->sign);
        $upload->setBucketName(getenv('UPYUN_FILE_BUCKET'));
        $upload->upload(
            new \Crocodile\File(dirname(__FILE__) . "/assets/test.jpg"),
            array(
                'return_url' => 'your_return_url',
                'notify_url' => 'your_notify_url',
            )
        );
    }

    public function testUpload()
    {
        $upload = new \Crocodile\Upload($this->sign);
        $upload->setBucketName(getenv('UPYUN_FILE_BUCKET'));
        $upload->upload(
            new \Crocodile\File(dirname(__FILE__) . "/assets/test.jpg"),
            array(
                'path' => '/test/test.jpg',
                'return_url' => 'your_return_url',
                'notify_url' => 'your_notify_url',
            )
        );
        $this->assertEquals(true, $upload->isUploadSuccess());
        $this->assertEquals(32, strlen($upload->getXRequestId()));
    }

    public function testUploadWithJsonValidate()
    {
        $upload = new \Crocodile\Upload($this->sign);
        $upload->setBucketName(getenv('UPYUN_FILE_BUCKET'));
        $result = $upload->upload(
            new \Crocodile\File(dirname(__FILE__) . "/assets/bar.txt"),
            array(
                'path' => '/test/bar.txt'
            )
        );
        $this->assertEquals(true, $upload->getSignature()->syncJsonValidate($result));
    }
} 
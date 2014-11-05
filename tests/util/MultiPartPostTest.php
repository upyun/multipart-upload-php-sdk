<?php
class MultiPartPostTest extends PHPUnit_Framework_TestCase {

    public function testPost()
    {
        if(getenv('UPYUN_TEST_ENV') !== 'local') {
            return ;
        }
        $binaryData = "sdfer34245xccxfg";
        $result = \Crocodile\Util\MultiPartPost::post(
            array('file' => array('data' => $binaryData), 'aa' => 1),
            "http://mytest.com/multipart_upload_test.php"
        );
        $this->assertEquals($binaryData, $result);
    }
} 
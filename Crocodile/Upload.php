<?php

namespace Crocodile;
use Crocodile\util\MultiPartPost;

class Upload {

    public $api = "http://m0.api.upyun.com/";
    protected $blocks;
    protected $blockSize;
    protected $expiration;
    protected $fileHash;
    protected $saveToken;
    protected $bucketName;
    protected $realPath;
    protected $fileSize;
    protected $handle;

    public function __construct()
    {

    }
    /**
     * @var Signature;
     */
    protected $signature;

    public function upload($path)
    {
        $realpath = realpath($path);
        if(!($realpath && file_exists($realpath))) {
            throw new \Exception('upload file not exists');
        }
        $this->realPath = $realpath;
        $this->fileSize = filesize($path);
        $this->blocks = ceil($this->fileSize / $this->blockSize);
        $this->fileHash = md5_file($this->realPath);

        $this->initUpload();
        for($blockIndex = 1; $blockIndex <= $this->blocks; $blockIndex++) {
            $this->blockUpload($blockIndex);
        }
        $this->endUpload();
    }

    protected function blockUpload($index)
    {
        $startPosition = ($index - 1) * $this->blockSize;
        $endPosition = $index === $this->blocks ? $this->fileSize : $startPosition + $this->blockSize;
        $fh = fopen($this->realPath, 'rb');
        $data = $this->readBlock($fh, $endPosition, $startPosition);
        $hash = md5($data);
        $metaData = array(
            'save_token' => $this->saveToken,
            'expiration' => $this->expiration,
            'block_index' => $index,
            'block_hash' => $hash,
        );
        $postData['policy'] = $this->signature->createPolicy($metaData);
        $postData['signature'] = $this->signature->createSign($metaData);
        $postData['file'] = array('data' => $data);
        $result = MultiPartPost::post($postData, $this->api . $this->bucketName . "/");
        $data = $this->parseResult($result);
    }

    protected function readBlock($fh, $endPosition, $currentPosition, $len = 8192)
    {
        if($currentPosition >= $endPosition) {
            return '';
        }
        if($currentPosition + $len > $endPosition) {
            $len = $endPosition - $currentPosition;
        }

        fseek($fh, $currentPosition);
        $data = fread($fh, $len);
        return $data . $this->readBlock($fh, $endPosition, $currentPosition + $len, $len);
    }

    protected function initUpload()
    {
        $metaData = array(
            'path' => $this->realPath,
            'expiration' => $this->expiration,
            'file_blocks' => $this->blocks,
            'file_hash' => $this->fileHash,
            'file_size' => $this->fileSize,
        );

        $data = $this->postData($metaData);
        $this->saveToken = $data['save_token'];
    }

    protected function parseResult($result)
    {
        $data = json_decode($result, true);
        if(isset($data->error_code)) {
            throw new \Exception(
                sprintf("upload failed, error code: %s, message: %s, upload path: %s",
                    $data->error_code,
                    $data->message,
                    $data->path
                ));
        }
        return $data;
    }

    private function postData($metaData, $data = array(), $headers = array())
    {
        $policy = $this->signature->createPolicy($metaData);
        $signature = $this->signature->createSign($metaData);
        $postData = compact('policy', 'signature');
        $postData = array_merge($postData, $data);
        $url = $this->api . $this->bucketName . "/";
        $ch = curl_init($url);
        $headers = array_merge($headers,
            array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
        ));
        $result = curl_exec($ch);
        $data = $this->parseResult($result);
        curl_close($ch);
        return $data;
    }

    protected function endUpload()
    {
        $metaData['save_token'] = $this->saveToken;
        $metaData['expiration'] = $this->expiration;
        $this->postData($metaData);
    }
}
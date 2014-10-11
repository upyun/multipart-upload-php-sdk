<?php

namespace Crocodile;
use Crocodile\Util\MultiPartPost;

class Upload {
    /**
     * @var string:请求的接口地址
     */
    public $api = "http://m0.api.upyun.com/";
    /**
     * @var string:文件分块数
     */
    protected $blocks;
    /**
     * @var int: 文件块大小
     */
    protected $blockSize;
    /**
     * @var int: 文件过期时间
     */
    protected $expiration;
    /**
     * @var string: save_token
     */
    protected $saveToken;
    /**
     * @var string: 上传的空间名
     */
    protected $bucketName;
    /**
     * @var array: 文件块的状态 [1,0,1] 表示共三个文件块，第0块和第2块上传成功
     */
    protected $status;

    /**
     * @var Signature: 签名
     */
    protected $signature;

    public function __construct($signature)
    {
        //default 1MB
        $this->blockSize = 1024 * 1024 * 1024;
        $this->signature = $signature;
    }

    public function setBlockSize($size)
    {
        $this->blockSize = $size;
    }

    public function setBucketName($bucketName)
    {
        $this->bucketName = $bucketName;
    }

    /**
     * 分块上传本地文件
     * @param File $file: 等待上传的文件
     * @param array $data : 上传的参数, 必须包含路径选项 'path' => '/yourpath/file.ext'
     * @return mixed
     * @throws \Exception
     */
    public function upload(File $file, $data)
    {
        $this->blocks = intval(ceil($file->getSize() / $this->blockSize));

        $result = $this->initUpload($file, $data);
        $this->updateStatus($result);

        $times = 0;
        do {
            for($blockIndex = 0; $blockIndex < $this->blocks; $blockIndex++) {
                if(! $this->status[$blockIndex]) {
                    $result = $this->blockUpload($blockIndex, $file, $data);
                    $this->updateStatus($result);
                }
            }
            $times++;
        } while(!$this->isUploadSuccess() && $times < 3);

        if($this->isUploadSuccess()) {
            $result = $this->endUpload($data);
            return $result;
        } else {
            throw new \Exception(sprintf("chunk upload failed! status is : [%s]", implode(',', $this->status)));
        }
    }

    /**
     * 初始化，将文件信息发送个服务器
     * @param File $file
     * @param array $data : 附加参数 必须包含路径选项 'path' => '/yourpath/file.ext'
     * @return mixed
     */
    public function initUpload(File $file, $data)
    {
        $this->expiration = time() + 60;

        $metaData = array(
            'expiration' => $this->expiration,
            'file_blocks' => $this->blocks,
            'file_hash' => $file->getMd5FileHash(),
            'file_size' => $file->getSize(),
        );
        $metaData = array_merge($metaData, $data);
        $policy = $this->signature->createPolicy($metaData);
        $signature = $this->signature->createSign($metaData);
        $postData = compact('policy', 'signature');

        $result = $this->postData($postData);
        $this->saveToken = $result['save_token'];
        $this->signature->setTokenSecret($result['token_secret']);
        return $result;
    }


    /**
     * 上传单个文件块
     * @param $index : 文件块索引， 从0开始
     * @param File $file
     * @param array $data: 附加参数,可选
     * @return mixed
     */
    public function blockUpload($index, File $file, $data = array())
    {
        $startPosition = $index * $this->blockSize;
        $endPosition = $index >= $this->blocks - 1 ? $file->getSize() : $startPosition + $this->blockSize;

        $fileBlock = $file->readBlock($startPosition, $endPosition);
        $hash = md5($fileBlock);

        $metaData = array(
            'save_token' => $this->saveToken,
            'expiration' => $this->expiration,
            'block_index' => $index,
            'block_hash' => $hash,
        );
        $metaData = array_merge($metaData, $data);
        $postData['policy'] = $this->signature->createPolicy($metaData);
        $postData['signature'] = $this->signature->createSign($metaData, false);
        $postData['file'] = array('data' => $fileBlock);

        $result = MultiPartPost::post($postData, $this->api . $this->bucketName . "/");
        $result = $this->parseResult($result);
        return $result;
    }

    /**
     * 文件块全部上传成功后，请求服务器，终止文件上传
     * @param array $data:  附加参数,可选
     * @return mixed
     */
    public function endUpload($data = array())
    {
        $metaData['save_token'] = $this->saveToken;
        $metaData['expiration'] = $this->expiration;
        $metaData = array_merge($metaData, $data);

        $policy = $this->signature->createPolicy($metaData);
        $signature = $this->signature->createSign($metaData, false);

        $postData = compact('policy', 'signature');
        $result = $this->postData($postData);
        return $result;
    }

    /**
     * 判断所有文件块是否上传成功
     * @return bool
     */
    public function isUploadSuccess()
    {
        return array_sum($this->status) === count($this->status);
    }

    protected function parseResult($result)
    {
        $data = json_decode($result, true);
        if(isset($data['error_code'])) {
            throw new \Exception(
                sprintf("upload failed, error code: %s, message: %s",
                    $data['error_code'],
                    $data['message']
                ));
        }
        return $data;
    }

    /**
     * 发送 Content-Type: application/x-www-form-urlencoded 的POST请求
     * @param array $postData
     * @param array $headers
     * @param int $retryTimes : 重试次数
     * @return mixed
     */
    protected function postData($postData, $headers = array(), $retryTimes = 3)
    {
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

        $times = 0;
        do{
            $result = curl_exec($ch);
            $times++;
        } while($result === false && $times < $retryTimes);

        $data = $this->parseResult($result);
        curl_close($ch);
        return $data;
    }

    protected function updateStatus($result)
    {
        if(isset($result['status'])) {
            $this->status = $result['status'];
        }
    }
}
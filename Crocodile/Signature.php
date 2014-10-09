<?php

namespace Crocodile;
/**
 * 签名操作
 * Class Signature
 * @package Crocodile
 */
class Signature {
    protected $formApiKey;
    protected $tokenSecret;

    public function __construct($key = '')
    {
        $this->formApiKey = $key;
    }

    public function setFormApiKey($key)
    {
        $this->formApiKey = $key;
    }

    public function setTokenSecret($key)
    {
        $this->tokenSecret = $key;
    }

    /**
     * 生成签名
     * @param $data
     * @param bool $init: 初始化上传则为 true
     * @return bool|string
     */
    public function createSign($data, $init = true)
    {
        if(is_array($data)) {
            ksort($data);
            $string = '';
            foreach($data as $k => $v) {
                $string .= "$k$v";
            }
            $string .= $init ? $this->formApiKey : $this->tokenSecret;
            $sign = md5($string);
            return $sign;
        }
        return false;
    }

    /**
     * 获取 Policy 值
     * @param $metaData
     * @return bool|string
     */
    public function createPolicy($metaData)
    {
        if(is_array($metaData)) {
            $jsonStr = json_encode($metaData);
            return base64_encode($jsonStr);
        }
        return false;
    }

    public function validateSign($data, $init = true)
    {
        if(! isset($data['signature'])) {
            return false;
        }

        $sign = $data['signature'];
        unset($data['signature']);

        return $this->createSign($data, $init) === $sign;
    }
}
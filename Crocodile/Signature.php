<?php

namespace Crocodile;
/**
 * 签名操作
 * Class Signature
 * @package Crocodile
 */
class Signature {
    protected $formApiKey;

    public function __construct($key = '')
    {
        $this->formApiKey = $key;
    }

    public function setFormApiKey($key)
    {
        $this->formApiKey = $key;
    }

    public function createSign($data)
    {
        if(is_array($data)) {
            ksort($data);
            $string = '';
            foreach($data as $k => $v) {
                $string .= "$k$v";
            }
            $string .= $this->formApiKey;
            $sign = md5($string);
            return $sign;
        }
        return false;
    }

    public function createPolicy($metaData)
    {
        if(is_array($metaData)) {
            $jsonStr = json_encode($metaData);
            return base64_encode($jsonStr);
        }
        return false;
    }

    public function validateSign($data, $sign = '')
    {
        if(isset($data['signature'])) {
            $sign = $data['signature'];
            unset($data['signature']);
        }
        return $this->createSign($data) === $sign;
    }
}
<?php

/**
 * 辅助类：利用curl进行 multipart/form-data 提交数据
 */

namespace Crocodile\Util;


class MultiPartPost {

    public static function post($postData, $url, $retryTimes = 3)
    {
        $delimiter = '-------------' . uniqid();
        $data = '';
        foreach($postData as $name => $content) {
            if(is_array($content)) {
                $data .= "--" . $delimiter . "\r\n";
                $filename = isset($content['name']) ? $content['name'] : $name;
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $filename . "\" \r\n";
                $type = isset($content['type']) ? $content['type'] : 'application/octet-stream';
                $data .= 'Content-Type: ' . $type . "\r\n\r\n";
                $data .= $content['data'] . "\r\n";
            } else {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
        }
        $data .= "--" . $delimiter . "--";

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER , array(
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $times = 0;
        do{
            $result = curl_exec($handle);
            $times++;
        } while($result === false && $times < $retryTimes);

        curl_close($handle);
        return $result;
    }
} 
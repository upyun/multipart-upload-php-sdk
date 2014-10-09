<?php

namespace Crocodile;


class File {

    protected $realPath;
    protected $size;
    protected $md5FileHash;
    protected $handler;

    public function __construct($path){
        $this->realPath = realpath($path);
        if(!($this->realPath && file_exists($this->realPath))) {
            throw new \Exception('upload file not exists');
        }
        $this->size = filesize($path);
        $this->md5FileHash = md5_file($this->realPath);
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getMd5FileHash()
    {
        return $this->md5FileHash;
    }

    public function getHandler()
    {
        if(is_resource($this->handler) === false) {
            $this->handler = fopen($this->realPath, 'rb');
            if($this->handler === false) {
                throw new \Exception('open file failed:' . $this->realPath);
            }
        }
        return $this->handler;
    }

    public function readBlock($currentPosition, $endPosition, $len = 8192, $data = '')
    {
        if($currentPosition >= $endPosition) {
            return $data;
        }
        if($currentPosition + $len > $endPosition) {
            $len = $endPosition - $currentPosition;
        }

        fseek($this->getHandler(), $currentPosition);
        $data .= fread($this->getHandler(), $len);
        return $this->readBlock($currentPosition + $len, $endPosition, $len, $data);
    }

    public function getRealPath()
    {
        return $this->realPath;
    }

    public function __destruct()
    {
        if(is_resource($this->handler)) {
            fclose($this->handler);
        }
    }
} 
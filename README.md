# 又拍云文件分块上传 PHP-SDK
![build](https://travis-ci.org/upyun/multipart-upload-php-sdk.svg)
## 目录
- [使用说明](#instructions)
- [安装说明](#install)
  - [要求](#require)
  - [通过[composer](https://getcomposer.org/)安装](#composer install)
  - [直接下载压缩包安装](#download zip and install)
- [示例](#usage)
  - [上传文件](#upload)
  - [回调验证](#validate)

<a name="instructions"></a>
## 使用说明
普通方式进行大文件上传时，稳定性较低，无法断点续传。

使用该SDK进行大文件上传时，会将大文件分成各个小块，再进行上传。

最大支持1024个分块，每个块不能小于1MB,不能大于5MB。

当上传中断时，在`expiration`有效期內，只需继续上传剩余的块即可，实现断点续传功能。

(`\Crocodile\Upload::upload()`方法会自动将剩余的块上传)。

该特性仅当上传相同的文件、分块大小、上传的目标路径都不变时才有效

上传之前可以通过`\Crocodile\Upload::setBlockSize()`设置分块的大小。

<a name="install"></a>
## 安装说明

<a name="require"></a>
### 要求
  php 5.3+

<a name="composer install"></a>
### 通过[composer](https://getcomposer.org/)安装
1.安装composer
```
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

2.在你的项目根目录下创建`composer.json`，并添加如下内容
```
    {
        "repositories": [
            {   
                "type": "vcs",
                "url": "https://github.com/upyun/multipart-upload-php-sdk"
            }   
        ],  
        "require":{
            "upyun/sugar-php-sdk":"dev-master"
        }   
    }
```

3.运行 `composer install`

<a name="download zip and install"></a>
### 直接下载压缩包安装
通过github直接下载最新稳定版

在项目中添加以下代码
```
include "Crocodile/Upload.php";
include "Crocodile/Signature.php";
include "Crocodile/File.php";
include "Crocodile/Util/MultiPartPost.php";
```

<a name="usage"></a>
## 示例

<a name="upload"></a>
### 上传文件
```php
$formApiKey = "w3mRPyWWwG_your_form_api_key_6C5X9pac=";
$upload = new \Crocodile\Upload(new \Crocodile\Signature($formApiKey));
$upload->setBucketName('your_bucket_name');//上传的空间
try {
    //其他参数参见文档: http://docs.upyun.com/api/form_api/#Policy内容详解
    $options = array(
       'path' => '/test.png',                   // 文件在服务器保存的路径,必须
       'return_url' => 'http://yourdomain.com', // 回调地址,可选
       'notify_url' => 'http://yourdomain.com', // 通知地址,可选
    )
    $result = $upload->upload(
        new \Crocodile\File("/path/to/your/file"),
        $options
    );
} catch(\Exception $e) {
    echo $e->getMessage();
}
```

<a name="validate"></a>
### 回调验证
上传文件后，可选有三种方式进行上传结果的回调通知。客户端需要对回调进行验证
* 如果没有设置`return_url`，服务端直接同步返回json数据进行验证
* 如果设置了`return_url`，服务端将返回`302`跳转到`return_url`，并将数据以`GET`方式返回
* 如果设置了`notify_url`，服务端异步将数据`POST`到`notify_url`

*注意* `notify_url`和`return_url`可以同时设置

三种回调验证代码如下所示:
1.直接返回json数据验证
```php
$formApiKey = "w3mRPyWWwG_your_form_api_key_6C5X9pac=";
upload = new \Crocodile\Upload(new \Crocodile\Signature($formApiKey));
$upload->setBucketName('your_bucket_name');//上传的空间
try {
    //其他参数参见文档: http://docs.upyun.com/api/form_api/#Policy内容详解
    $options = array(
       'path' => '/test.png',                   // 文件在服务器保存的路径,必须
    )
    $result = $upload->upload(
        new \Crocodile\File("/path/to/your/file"),
        $options
    );
    if($upload->getSignature()->syncJsonValidate($result)) {
        //回调验证成功

    } else {
        //回调验证失败

    }
} catch(\Exception $e) {
    echo $e->getMessage();
}
```
2.`302`跳转到`return_url`验证
```php
/**
 * 表单API和上传时保持一致
 */
$formApiKey = "w3mRPyWG_your_form_api_key_6C57AX9pac=";
$sign = new \Crocodile\Signature($formApiKey);
if($sign->returnValidate()) {
    echo '回调签名验证成功';
} else {
    echo '回调签名验证失败';
}
```
3.`notify_url`异步验证
```php
/**
 * 表单API和上传时保持一致
 */
$formApiKey = "w3mRPOHwG_your_form_api_key_6C57AX9pac=";
$sign = new \Crocodile\Signature($formApiKey);
if($sign->notifyValidate()) {
    echo '回调签名验证成功';
} else {
    echo '回调签名验证失败';
}
```

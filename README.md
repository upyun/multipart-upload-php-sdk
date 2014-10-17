### 文件分块上传 Crocodile PHP-SDk
使用该SDK进行文件上传时，会将大文件分成各个小块，再进行上传。当上传中断时，
只需继续上传剩余的块即可(`\Crocodile\Upload::upload()`方法会自动将剩
余的块上传)。该特性仅当上传相同的文件、分块大小、上传的目标路径不变时才有效
上传之前可以通过`\Crocodile\Upload::setBlockSize()`设置分块的大小。
### 示例代码:
#### 上传文件
```php
$formApiKey = "w3mRPyWWOHwG_your_form_api_key_6C57AX9pac=";
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
#### 文件回调验证
```php
/**
 * 以 302同步回调为例
 * 表单API和上传时相同
 */
$formApiKey = "w3mRPyWWOHwG_your_form_api_key_6C57AX9pac=";
$sign = new \Crocodile\Signature($formApiKey);
$keys = array('path', 'content_type', 'content_length',
              'image_width', 'image_height', 'image_frames',
              'last_modified', 'signature');
$data = array();
foreach($keys as $key) {
    if(isset($_GET[$key])) {
        $data[$key] = $_GET[$key];
    }
}
if($sign->validateSign($data)) {
    echo '回调签名验证成功';
} else {
    echo '回调签名验证失败';
}
```

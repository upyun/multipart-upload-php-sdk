### 文件分块上传 Crocodile PHP-SDk
示例代码:

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

        );
        $this->assertEquals(true, $upload->isUploadSuccess());
    } catch(\Exception $e) {
        echo $e->getMessage();
    }
```
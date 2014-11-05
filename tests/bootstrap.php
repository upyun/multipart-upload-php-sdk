<?php
require dirname(__DIR__) . '/vendor/autoload.php';

putenv('UPYUN_FORMAPI_KEY=ESxWIoMmF39nSDY7CSFUsC7s50U=');
putenv('UPYUN_FILE_BUCKET=sdkfile');

if(getenv('UPYUN_TEST_ENV') !== 'travis') {
    putenv('UPYUN_TEST_ENV=local');
}
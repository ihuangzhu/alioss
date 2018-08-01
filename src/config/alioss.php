<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 基础配置
    |--------------------------------------------------------------------------
    |
    | 阿里云对象存储最基础配置项，相关数据请至阿里云对象存储控制台获取。
    |
    */
    'oss_key' => env('OSS_ACCESS_KEY_ID'),
    'oss_secret' => env('OSS_ACCESS_KEY_SECRET'),


    /*
    |--------------------------------------------------------------------------
    | 对象存储域配置
    |--------------------------------------------------------------------------
    |
    | 对象上传至阿里云，指定存放域。
    |
    */
    'oss_endpoint' => env('OSS_ENDPOINT'),
    'oss_bucket' => env('OSS_BUCKET'),
    'oss_bucket_host' => env('OSS_BUCKET_HOST'),


    /*
    |--------------------------------------------------------------------------
    | 客户端上传回调配置
    |--------------------------------------------------------------------------
    |
    | 在客户端发起直传阿里云时，完成上传会回调本地服务器上指定接口，以下配置可以
    | 指定回调接口、回调数据及回调类型。
    |
    */
    'oss_callback_url' => env('OSS_CALLBACK_URL'),
    'oss_callback_body' => env('OSS_CALLBACK_BODY', 'bucket=${bucket}&filename=${object}&size=${size}&mimeType=${mimeType}'),
    'oss_callback_type' => env('OSS_CALLBACK_TYPE', 'application/x-www-form-urlencoded'),
    'oss_callback_expire' => env('OSS_CALLBACK_EXPIRE', 30),

];
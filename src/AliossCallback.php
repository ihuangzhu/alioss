<?php

namespace Ihuangzhu\Alioss;


use Illuminate\Support\Facades\Request;

class AliossCallback
{
    /**
     * 取得上传口令
     *
     * @param array $replacePairs 替换键值对
     * @param string $dir 存储目录
     * @return array
     */
    public static function getToken($replacePairs = [], $dir = 'default/')
    {
        // 基本配置
        $id = config('alioss.oss_key');
        $key = config('alioss.oss_secret');

        // 存储域
        $host = config('alioss.oss_bucket_host');

        // 回调配置
        $callbackUrl = config('alioss.oss_callback_url');
        $callbackBody = config('alioss.oss_callback_body');
        $callbackType = config('alioss.oss_callback_type');
        $callbackExpire = config('alioss.oss_callback_expire');

        // 替换回调参数
        $callbackBody = strtr($callbackBody, $replacePairs);

        // 格式化回调数据
        $base64CallbackBody = base64_encode(
            json_encode([
                'callbackUrl' => $callbackUrl,
                'callbackBody' => $callbackBody,
                'callbackBodyType' => $callbackType
            ])
        );

        // 设置口令有效时间
        // 设置该policy默认超时时间是30s. 即这个policy过了这个有效时间，将不能访问
        $end = time() + $callbackExpire;
        $expiration = Utils::gmtIso8601($end);

        // 设置限制条件
        $conditions = [
            // 限制上传文件大小，最多10MB
            ['content-length-range', 0, 1048576 * 10],

            // 表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
            ['starts-with', '$key', $dir]
        ];

        // 加密生成口令
        $policy = json_encode(['expiration' => $expiration, 'conditions' => $conditions]);
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $key, true));

        // 返回
        return [
            'accessId' => $id,
            'host' => $host,
            'policy' => $base64Policy,
            'signature' => $signature,
            'expire' => $end,
            'callback' => $base64CallbackBody,
            //这个参数是设置用户上传指定的前缀
            'dir' => $dir
        ];
    }

    /**
     * 回调是否合法
     *
     * @return bool
     */
    public static function checkCallback()
    {
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = Request::server('HTTP_AUTHORIZATION');
        $pubKeyUrlBase64 = Request::server('HTTP_X_OSS_PUB_KEY_URL');
        if (empty($authorizationBase64) || empty($pubKeyUrlBase64)) return false;

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if (empty($pubKey)) return false;

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $path = Request::server('REQUEST_URI');
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        return $ok === 1;
    }

}
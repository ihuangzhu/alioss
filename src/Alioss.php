<?php

namespace Ihuangzhu\Alioss;


use OSS\Core\OssException;
use OSS\OssClient;

class Alioss
{
    /**
     * @var string
     */
    private $bucket;

    /**
     * @var OssClient
     */
    private $ossClient;

    /**
     * Alioss constructor.
     * @param OssClient $ossClient
     * @param $bucket
     */
    public function __construct(OssClient $ossClient, $bucket)
    {
        $this->ossClient = $ossClient;
        $this->bucket = $bucket;
    }

    /**
     * 上传文本段落
     *
     * @param string $path 路径不能以“/”开头
     * @param string $contents
     * @return array|false
     */
    public function write($path, $contents)
    {
        return $this->ossClient->putObject($this->bucket, $path, $contents, [OssClient::OSS_CHECK_MD5 => true]);
    }

    /**
     * 上传文件
     *
     * @param string $path 路径不能以“/”开头
     * @param resource $resource
     * @return array|false
     */
    public function writeStream($path, $resource)
    {
        $content = stream_get_contents($resource);
        return $this->ossClient->putObject($this->bucket, $path, $content, [OssClient::OSS_CHECK_MD5 => true]);
    }

    /**
     * 拷贝指定文件
     *
     * @param string $path
     * @param string $newpath
     * @param string $newbucket
     * @return bool
     */
    public function copy($path, $newpath, $newbucket = null)
    {
        try {
            $newbucket = $newbucket ?: $this->bucket;
            $this->ossClient->copyObject($this->bucket, $path, $newbucket, $newpath);
            return true;
        } catch (OssException $e) {
            logger('Copy object fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 删除文件
     *
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        return $this->ossClient->deleteObject($this->bucket, $path);
    }

    /**
     * 文件是否存在
     *
     * @param string $path
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->ossClient->doesObjectExist($this->bucket, $path);
    }

    /**
     * 获取文件详情数据
     *
     * @param string $path
     * @return array|false
     */
    public function getMetadata($path)
    {
        return $this->ossClient->getObjectMeta($this->bucket, $path);
    }

    /**
     * 获取文件大小
     *
     * @param string $path
     * @return array|false
     */
    public function getSize($path)
    {
        $metadata = $this->getMetadata($path);
        if ($metadata === false) return false;
        return $metadata['content-length'];
    }

    /**
     * 获取文件mimetype
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        $metadata = $this->getMetadata($path);
        if ($metadata === false) return false;
        return $metadata['content-type'];
    }
}
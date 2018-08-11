<?php

namespace Ihuangzhu\Alioss;


use Illuminate\Filesystem\FilesystemAdapter;
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
     * 删除指定文件
     *
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        try {
            return $this->ossClient->deleteObject($this->bucket, $path);
        } catch (OssException $e) {
            logger('Delete object fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 创建文件夹
     *
     * @param string $dirname directory name
     * @return bool
     */
    public function createDir($dirname)
    {
        try {
            $this->ossClient->createObjectDir($this->bucket, $dirname);
            return true;
        } catch (OssException $e) {
            logger('Create dir fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 设置文件权限
     *
     * @param string $path
     * @param string $visibility
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        try {
            $acl = $visibility === FilesystemAdapter::VISIBILITY_PRIVATE ? OssClient::OSS_ACL_TYPE_PRIVATE : OssClient::OSS_ACL_TYPE_PUBLIC_READ;
            $this->ossClient->putObjectAcl($this->bucket, $path, $acl);
            return true;
        } catch (OssException $e) {
            logger('Set object visibility fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 检测文件是否存在
     *
     * @param string $path
     * @return bool
     */
    public function has($path)
    {
        try {
            return $this->ossClient->doesObjectExist($this->bucket, $path);
        } catch (OssException $e) {
            logger('Get object exist fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 读取文本段落
     *
     * @param string $path
     * @return string|false
     */
    public function read($path)
    {
        try {
            return $this->ossClient->getObject($this->bucket, $path);
        } catch (OssException $e) {
            logger('Get object read fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 读取文件
     *
     * @param string $path
     * @return array|false
     */
    public function readStream($path)
    {
        try {
            $contents = $this->ossClient->getObject($this->bucket, $path);
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $contents);
            rewind($stream);
            return $stream;
        } catch (OssException $e) {
            logger('Get object read steam fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取文件详细数据
     *
     * @param string $path
     * @return array|false
     */
    public function getMetadata($path)
    {
        try {
            return $this->ossClient->getObjectMeta($this->bucket, $path);
        } catch (OssException $e) {
            logger('Get object metadata fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取文件大小
     *
     * @param string $path
     * @return integer|false
     */
    public function getSize($path)
    {
        try {
            $metadata = $this->getMetadata($path);
            if ($metadata === false) return false;
            return $metadata['content-length'];
        } catch (OssException $e) {
            logger('Get object size fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取文件mimetype
     *
     * @param string $path
     * @return string|false
     */
    public function getMimetype($path)
    {
        try {
            $metadata = $this->getMetadata($path);
            if ($metadata === false) return false;
            return $metadata['content-type'];
        } catch (OssException $e) {
            logger('Get object mimetype fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取文件的上次修改时间
     *
     * @param string $path
     * @return integer|false
     */
    public function getTimestamp($path)
    {
        try {
            $metadata = $this->getMetadata($path);
            if ($metadata === false) return false;
            return strtotime($metadata['last-modified']);
        } catch (OssException $e) {
            logger('Get object timestamp fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取文件的权限
     *
     * @param string $path
     * @return string|bool
     */
    public function getVisibility($path)
    {
        try {
            $visibility = FilesystemAdapter::VISIBILITY_PRIVATE;
            $acl = $this->ossClient->getObjectAcl($this->bucket, $path);
            if ($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ) $visibility = FilesystemAdapter::VISIBILITY_PUBLIC;
            return $visibility;
        } catch (OssException $e) {
            logger('Get object visibility fail. Cause: ' . $e->getMessage());
        }
        return false;
    }
}
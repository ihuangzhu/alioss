<?php

namespace Ihuangzhu\Alioss\Adapter;


use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use OSS\Core\OssException;
use OSS\OssClient;

class AliossAdapter extends AbstractAdapter
{
    /**
     * @var array
     */
    private static $resultMap = [
        'Body'           => 'raw_contents',
        'Content-Length' => 'size',
        'ContentType'    => 'mimetype',
        'Size'           => 'size',
        'StorageClass'   => 'storage_class',
    ];

    /**
     * @var OssClient
     */
    private $ossClient;

    /**
     * @var string
     */
    private $bucket;

    /**
     * AliossAdapter constructor.
     *
     * @param OssClient $ossClient
     * @param string $bucket
     */
    public function __construct(OssClient $ossClient, $bucket)
    {
        $this->ossClient = $ossClient;
        $this->bucket = $bucket;
    }

    /**
     * 上传文本段落
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->ossClient->putObject($this->bucket, $path, $contents, [OssClient::OSS_CHECK_MD5 => true]);
    }

    /**
     * 上传文件
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $content = stream_get_contents($resource);
        return $this->ossClient->putObject($this->bucket, $path, $content, [OssClient::OSS_CHECK_MD5 => true]);
    }

    /**
     * 替换上传的文本段落
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * 替换上传的文件
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * 重命名指定文件
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function rename($path, $newpath)
    {
        if ($this->copy($path, $newpath)) {
            return $this->delete($path);
        }
        return false;
    }

    /**
     * 拷贝指定文件
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function copy($path, $newpath)
    {
        try {
            $this->ossClient->copyObject($this->bucket, $path, $this->bucket, $newpath);
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
        return $this->ossClient->deleteObject($this->bucket, $path);
    }

    /**
     * 删除指定文件夹
     *
     * @param string $dirname
     * @return bool
     */
    public function deleteDir($dirname)
    {
        try {
            $objects[] = $dirname;
            $listObjects = $this->listObjects($dirname, true);
            if (!empty($listObjects)) {
                foreach ($listObjects as $object) {
                    $objects[] = $object['key'];
                }
            }
            $this->ossClient->deleteObjects($this->bucket, $objects);
            return true;
        } catch (\Exception $e) {
            logger('Delete directory fail. Cause: ' . $e->getMessage());
        } catch (\null $e) {
            logger('Delete directory fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 列出Bucket内所有目录和文件， 根据返回的nextMarker循环调用listObjects接口得到所有文件和目录
     *
     * @param string $prefix 目录
     * @param bool $recursive 是否递归
     * @return null
     * @throws OssException
     */
    private function listObjects($prefix = '', $recursive = false)
    {
        $delimiter = '/';
        $nextMarker = '';
        $maxKeys = 30;
        $objects = [];
        while (true) {
            $options = array(
                'delimiter' => $delimiter,
                'prefix' => $prefix,
                'max-keys' => $maxKeys,
                'marker' => $nextMarker
            );
            $listObjectInfo = $this->ossClient->listObjects($this->bucket, $options);
            // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
            $nextMarker = $listObjectInfo->getNextMarker();
            // 文件列表
            $listObject = $listObjectInfo->getObjectList();
            if (!empty($listObject)) {
                foreach ($listObject as $objectInfo) {
                    $objects[] = [
                        'prefix' => $prefix,
                        'key' => $objectInfo->getKey(),
                        'lastModified' => $objectInfo->getLastModified(),
                        'eTag' => $objectInfo->getETag(),
                        'type' => $objectInfo->getType(),
                        'size' => $objectInfo->getSize(),
                        'storageClass' => $objectInfo->getStorageClass()
                    ];
                }
            }
            // 目录列表
            $listPrefix = $listObjectInfo->getPrefixList();
            if (!empty($listPrefix) && $recursive) {
                // 递归查询子目录所有文件
                foreach ($listPrefix as $prefixInfo) {
                    $nextObjects = $this->listObjects($prefixInfo->getPrefix(), $recursive);
                    $objects = array_merge($objects, $nextObjects);
                }
            }
            // 没有更多结果了
            if ($nextMarker === '') break;
        }
        return $objects;
    }

    /**
     * 创建文件夹
     *
     * @param string $dirname directory name
     * @param Config $config
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $this->ossClient->createObjectDir($this->bucket, $dirname);
        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * 设置文件权限
     *
     * @param string $path
     * @param string $visibility
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        try {
            $acl = $visibility === FilesystemAdapter::VISIBILITY_PRIVATE ? OssClient::OSS_ACL_TYPE_PRIVATE : OssClient::OSS_ACL_TYPE_PUBLIC_READ;
            return $this->ossClient->putObjectAcl($this->bucket, $path, $acl);
        } catch (OssException $e) {
            logger('Set object visibility fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 检测文件是否存在
     *
     * @param string $path
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->ossClient->doesObjectExist($this->bucket, $path);
    }

    /**
     * 读取文本段落
     *
     * @param string $path
     * @return array|false
     */
    public function read($path)
    {
        $contents = $this->ossClient->getObject($this->bucket, $path);
        return ['contents' => $contents];
    }

    /**
     * 读取文件
     *
     * @param string $path
     * @return array|false
     */
    public function readStream($path)
    {
        $contents = $this->ossClient->getObject($this->bucket, $path);
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        return ['stream' => $stream];
    }

    /**
     * 获取文件夹中的内容
     *
     * @param string $directory
     * @param bool $recursive
     * @return array|bool
     */
    public function listContents($directory = '', $recursive = false)
    {
        try {
            $dirObjects = $this->listObjects($directory, true);
            $result = array_map([$this, 'normalizeResponseOri'], $dirObjects);
            $result = array_filter($result, function ($value) {
                return $value['path'] !== false;
            });
            return Util::emulateDirectories($result);
        } catch (OssException $e) {
            logger('Get object list fail. Cause: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Normalize a result from AWS.
     *
     * @param array  $object
     * @param string $path
     *
     * @return array file metadata
     */
    protected function normalizeResponseOri(array $object, $path = null)
    {
        $result = ['path' => $path ?: $this->removePathPrefix(isset($object['key']) ? $object['key'] : $object['prefix'])];
        $result['dirname'] = Util::dirname($result['path']);
        if (isset($object['lastModified'])) {
            $result['timestamp'] = strtotime($object['lastModified']);
        }
        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');
            return $result;
        }
        $result = array_merge($result, Util::map($object, static::$resultMap), ['type' => 'file']);
        return $result;
    }

    /**
     * 获取文件详细数据
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
        return ['size' => $metadata['content-length']];
    }

    /**
     * 获取文件mimetype
     *
     * @param string $path
     * @return array|false
     */
    public function getMimetype($path)
    {
        $metadata = $this->getMetadata($path);
        if ($metadata === false) return false;
        return ['mimetype' => $metadata['content-type']];
    }

    /**
     * 获取文件的上次修改时间
     *
     * @param string $path
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $metadata = $this->getMetadata($path);
        if ($metadata === false) return false;
        return ['timestamp' => strtotime($metadata['last-modified'])];
    }

    /**
     * 获取文件的权限
     *
     * @param string $path
     * @return array|false
     */
    public function getVisibility($path)
    {
        try {
            $visibility = AdapterInterface::VISIBILITY_PRIVATE;
            $acl = $this->ossClient->getObjectAcl($this->bucket, $path);
            if ($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ) $visibility = AdapterInterface::VISIBILITY_PUBLIC;
            return ['visibility' => $visibility];
        } catch (OssException $e) {
            logger('Get object visibility fail. Cause: ' . $e->getMessage());
        }
        return false;
    }
}
<?php

namespace Ihuangzhu\Alioss;

use Ihuangzhu\Alioss\Adapter\AliossAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\Core\OssException;
use OSS\OssClient;

class AliossServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true; // 延迟加载服务

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/alioss.php' => config_path('alioss.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        try {
            // 基本配置
            $id = config('alioss.oss_key');
            $key = config('alioss.oss_secret');

            // 存储域
            $endpoint = config('alioss.oss_endpoint');
            $bucket = config('alioss.oss_bucket');

            // 创建OSS客户端
            $ossClient = new OssClient($id, $key, $endpoint);

            // 单例绑定服务
            $this->app->singleton('alioss', function () use ($ossClient, $bucket) {
                return new Alioss($ossClient, $bucket);
            });

            // 扩展文件管理
            Storage::extend('alioss', function () use ($ossClient, $bucket) {
                return new Filesystem(new AliossAdapter($ossClient, $bucket));
            });
        } catch (OssException $e) {
            logger('Alioss service init error: ' . $e->getMessage());
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['alioss'];
    }
}

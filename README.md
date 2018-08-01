# alioss
基于aliyuncs/oss-sdk-php项目上对Laravel框架的扩展

## 使用
    composer require ihuangzhu/alioss

## 配置
在 config/app.php 的 providers 中添加
```php
    Ihuangzhu\Alioss\AliossServiceProvider::class
``` 
在 aliases 配置别名
```php
    'Alioss' => Ihuangzhu\Alioss\Facades\Alioss::class
``` 

## 发布
    php artisan make:provider –provider="Ihuangzhu\Alioss\AliossServiceProvider"
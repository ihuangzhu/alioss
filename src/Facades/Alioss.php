<?php

namespace Ihuangzhu\Alioss\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ihuangzhu\Alioss\Alioss write(string $path, string $contents)
 * @method static \Ihuangzhu\Alioss\Alioss writeStream(string $path, resource $resource)
 * @method static \Ihuangzhu\Alioss\Alioss copy(string $path, string $newpath, string $newbucket = null)
 * @method static \Ihuangzhu\Alioss\Alioss delete(string $path)
 * @method static \Ihuangzhu\Alioss\Alioss has(string $path)
 * @method static \Ihuangzhu\Alioss\Alioss getMetadata(string $path)
 * @method static \Ihuangzhu\Alioss\Alioss getSize(string $path)
 * @method static \Ihuangzhu\Alioss\Alioss getMimetype(string $path)
 *
 * @see \Ihuangzhu\Alioss\Alioss
 */
class Alioss extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'alioss';
    }
}
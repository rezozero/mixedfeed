<?php


namespace RZ\MixedFeed\Env;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\PhpFileCache;

class CacheResolver
{
    /**
     * @return CacheProvider
     */
    public static function parseFromEnvironment()
    {
        $cacheDir = dirname(dirname(__DIR__)).'/var/cache';
        switch (getenv('MF_CACHE_PROVIDER')) {
            case 'apcu':
                return new ApcuCache();
            case 'file':
                return new FilesystemCache($cacheDir);
            case 'php':
                return new PhpFileCache($cacheDir);
            default:
                return new ArrayCache();
        }
    }
}

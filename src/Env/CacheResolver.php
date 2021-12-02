<?php

namespace RZ\MixedFeed\Env;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class CacheResolver
{
    public static function parseFromEnvironment(): CacheItemPoolInterface
    {
        $cacheDir = \dirname(\dirname(__DIR__)).'/var/cache';
        switch (\getenv('MF_CACHE_PROVIDER')) {
            case 'apcu':
                return new ApcuAdapter('mixedfeed');
            case 'file':
                return new FilesystemAdapter('mixedfeed', 0, $cacheDir);
            case 'php':
                return new PhpFilesAdapter('mixedfeed', 0, $cacheDir);
            default:
                return new ArrayAdapter();
        }
    }
}

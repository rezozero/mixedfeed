<?php

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use JMS\Serializer\SerializerBuilder;
use RZ\MixedFeed\Response\FeedItemResponse;
use Symfony\Component\Stopwatch\Stopwatch;

if (PHP_VERSION_ID < 70200) {
    echo 'Your PHP version is ' . phpversion() . "." . PHP_EOL;
    echo 'You need a least PHP version 7.2.0';
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$cache = new ArrayCache();
// $cache = new FilesystemCache(dirname(__FILE__).'/var/cache');
$feed = new \RZ\MixedFeed\MixedFeed([
    // Add some providers here
]);

$sw = new Stopwatch();
$sw->start('fetch');
header('Content-type: application/json');
header('X-Generator: rezozero/mixedfeed');
$serializer = SerializerBuilder::create()->build();
$feedItems = $feed->getAsyncCanonicalItems(20);
$event = $sw->stop('fetch');
$feedItemResponse = new FeedItemResponse($feedItems, [
    'time' => $event->getDuration(),
    'memory' => $event->getMemory(),
]);
$jsonContent = $serializer->serialize($feedItemResponse, 'json');
echo $jsonContent;

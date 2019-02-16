<?php
use JMS\Serializer\SerializerBuilder;
use RZ\MixedFeed\Response\FeedItemResponse;
use Symfony\Component\Stopwatch\Stopwatch;

require 'vendor/autoload.php';

$cache = new \Doctrine\Common\Cache\ArrayCache();
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

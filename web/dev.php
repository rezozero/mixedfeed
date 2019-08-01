<?php
use JMS\Serializer\SerializerBuilder;
use RZ\MixedFeed\Env\CacheResolver;
use RZ\MixedFeed\Env\ProviderResolver;
use RZ\MixedFeed\MixedFeed;
use RZ\MixedFeed\Response\FeedItemResponse;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Stopwatch\Stopwatch;

if (PHP_VERSION_ID < 70200) {
    echo 'Your PHP version is ' . phpversion() . "." . PHP_EOL;
    echo 'You need a least PHP version 7.2.0';
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->load(
        dirname(__DIR__).'/.env',
        dirname(__DIR__).'/.env.dev'
    );
}
try {
    $sw = new Stopwatch();
    $sw->start('fetch');
    $feed = new MixedFeed(ProviderResolver::parseFromEnvironment(CacheResolver::parseFromEnvironment()));
    header('Content-type: application/json');
    header('X-Generator: rezozero/mixedfeed');
    $serializer = SerializerBuilder::create()->build();
    $feedItems = $feed->getAsyncCanonicalItems((int) getenv('MF_FEED_LENGTH'));
    $event = $sw->stop('fetch');
    $feedItemResponse = new FeedItemResponse($feedItems, [
        'time' => $event->getDuration(),
        'memory' => $event->getMemory(),
        'count' => count($feedItems),
    ]);
    echo $serializer->serialize($feedItemResponse, 'json');
} catch (\RuntimeException $exception) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo json_encode([
        'message' => $exception->getMessage(),
    ]);
}

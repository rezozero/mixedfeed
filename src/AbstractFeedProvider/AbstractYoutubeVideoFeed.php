<?php

namespace RZ\MixedFeed\AbstractFeedProvider;

use DateTime;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use stdClass;

abstract class AbstractYoutubeVideoFeed extends AbstractFeedProvider
{
    public const YOUTUBE_URL_FORMAT = 'https://www.youtube.com/watch?v=%s';

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @throws CredentialsException
     */
    public function __construct(string $apiKey, ?CacheItemPoolInterface $cacheProvider = null)
    {
        parent::__construct($cacheProvider);

        $this->apiKey = $apiKey;

        if (empty($this->apiKey)) {
            throw new CredentialsException('YoutubeVideoFeed needs a valid apiKey.', 1);
        }
    }

    protected function getFeed(int $count = 5)
    {
        $rawFeed = $this->getCachedRawFeed($count);
        if ($this->isValid($rawFeed)) {
            return $rawFeed->items;
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): ?DateTime
    {
        if (isset($item->snippet->publishedAt)) {
            return new DateTime($item->snippet->publishedAt);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        if (isset($item->snippet->title)) {
            return $item->snippet->title;
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setLink(\sprintf(self::YOUTUBE_URL_FORMAT, $item->id));
        $feedItem->setTitle($item->snippet->title);
        $feedItem->setMessage($item->snippet->description);
        $feedItem->setAuthor($item->snippet->channelTitle);

        if (isset($item->snippet->tags)) {
            $feedItem->setTags($item->snippet->tags);
        }

        if (isset($item->snippet) && isset($item->snippet->thumbnails->maxres)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->snippet->thumbnails->maxres->url);
            $feedItemImage->setWidth($item->snippet->thumbnails->maxres->width);
            $feedItemImage->setHeight($item->snippet->thumbnails->maxres->height);
            $feedItem->addImage($feedItemImage);
        }

        return $feedItem;
    }
}

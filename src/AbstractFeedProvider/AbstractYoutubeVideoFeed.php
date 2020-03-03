<?php
declare(strict_types=1);

namespace RZ\MixedFeed\AbstractFeedProvider;

use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;

abstract class AbstractYoutubeVideoFeed extends AbstractFeedProvider
{
    protected $apiKey;

    /**
     * YoutubeVideoFeed constructor.
     *
     * @param string             $apiKey
     * @param CacheProvider|null $cacheProvider
     *
     * @throws CredentialsException
     */
    public function __construct(string $apiKey, CacheProvider $cacheProvider = null)
    {
        parent::__construct($cacheProvider);

        $this->apiKey = $apiKey;

        if (null === $this->apiKey ||
            false === $this->apiKey ||
            empty($this->apiKey)) {
            throw new CredentialsException("YoutubeVideoFeed needs a valid apiKey.", 1);
        }
    }

    protected function getFeed($count = 5)
    {
        $rawFeed = $this->getRawFeed($count);
        if ($this->isValid($rawFeed)) {
            return $rawFeed->items;
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        if (isset($item->snippet->publishedAt)) {
            return new \DateTime($item->snippet->publishedAt);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (isset($item->snippet->title)) {
            return $item->snippet->title;
        }

        return "";
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setLink('https://www.youtube.com/watch?v=' . $item->id);
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

<?php

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

/**
 * Get an Instagram user feed.
 *
 * @deprecated Use GraphInstagramFeed to comply with new Facebook API policy
 */
class InstagramFeed extends AbstractFeedProvider
{
    protected string $userId;
    protected string $accessToken;

    /**
     * InstagramFeed constructor.
     *
     * @throws CredentialsException
     */
    public function __construct(string $userId, string $accessToken, ?CacheItemPoolInterface $cacheProvider = null)
    {
        parent::__construct($cacheProvider);
        $this->userId = $userId;
        $this->accessToken = $accessToken;

        if (empty($this->accessToken)) {
            throw new CredentialsException('InstagramFeed needs a valid access token.', 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->userId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'access_token' => $this->accessToken,
            'count'        => $count,
        ], '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://api.instagram.com/v1/users/' . $this->userId . '/media/recent/?' . $value
        );
    }

    protected function getFeed(int $count = 5)
    {
        $rawFeed = $this->getCachedRawFeed($count);
        if ($this->isValid($rawFeed)) {
            return $rawFeed->data;
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed): bool
    {
        if (\count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), \implode(', ', $this->errors));
        }

        return isset($feed->data) && \is_iterable($feed->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): DateTime
    {
        return new DateTime('@' . $item->created_time);
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        if (null !== $item->caption) {
            return $item->caption->text;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'instagram';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->user->full_name);
        $feedItem->setLink($item->link);
        if (isset($item->like_count)) {
            $feedItem->setLikeCount($item->like_count);
        }
        $feedItemImage = new Image();
        $feedItemImage->setUrl($item->images->standard_resolution->url);
        $feedItemImage->setWidth($item->images->standard_resolution->width);
        $feedItemImage->setHeight($item->images->standard_resolution->height);
        $feedItem->addImage($feedItemImage);

        return $feedItem;
    }
}

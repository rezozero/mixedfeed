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

use const PHP_QUERY_RFC3986;

/**
 * Get an Instagram user feed from Facebook Graph API.
 */
class GraphInstagramFeed extends AbstractFeedProvider
{
    public const TYPE_IMAGE = 'IMAGE';
    public const TYPE_VIDEO = 'VIDEO';
    public const TYPE_CAROUSEL_ALBUM = 'CAROUSEL_ALBUM';

    protected string $userId;

    protected string $accessToken;

    /** @var string[] */
    protected array $fields;

    /**
     * GraphInstagramFeed constructor.
     *
     * @param string[] $fields
     *
     * @throws CredentialsException
     */
    public function __construct(
        string $userId,
        string $accessToken,
        ?CacheItemPoolInterface $cacheProvider = null,
        array $fields = []
    ) {
        parent::__construct($cacheProvider);

        $this->userId = $userId;
        $this->accessToken = $accessToken;

        $this->fields = \count($fields) > 0
            ? $fields
            : [
                'id',
                'username',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'timestamp',
                'permalink',
                'like_count',
                'comments_count',
            ];

        if (empty($this->accessToken)) {
            throw new CredentialsException('GraphInstagramFeed needs a valid access token.', 1);
        }
    }

    /** @inheritDoc */
    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'fields'       => \implode(',', $this->fields),
            'access_token' => $this->accessToken,
            'limit'        => $count,
        ], '', '&', PHP_QUERY_RFC3986);

        yield new Request('GET', 'https://graph.instagram.com/' . $this->userId . '/media?' . $value);
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->userId;
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
        return new DateTime($item->timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        if (null !== $item->caption) {
            return $item->caption;
        }

        return '';
    }

    public function getFeedPlatform(): string
    {
        return 'instagram';
    }

    /** @inheritDoc */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->username);
        $feedItem->setLink($item->permalink);

        if (isset($item->like_count)) {
            $feedItem->setLikeCount($item->like_count);
        }

        if (isset($item->comments_count)) {
            $feedItem->setShareCount($item->comments_count);
        }

        if (self::TYPE_VIDEO === $item->media_type && !empty($item->thumbnail_url)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->thumbnail_url);
            $feedItem->addImage($feedItemImage);
        } else {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->media_url);
            $feedItem->addImage($feedItemImage);
        }

        return $feedItem;
    }
}

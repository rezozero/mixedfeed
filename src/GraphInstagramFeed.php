<?php
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get an Instagram user feed from Facebook Graph API
 */
class GraphInstagramFeed extends AbstractFeedProvider
{
    const TYPE_IMAGE = 'IMAGE';
    const TYPE_VIDEO = 'VIDEO';
    const TYPE_CAROUSEL_ALBUM = 'CAROUSEL_ALBUM';

    /**
     * @var string
     */
    protected $userId;
    /**
     * @var string
     */
    protected $accessToken;
    /**
     * @var array
     */
    private $fields;

    /**
     * GraphInstagramFeed constructor.
     *
     * @param string             $userId
     * @param string             $accessToken
     * @param CacheProvider|null $cacheProvider
     * @param array              $fields
     *
     * @throws CredentialsException
     */
    public function __construct($userId, $accessToken, CacheProvider $cacheProvider = null, array $fields = [])
    {
        parent::__construct($cacheProvider);
        $this->userId = $userId;
        $this->accessToken = $accessToken;
        if (count($fields) > 0) {
            $this->fields = $fields;
        } else {
            $this->fields = [
                'id',
                'username',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'timestamp',
                'permalink',
            ];
        }

        if (null === $this->accessToken ||
            false === $this->accessToken ||
            empty($this->accessToken)) {
            throw new CredentialsException("GraphInstagramFeed needs a valid access token.", 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->userId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests($count = 5): \Generator
    {
        $value = http_build_query([
            'fields' => implode(',', $this->fields),
            'access_token' => $this->accessToken,
            'limit' => $count,
        ], null, '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://graph.instagram.com/' . $this->userId . '/media?'.$value
        );
    }

    protected function getFeed($count = 5)
    {
        $rawFeed = $this->getRawFeed($count);
        if ($this->isValid($rawFeed)) {
            return $rawFeed->data;
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        if (count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), implode(', ', $this->errors));
        }
        return isset($feed->data) && is_iterable($feed->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        return new \DateTime($item->timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (null !== $item->caption) {
            return $item->caption;
        }

        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'instagram';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
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

        if ($item->media_type === static::TYPE_VIDEO && !empty($item->thumbnail_url)) {
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

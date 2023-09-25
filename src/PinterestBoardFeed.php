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
 * Get a Pinterest public board pins feed.
 *
 * https://developers.pinterest.com/tools/access_token/
 */
class PinterestBoardFeed extends AbstractFeedProvider
{
    protected string $boardId;
    protected string $accessToken;

    /**
     * @param string $accessToken Your App Token
     *
     * @throws CredentialsException
     */
    public function __construct(
        string $boardId,
        string $accessToken,
        ?CacheItemPoolInterface $cacheProvider = null
    ) {
        parent::__construct($cacheProvider);
        $this->boardId = $boardId;
        $this->accessToken = $accessToken;

        if (empty($this->accessToken)) {
            throw new CredentialsException('PinterestBoardFeed needs a valid access token.', 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->boardId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'access_token' => $this->accessToken,
            'limit'        => $count,
            'fields'       => 'id,color,created_at,creator,media,image[original],note,link,url',
        ], '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://api.pinterest.com/v1/boards/' . $this->boardId . '/pins?' . $value
        );
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
     * @return mixed
     *
     * @throws FeedProviderErrorException
     */
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
    public function getDateTime($item): DateTime
    {
        return new DateTime($item->created_at);
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        return $item->note;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'pinterest_pin';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->creator->first_name . ' ' . $item->creator->last_name);
        $feedItem->setLink($item->url);

        if (isset($item->image)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->image->original->url);
            $feedItemImage->setWidth($item->image->original->width);
            $feedItemImage->setHeight($item->image->original->height);
            $feedItem->addImage($feedItemImage);
        }

        return $feedItem;
    }
}

<?php

namespace RZ\MixedFeed;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

/**
 * Implements a basic feed provider with
 * platform name and DateTime injection.
 */
abstract class AbstractFeedProvider implements FeedProviderInterface
{
    protected ?int $ttl = 7200;

    /** @var mixed|null */
    protected $rawFeed = null;

    protected ?CacheItemPoolInterface $cacheProvider;

    /** @var string[] */
    protected array $errors = [];

    abstract protected function getCacheKey(): string;

    /**
     * AbstractFeedProvider constructor.
     */
    public function __construct(?CacheItemPoolInterface $cacheProvider = null)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function addError(string $reason): FeedProviderInterface
    {
        $this->errors[] = $reason;

        return $this;
    }

    public function isCacheHit(int $count = 5): bool
    {
        if (!$this->cacheProvider) {
            return false;
        }

        $countKey = $this->getCacheKey().$count;

        return $this->cacheProvider->getItem($countKey)->isHit();
    }

    /** @param string $rawFeed */
    public function setRawFeed(string $rawFeed): AbstractFeedProvider
    {
        $this->rawFeed = Utils::jsonDecode($rawFeed);

        return $this;
    }

    /** @return mixed */
    protected function getFeed(int $count = 5)
    {
        $rawFeed = $this->getCachedRawFeed($count);

        if ($this->isValid($rawFeed)) {
            return $rawFeed;
        }

        return [];
    }

    /**
     * @return mixed
     *
     * @throws FeedProviderErrorException
     */
    protected function getRawFeed(int $count = 5)
    {
        if (null !== $this->rawFeed) {
            return $this->rawFeed;
        }

        try {
            $client = new Client([
                'http_errors' => true,
            ]);
            $response = $client->send($this->getRequests($count)->current());

            return Utils::jsonDecode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e, );
        }
    }

    /**
     * @return mixed
     *
     * @throws FeedProviderErrorException
     */
    protected function getCachedRawFeed(int $count = 5)
    {
        if (!$this->cacheProvider) {
            return $this->getRawFeed($count);
        }

        $countKey = $this->getCacheKey().$count;
        $item = $this->cacheProvider->getItem($countKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $feed = $this->getRawFeed($count);
        $item->set($feed);
        $item->expiresAfter($this->ttl);

        $this->cacheProvider->save($item);

        return $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalItems(int $count = 5): array
    {
        $items = [];

        foreach ($this->getFeed($count) as $item) {
            if ($item instanceof stdClass) {
                $items[] = $this->createFeedItemFromObject($item);
            }
        }

        return $items;
    }

    /**
     * Gets the value of ttl.
     */
    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    /**
     * Sets the value of ttl.
     *
     * @param int $ttl the ttl
     */
    public function setTtl(?int $ttl): AbstractFeedProvider
    {
        $this->ttl = $ttl;

        return $this;
    }

    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = new FeedItem();
        $feedItem->setRaw($item);
        $feedItem->setDateTime($this->getDateTime($item));
        $feedItem->setMessage($this->getCanonicalMessage($item));
        $feedItem->setPlatform($this->getFeedPlatform());

        return $feedItem;
    }

    public function supportsRequestPool(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed): bool
    {
        if (\count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), \implode(', ', $this->errors));
        }

        return null !== $feed && \is_iterable($feed->items);
    }
}

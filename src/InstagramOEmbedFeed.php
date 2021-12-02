<?php

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils as GuzzleUtils;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

class InstagramOEmbedFeed extends AbstractFeedProvider
{
    protected array $embedUrls;

    /**
     * InstagramOEmbedFeed constructor.
     *
     * @param string[]|array $embedUrls
     */
    public function __construct(array $embedUrls, ?CacheItemPoolInterface $cacheProvider = null)
    {
        parent::__construct($cacheProvider);
        foreach ($embedUrls as $i => $url) {
            if (0 === \preg_match('#^https?:\/\/www\.instagram\.com\/p\/#', $url)) {
                $embedUrls[$i] = 'https://www.instagram.com/p/'.$url;
            }
        }
        $this->embedUrls = $embedUrls;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform().\serialize($this->embedUrls);
    }

    public function getRequests(int $count = 5): Generator
    {
        foreach ($this->embedUrls as $embedUrl) {
            $value = \http_build_query([
                'url' => $embedUrl,
            ], '', '&', PHP_QUERY_RFC3986);
            yield new Request(
                'GET',
                'https://api.instagram.com/oembed?'.$value
            );
        }
    }

    /**
     * @param mixed $rawFeed
     * @param bool  $json
     */
    public function setRawFeed($rawFeed, $json = true): AbstractFeedProvider
    {
        if (null === $this->rawFeed) {
            $this->rawFeed = [];
        }
        if (true === $json && \is_string($rawFeed)) {
            GuzzleUtils::jsonDecode($rawFeed, true);
        }
        \array_push($this->rawFeed, $rawFeed);

        return $this;
    }

    /**
     * @return array
     *
     * @throws FeedProviderErrorException
     */
    protected function getRawFeed(int $count = 5)
    {
        if (null !== $this->rawFeed) {
            return $this->rawFeed;
        }

        try {
            $body = [];
            $promises = [];
            $client = new \GuzzleHttp\Client();
            foreach ($this->getRequests($count) as $request) {
                // Initiate each request but do not block
                $promises[] = $client->sendAsync($request);
            }
            /** @var array $responses */
            $responses = Promise\Utils::settle($promises)->wait();

            foreach ($responses as $response) {
                if ('rejected' !== $response['state']) {
                    \array_push($body, GuzzleUtils::jsonDecode($response['value']->getBody()->getContents()));
                } else {
                    throw $response['reason'];
                }
            }

            return $body;
        } catch (ClientException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'instagram_oembed';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed): bool
    {
        if (\count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), \implode(', ', $this->errors));
        }
        // OEmbed response is not iterable because there is only one item
        return null !== $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): ?DateTime
    {
        if (false !== \preg_match('#datetime=\\"([^"]+)\\"#', $item->html, $matches)) {
            return new DateTime('@'.$matches[1]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        if (isset($item->title)) {
            return $item->title;
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->media_id);
        $feedItem->setAuthor($item->author_name);
        $feedItem->setLink($item->author_url);
        $feedItemImage = new Image();
        $feedItemImage->setUrl($item->thumbnail_url);
        $feedItemImage->setWidth($item->thumbnail_width);
        $feedItemImage->setHeight($item->thumbnail_height);
        $feedItem->addImage($feedItemImage);

        return $feedItem;
    }
}

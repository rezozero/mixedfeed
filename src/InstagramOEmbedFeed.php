<?php

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
use RZ\MixedFeed\Canonical\Image;

class InstagramOEmbedFeed extends AbstractFeedProvider
{
    /**
     * @var array
     */
    protected $embedUrls;
    /**
     * @var CacheProvider|null
     */
    protected $cacheProvider;
    /**
     * @var string
     */
    private $cacheKey;

    /**
     * InstagramOEmbedFeed constructor.
     *
     * @param                    $embedUrls
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct($embedUrls, CacheProvider $cacheProvider = null)
    {
        $this->cacheProvider = $cacheProvider;
        $this->cacheKey = $this->getFeedPlatform() . serialize($embedUrls);
        $this->embedUrls = $embedUrls;
    }

    protected function getFeed($count = 5)
    {
        try {
            $countKey = $this->cacheKey . $count;
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }
            $body = [];

            foreach ($this->embedUrls as $embedUrl) {
                $client = new \GuzzleHttp\Client();
                $response = $client->get('https://api.instagram.com/oembed', [
                    'query' => [
                        'url' => $embedUrl,
                    ],
                ]);
                array_push($body, json_decode($response->getBody()));
            }
            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body,
                    $this->ttl
                );
            }

            return $body;
        } catch (ClientException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'instagram_oembed';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        if (false !== preg_match("#datetime=\\\"([^\"]+)\\\"#", $item->html, $matches)) {
            return new \DateTime($matches[1]);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (isset($item->title)) {
            return $item->title;
        }

        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        return null !== $feed && is_array($feed) && !isset($feed['error']);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        return $feed['error'];
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item)
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

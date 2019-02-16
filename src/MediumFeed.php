<?php

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;

class MediumFeed extends AbstractFeedProvider
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $name;

    /**
     * MediumFeed constructor.
     *
     * @param string        $username
     * @param CacheProvider $cacheProvider
     */
    public function __construct($username, CacheProvider $cacheProvider = null)
    {
        parent::__construct($cacheProvider);
        $this->username = $username;
        if (substr($username, 0, 1) !== '@') {
            $this->username = '@'.$username;
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getRequests($count = 5): \Generator
    {
        $value = http_build_query([
            'format' => 'json',
            'limit' => $count,
            'collectionId' => null,
            'source' => 'latest',
        ], null, '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://medium.com/' . $this->username . '/latest?'.$value
        );
    }

    /**
     * @inheritDoc
     */
    public function setRawFeed($rawFeed, $json = true): AbstractFeedProvider
    {
        if ($json === true) {
            $rawFeed = str_replace('])}while(1);</x>', '', $rawFeed);
            $rawFeed = json_decode($rawFeed);
            if ('No error' !== $jsonError = json_last_error_msg()) {
                throw new \RuntimeException($jsonError);
            }
        }
        $this->rawFeed = $rawFeed;
        return $this;
    }


    protected function getRawFeed($count = 5)
    {
        $countKey = $this->getCacheKey() . $count;

        if (null !== $this->rawFeed) {
            if (null !== $this->cacheProvider &&
                !$this->cacheProvider->contains($countKey)) {
                $this->cacheProvider->save(
                    $countKey,
                    $this->rawFeed,
                    $this->ttl
                );
            }
            return $this->rawFeed;
        }
        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->send($this->getRequests($count)->current());
            $raw = $response->getBody()->getContents();
            $raw = str_replace('])}while(1);</x>', '', $raw);
            $body = json_decode($raw);
            if ('No error' !== $jsonError = json_last_error_msg()) {
                throw new \RuntimeException($jsonError);
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
     * @inheritDoc
     */
    protected function getFeed($count = 5): array
    {
        return $this->getTypedFeed($this->getRawFeed($count));
    }


    /**
     * @param $body
     *
     * @return array
     */
    protected function getTypedFeed($body)
    {
        $feed = [];
        if (isset($body->payload->user)) {
            $this->name = $body->payload->user->name;
        }
        foreach ($body->payload->streamItems as $item) {
            if ($item->itemType === 'postPreview') {
                $id = $item->postPreview->postId;
                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item->createdAt);

                if (isset($body->payload->references->Post->$id)) {
                    $feed[] = $body->payload->references->Post->$id;
                }
            }
        }

        return $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'medium';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        $createdAt = new \DateTime();
        $createdAt->setTimestamp($item->latestPublishedAt/1000);
        return $createdAt;
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
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->uniqueSlug);
        $feedItem->setAuthor($this->name);
        $feedItem->setLink('https://medium.com/'.$this->username.'/'.$item->uniqueSlug);
        $feedItem->setTitle($item->title);
        if (isset($item->content) && isset($item->content->subtitle)) {
            $feedItem->setMessage($item->content->subtitle);
        }

        if (isset($item->previewContent->bodyModel->paragraphs)) {
            foreach ($item->previewContent->bodyModel->paragraphs as $paragraph) {
                /*
                 * 4 seems to be an image type
                 */
                if ($paragraph->type === 4 && isset($paragraph->metadata)) {
                    $feedItemImage = new Image();
                    $feedItemImage->setUrl('https://miro.medium.com/' . $paragraph->metadata->id);
                    $feedItemImage->setWidth($paragraph->metadata->originalWidth);
                    $feedItemImage->setHeight($paragraph->metadata->originalHeight);
                    $feedItem->addImage($feedItemImage);
                }
            }
        }
        return $feedItem;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

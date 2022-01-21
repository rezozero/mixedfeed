<?php

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

class MediumFeed extends AbstractFeedProvider
{
    /**
     * @var null
     */
    protected ?string $userId;

    private string $username;

    private string $name;

    private string $url;

    private bool $useLatestPublicationDate = false;

    /**
     * @param string $username
     * @param null   $userId
     */
    public function __construct($username, ?CacheItemPoolInterface $cacheProvider = null, ?string $userId = null)
    {
        parent::__construct($cacheProvider);
        $this->username = $username;
        $this->cacheProvider = $cacheProvider;
        $this->userId = $userId;

        if (null !== $this->userId) {
            /*
             * If userId is available, use the profile/stream endpoint instead for better consistency
             * between calls.
             */
            $this->url = 'https://medium.com/_/api/users/' . $this->userId . '/profile/stream';
        } else {
            $this->url = 'https://medium.com/' . $this->username . '/latest';
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'format'       => 'json',
            'limit'        => $count,
            'collectionId' => null,
            'source'       => 'latest',
        ], '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            $this->url . '?' . $value
        );
    }

    /**
     * @inheritDoc
     */
    public function setRawFeed(string $rawFeed): AbstractFeedProvider
    {
        $rawFeed = \str_replace('])}while(1);</x>', '', $rawFeed);
        $rawFeed = Utils::jsonDecode($rawFeed, true);
        $this->rawFeed = $rawFeed;

        return $this;
    }

    protected function getRawFeed(int $count = 5)
    {
        if (null !== $this->rawFeed) {
            return $this->rawFeed;
        }

        try {
            $client = new Client();
            $response = $client->send($this->getRequests($count)->current());
            $raw = $response->getBody()->getContents();
            $raw = \str_replace('])}while(1);</x>', '', $raw);
            $body = Utils::jsonDecode($raw);

            return $body;
        } catch (ClientException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getFeed(int $count = 5): array
    {
        return $this->getTypedFeed($this->getCachedRawFeed($count));
    }

    /**
     * @param object $body
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getTypedFeed($body)
    {
        $feed = [];
        if (!isset($body->payload)) {
            return $feed;
        }
        if (isset($body->payload->user)) {
            $this->name = $body->payload->user->name;
        }
        foreach ($body->payload->streamItems as $item) {
            if ('postPreview' === $item->itemType) {
                $id = $item->postPreview->postId;
                $createdAt = new DateTime();
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
    public function getFeedPlatform(): string
    {
        return 'medium';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): ?DateTime
    {
        $createdAt = $this->isUsingLatestPublicationDate() ? $item->latestPublishedAt / 1000 : $item->firstPublishedAt / 1000;

        return new DateTime('@' . $createdAt);
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
        $feedItem->setId($item->uniqueSlug);
        $feedItem->setAuthor($this->name);
        $feedItem->setLink('https://medium.com/' . $this->username . '/' . $item->uniqueSlug);
        $feedItem->setTitle($item->title);
        $feedItem->setTags($item->virtuals->tags);
        if (isset($item->content) && isset($item->content->subtitle)) {
            $feedItem->setMessage($item->content->subtitle);
        }

        if (isset($item->previewContent->bodyModel->paragraphs)) {
            foreach ($item->previewContent->bodyModel->paragraphs as $paragraph) {
                /*
                 * 4 seems to be an image type
                 */
                if (4 === $paragraph->type && isset($paragraph->metadata)) {
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

    public function isUsingLatestPublicationDate(): bool
    {
        return $this->useLatestPublicationDate;
    }

    public function setUseLatestPublicationDate(bool $useLatestPublicationDate): MediumFeed
    {
        $this->useLatestPublicationDate = $useLatestPublicationDate;

        return $this;
    }
}

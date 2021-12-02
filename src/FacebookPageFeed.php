<?php

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use stdClass;

/**
 * Get a Facebook public page timeline feed using an App Token.
 *
 * https://developers.facebook.com/docs/facebook-login/access-tokens
 */
class FacebookPageFeed extends AbstractFeedProvider
{
    protected string $pageId;
    protected string $accessToken;
    /** @var string[] */
    protected array $fields;

    protected string $apiBaseUrl = 'https://graph.facebook.com/v3.3/';

    protected ?DateTime $since = null;
    protected ?DateTime $until = null;

    /**
     * @param string   $accessToken Your App Token
     * @param string[] $fields
     *
     * @throws CredentialsException
     */
    public function __construct(
        string $pageId,
        string $accessToken,
        ?CacheItemPoolInterface $cacheProvider = null,
        array $fields = [],
        ?string $apiBaseUrl = null
    ) {
        parent::__construct($cacheProvider);
        $this->pageId = $pageId;
        $this->accessToken = $accessToken;
        $this->fields = [
            'from',
            'picture',
            'full_picture',
            'message',
            'story',
            'created_time',
            'status_type',
            'message_tags',
            'shares',
            'permalink_url',
        ];
        $this->fields = \array_unique(\array_merge($this->fields, $fields));
        $this->apiBaseUrl = $apiBaseUrl ?: $this->apiBaseUrl;

        if (empty($this->accessToken)) {
            throw new CredentialsException('FacebookPageFeed needs a valid App access token.', 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform().$this->pageId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests(int $count = 5): Generator
    {
        $params = [
            'access_token' => $this->accessToken,
            'limit'        => $count,
            'fields'       => \implode(',', $this->fields),
        ];
        /*
         * Filter by date range
         */
        if (
            null !== $this->since &&
            $this->since instanceof DateTime
        ) {
            $params['since'] = $this->since->getTimestamp();
        }
        if (
            null !== $this->until &&
            $this->until instanceof DateTime
        ) {
            $params['until'] = $this->until->getTimestamp();
        }
        $value = \http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            $this->apiBaseUrl.$this->pageId.'/posts?'.$value
        );
    }

    protected function getFeed(int $count = 5)
    {
        $rawFeed = $this->getCachedRawFeed($count);
        if (\is_array($rawFeed) && isset($rawFeed['error'])) {
            return $rawFeed;
        }

        return $rawFeed->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): ?DateTime
    {
        return new DateTime('@'.\strtotime($item->created_time));
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        return isset($item->message) ? $item->message : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'facebook_page';
    }

    /**
     * Gets the value of since.
     */
    public function getSince(): ?DateTime
    {
        return $this->since;
    }

    /**
     * Sets the value of since.
     *
     * @param DateTime $since the since
     */
    public function setSince(?DateTime $since): AbstractFeedProvider
    {
        $this->since = $since;

        return $this;
    }

    /**
     * Gets the value of until.
     */
    public function getUntil(): ?DateTime
    {
        return $this->until;
    }

    /**
     * Sets the value of until.
     *
     * @param DateTime $until the until
     */
    public function setUntil(?DateTime $until): AbstractFeedProvider
    {
        $this->until = $until;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        if (isset($item->from)) {
            $feedItem->setAuthor($item->from->name);
        }
        if (isset($item->link)) {
            $feedItem->setLink($item->link);
        }
        if (isset($item->permalink_url)) {
            $feedItem->setLink($item->permalink_url);
        }

        if (isset($item->shares)) {
            $feedItem->setShareCount($item->shares->count);
        }

        if (isset($item->message_tags)) {
            $feedItem->setTags(\array_map(function ($messageTag) {
                return $messageTag->name;
            }, $item->message_tags));
        }

        if (isset($item->full_picture)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->full_picture);
            $feedItem->addImage($feedItemImage);
        }

        return $feedItem;
    }

    /** @return string[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string[] $fields
     *
     * @return FacebookPageFeed
     */
    public function setFields(array $fields)
    {
        $this->fields = \array_unique($fields);

        return $this;
    }
}

<?php

namespace RZ\MixedFeed\AbstractFeedProvider;

use Abraham\TwitterOAuth\TwitterOAuth;
use DateTime;
use Generator;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\AbstractFeedProvider as BaseFeedProvider;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

/**
 * Get a Twitter tweets abstract feed.
 */
abstract class AbstractTwitterFeed extends BaseFeedProvider
{
    /**
     * Shorter TTL for Twitter - 5 min.
     */
    protected ?int $ttl = 60 * 5;

    protected string $accessToken;

    protected TwitterOAuth $twitterConnection;

    /**
     * @throws CredentialsException
     */
    public function __construct(
        string $consumerKey,
        string $consumerSecret,
        string $accessToken,
        string $accessTokenSecret,
        ?CacheItemPoolInterface $cacheProvider = null
    ) {
        parent::__construct($cacheProvider);
        $this->accessToken = $accessToken;

        if (empty($accessToken)) {
            throw new CredentialsException('TwitterSearchFeed needs a valid access token.', 1);
        }
        if (empty($accessTokenSecret)) {
            throw new CredentialsException('TwitterSearchFeed needs a valid access token secret.', 1);
        }
        if (empty($consumerKey)) {
            throw new CredentialsException('TwitterSearchFeed needs a valid consumer key.', 1);
        }
        if (empty($consumerSecret)) {
            throw new CredentialsException('TwitterSearchFeed needs a valid consumer secret.', 1);
        }

        $this->twitterConnection = new TwitterOAuth(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): DateTime
    {
        return new DateTime('@' . \strtotime($item->created_at));
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        if (isset($item->text)) {
            return $item->text;
        }

        return $item->full_text;
    }

    public function getRequests(int $count = 5): Generator
    {
        throw new \RuntimeException('Twitter cannot be used in async mode');
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed): bool
    {
        if (\count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), \implode(', ', $this->errors));
        }

        return null !== $feed && \is_array($feed);
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId((string) $item->id);
        $feedItem->setAuthor($item->user->name);
        /**
         * @see https://dev.to/twitterdev/getting-to-the-canonical-url-for-a-tweet-85e
         */
        $feedItem->setLink(sprintf('https://twitter.com/%s/status/%d', $item->user->screen_name, $item->id));

        if (isset($item->retweet_count)) {
            $feedItem->setShareCount($item->retweet_count);
        }
        if (isset($item->favorite_count)) {
            $feedItem->setLikeCount($item->favorite_count);
        }

        if (isset($item->entities->hashtags)) {
            foreach ($item->entities->hashtags as $hashtag) {
                $feedItem->setTags(\array_merge($feedItem->getTags(), [
                    $hashtag->text,
                ]));
            }
        }

        if (isset($item->entities->media)) {
            foreach ($item->entities->media as $media) {
                if (!in_array($media->type, ['photo', 'animated_gif'], true)) {
                    continue;
                }
                $feedItemImage = new Image();
                $feedItemImage->setUrl($media->media_url_https);
                $feedItemImage->setWidth($media->sizes->large->w);
                $feedItemImage->setHeight($media->sizes->large->h);
                $feedItem->addImage($feedItemImage);
            }
        }

        return $feedItem;
    }

    /**
     * @inheritDoc
     */
    public function supportsRequestPool(): bool
    {
        return false;
    }
}

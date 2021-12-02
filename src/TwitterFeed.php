<?php

namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Twitter user timeline feed.
 */
class TwitterFeed extends AbstractTwitterFeed
{
    protected string $userId;
    protected string $accessToken;
    protected TwitterOAuth $twitterConnection;
    protected bool $excludeReplies;
    protected bool $includeRts;

    protected bool $extended;

    /**
     * @throws Exception\CredentialsException
     */
    public function __construct(
        string $userId,
        string $consumerKey,
        string $consumerSecret,
        string $accessToken,
        string $accessTokenSecret,
        ?CacheItemPoolInterface $cacheProvider = null,
        bool $excludeReplies = true,
        bool $includeRts = false,
        bool $extended = true
    ) {
        parent::__construct(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret,
            $cacheProvider
        );

        $this->userId = $userId;
        $this->excludeReplies = $excludeReplies;
        $this->includeRts = $includeRts;
        $this->extended = $extended;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform().$this->userId;
    }

    protected function getRawFeed(int $count = 5)
    {
        try {
            $body = $this->twitterConnection->get('statuses/user_timeline', [
                'user_id'         => $this->userId,
                'count'           => $count,
                'exclude_replies' => $this->excludeReplies,
                'include_rts'     => $this->includeRts,
                'tweet_mode'      => ($this->extended ? 'extended' : ''),
            ]);

            return $body;
        } catch (TwitterOAuthException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }
}

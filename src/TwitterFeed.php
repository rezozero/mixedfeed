<?php
namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuthException;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Twitter user timeline feed.
 */
class TwitterFeed extends AbstractTwitterFeed
{
    protected $userId;
    protected $accessToken;
    protected $twitterConnection;
    protected $excludeReplies;
    protected $includeRts;

    protected static $timeKey = 'created_at';
    /**
     * @var bool
     */
    protected $extended;

    /**
     * @param string             $userId
     * @param string             $consumerKey
     * @param string             $consumerSecret
     * @param string             $accessToken
     * @param string             $accessTokenSecret
     * @param CacheProvider|null $cacheProvider
     * @param boolean            $excludeReplies
     * @param boolean            $includeRts
     * @param bool               $extended
     *
     * @throws Exception\CredentialsException
     */
    public function __construct(
        $userId,
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        CacheProvider $cacheProvider = null,
        $excludeReplies = true,
        $includeRts = false,
        $extended = true
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
        return $this->getFeedPlatform() . $this->userId;
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->getCacheKey() . $count;

        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }
            $body = $this->twitterConnection->get("statuses/user_timeline", [
                "user_id" => $this->userId,
                "count" => $count,
                "exclude_replies" => $this->excludeReplies,
                'include_rts' => $this->includeRts,
                'tweet_mode' =>  ($this->extended ? 'extended' : '')
            ]);
            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body,
                    $this->ttl
                );
            }
            return $body;
        } catch (TwitterOAuthException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }
}

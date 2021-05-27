<?php
namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuthException;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Twitter search tweets feed.
 */
class TwitterSearchFeed extends AbstractTwitterFeed
{
    protected $queryParams;

    /**
     * @var bool
     */
    protected $includeRetweets = true;

    /**
     * @var bool
     */
    protected $extended = false;

    /**
     * @var string
     */
    protected $resultType = 'mixed';

    /**
     * @var string
     */
    protected static $timeKey = 'created_at';

    /**
     * @param array              $queryParams
     * @param string             $consumerKey
     * @param string             $consumerSecret
     * @param string             $accessToken
     * @param string             $accessTokenSecret
     * @param CacheProvider|null $cacheProvider
     * @param bool               $extended
     *
     * @throws Exception\CredentialsException
     */
    public function __construct(
        array $queryParams,
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        CacheProvider $cacheProvider = null,
        $extended = true
    ) {
        parent::__construct(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret,
            $cacheProvider
        );

        $this->queryParams = array_filter($queryParams);
        $this->extended = $extended;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . md5(serialize($this->queryParams));
    }

    /**
     * @return string
     */
    protected function formatQueryParams()
    {
        $inlineParams = [];
        foreach ($this->queryParams as $key => $value) {
            if (is_numeric($key)) {
                $inlineParams[] = $value;
            } else {
                $inlineParams[] = $key . ':' . $value;
            }
        }

        return implode(' ', $inlineParams);
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->getCacheKey() . $count;

        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            if ($this->includeRetweets === false) {
                $this->queryParams['-filter'] = 'retweets';
            }

            $params = [
                "q" => $this->formatQueryParams(),
                "count" => $count,
                "result_type" => $this->resultType,
            ];
            if ($this->extended) {
                $params['tweet_mode'] = 'extended';
            }

            /** @var object $body */
            $body = $this->twitterConnection->get("search/tweets", $params);

            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body->statuses,
                    $this->ttl
                );
            }
            return $body->statuses;
        } catch (TwitterOAuthException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }

    /**
     * @return bool
     */
    public function isIncludeRetweets()
    {
        return $this->includeRetweets;
    }

    /**
     * @param bool $includeRetweets
     * @return TwitterSearchFeed
     */
    public function setIncludeRetweets($includeRetweets)
    {
        $this->includeRetweets = $includeRetweets;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultType()
    {
        return $this->resultType;
    }

    /**
     * Optional. Specifies what type of search results you would prefer to receive. The current default is “mixed.” Valid values include:
     * mixed : Include both popular and real time results in the response.
     * recent : return only the most recent results in the response
     * popular : return only the most popular results in the response.
     *
     * @param string $resultType
     * @return TwitterSearchFeed
     */
    public function setResultType($resultType)
    {
        $this->resultType = $resultType;
        return $this;
    }
}

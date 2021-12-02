<?php

namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuthException;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Twitter search tweets feed.
 */
class TwitterSearchFeed extends AbstractTwitterFeed
{
    /** @var string[] */
    protected array $queryParams;

    protected bool $includeRetweets = true;

    protected bool $extended = false;

    protected string $resultType = 'mixed';

    /**
     * @throws Exception\CredentialsException
     */
    public function __construct(
        array $queryParams,
        string $consumerKey,
        string $consumerSecret,
        string $accessToken,
        string $accessTokenSecret,
        ?CacheItemPoolInterface $cacheProvider = null,
        bool $extended = true
    ) {
        parent::__construct(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret,
            $cacheProvider
        );

        $this->queryParams = \array_filter($queryParams);
        $this->extended = $extended;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform().\md5(\serialize($this->queryParams));
    }

    protected function formatQueryParams(): string
    {
        $inlineParams = [];
        foreach ($this->queryParams as $key => $value) {
            if (\is_numeric($key)) {
                $inlineParams[] = $value;
            } else {
                $inlineParams[] = $key.':'.$value;
            }
        }

        return \implode(' ', $inlineParams);
    }

    protected function getRawFeed(int $count = 5)
    {
        try {
            if (false === $this->includeRetweets) {
                $this->queryParams['-filter'] = 'retweets';
            }

            $params = [
                'q'           => $this->formatQueryParams(),
                'count'       => $count,
                'result_type' => $this->resultType,
            ];
            if ($this->extended) {
                $params['tweet_mode'] = 'extended';
            }

            /** @var object $body */
            $body = $this->twitterConnection->get('search/tweets', $params);

            return isset($body->statuses) ? $body->statuses : [];
        } catch (TwitterOAuthException $e) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), $e->getMessage(), $e);
        }
    }

    public function isIncludeRetweets(): bool
    {
        return $this->includeRetweets;
    }

    /**
     * @return TwitterSearchFeed
     */
    public function setIncludeRetweets(bool $includeRetweets): AbstractFeedProvider
    {
        $this->includeRetweets = $includeRetweets;

        return $this;
    }

    public function getResultType(): string
    {
        return $this->resultType;
    }

    /**
     * Optional. Specifies what type of search results you would prefer to receive. The current default is “mixed.” Valid values include:
     * mixed : Include both popular and real time results in the response.
     * recent : return only the most recent results in the response
     * popular : return only the most popular results in the response.
     *
     * @return TwitterSearchFeed
     */
    public function setResultType(string $resultType): AbstractFeedProvider
    {
        $this->resultType = $resultType;

        return $this;
    }
}

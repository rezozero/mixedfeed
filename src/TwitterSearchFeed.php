<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file TwitterSearchFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuthException;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;

/**
 * Get a Twitter search tweets feed.
 */
class TwitterSearchFeed extends AbstractTwitterFeed
{
    protected $cacheKey;
    protected $queryParams;

    protected static $timeKey = 'created_at';

    /**
     *
     * @param array              $queryParams
     * @param string             $consumerKey
     * @param string             $consumerSecret
     * @param string             $accessToken
     * @param string             $accessTokenSecret
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        array $queryParams,
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        CacheProvider $cacheProvider = null
    ) {
        parent::__construct(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret,
            $cacheProvider
        );

        $this->queryParams = array_filter($queryParams);
        $this->cacheKey = $this->getFeedPlatform() . md5(serialize($queryParams));
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
        $countKey = $this->cacheKey . $count;

        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            $body = $this->twitterConnection->get("search/tweets", [
                "q" => $this->formatQueryParams(),
                "count" => $count,
            ]);

            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body->statuses,
                    $this->ttl
                );
            }

            return $body->statuses;
        } catch (TwitterOAuthException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

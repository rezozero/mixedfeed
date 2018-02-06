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
 * @file TwitterFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Abraham\TwitterOAuth\TwitterOAuthException;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider\AbstractTwitterFeed;

/**
 * Get a Twitter user timeline feed.
 */
class TwitterFeed extends AbstractTwitterFeed
{
    protected $userId;
    protected $accessToken;
    protected $cacheProvider;
    protected $cacheKey;
    protected $twitterConnection;
    protected $excludeReplies;
    protected $includeRts;

    protected static $timeKey = 'created_at';

    /**
     *
     * @param string             $userId
     * @param string             $consumerKey
     * @param string             $consumerSecret
     * @param string             $accessToken
     * @param string             $accessTokenSecret
     * @param CacheProvider|null $cacheProvider
     * @param boolean            $excludeReplies
     * @param boolean            $includeRts
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
        $extended = false
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
        $this->cacheKey = $this->getFeedPlatform() . $this->userId;
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->cacheKey . $count;

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
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

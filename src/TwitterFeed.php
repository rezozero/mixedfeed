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

use Abraham\TwitterOAuth\TwitterOAuth;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Exception\CredentialsException;

/**
 * Get a Twitter user timeline feed.
 */
class TwitterFeed extends AbstractFeedProvider
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
        $includeRts = false
    ) {
        $this->userId = $userId;
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;
        $this->excludeReplies = $excludeReplies;
        $this->includeRts = $includeRts;
        $this->cacheKey = $this->getFeedPlatform() . $this->userId;

        if (null === $accessToken ||
            false === $accessToken ||
            empty($accessToken)) {
            throw new CredentialsException("TwitterFeed needs a valid access token.", 1);
        }
        if (null === $accessTokenSecret ||
            false === $accessTokenSecret ||
            empty($accessTokenSecret)) {
            throw new CredentialsException("TwitterFeed needs a valid access token secret.", 1);
        }
        if (null === $consumerKey ||
            false === $consumerKey ||
            empty($consumerKey)) {
            throw new CredentialsException("TwitterFeed needs a valid consumer key.", 1);
        }
        if (null === $consumerSecret ||
            false === $consumerSecret ||
            empty($consumerSecret)) {
            throw new CredentialsException("TwitterFeed needs a valid consumer secret.", 1);
        }

        $this->twitterConnection = new TwitterOAuth(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret
        );
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->cacheKey . $count;

        if (null !== $this->cacheProvider &&
            $this->cacheProvider->contains($countKey)) {
            return $this->cacheProvider->fetch($countKey);
        }

        $body = $this->twitterConnection->get("statuses/user_timeline", [
            "user_id" => $this->userId,
            "count" => $count,
            "exclude_replies" => $this->excludeReplies,
            'include_rts' => $this->includeRts,
        ]);
        if (null !== $this->cacheProvider) {
            $this->cacheProvider->save(
                $countKey,
                $body,
                7200
            );
        }

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        $date = new \DateTime();
        $date->setTimestamp(strtotime($item->created_at));
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        return null !== $feed && (null === $feed->errors || empty($feed->errors));
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        $errors = "";

        if (null !== $feed && null !== $feed->errors && !empty($feed->errors)) {
            foreach ($feed->errors as $error) {
                $errors .= "[" . $error->code . "] ";
                $errors .= $error->message . PHP_EOL;
            }
        }

        return $errors;
    }
}

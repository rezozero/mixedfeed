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
 * @file AbstractTwitterFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed\AbstractFeedProvider;

use Abraham\TwitterOAuth\TwitterOAuth;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\AbstractFeedProvider as BaseFeedProvider;

/**
 * Get a Twitter tweets abstract feed.
 */
class AbstractTwitterFeed extends BaseFeedProvider
{
    protected $accessToken;
    protected $cacheProvider;
    protected $twitterConnection;

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
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        CacheProvider $cacheProvider = null
    ) {
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;

        if (null === $accessToken ||
            false === $accessToken ||
            empty($accessToken)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid access token.", 1);
        }
        if (null === $accessTokenSecret ||
            false === $accessTokenSecret ||
            empty($accessTokenSecret)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid access token secret.", 1);
        }
        if (null === $consumerKey ||
            false === $consumerKey ||
            empty($consumerKey)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid consumer key.", 1);
        }
        if (null === $consumerSecret ||
            false === $consumerSecret ||
            empty($consumerSecret)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid consumer secret.", 1);
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
    public function getDateTime($item)
    {
        $date = new \DateTime();
        $date->setTimestamp(strtotime($item->created_at));
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (isset($item->text)) {
            return $item->text;
        }

        return $item->full_text;
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
        return null !== $feed && is_array($feed);
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

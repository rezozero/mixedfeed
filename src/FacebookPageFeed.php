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
 * @file FacebookPageFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider;

/**
 * Get a Facebook public page timeline feed using an App Token.
 *
 * https://developers.facebook.com/docs/facebook-login/access-tokens
 */
class FacebookPageFeed extends AbstractFeedProvider
{
    protected $pageId;
    protected $accessToken;
    protected $cacheProvider;
    protected $cacheKey;

    protected static $timeKey = 'created_time';

    /**
     *
     * @param string             $pageId
     * @param string             $accessToken Your App Token
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        $pageId,
        $accessToken,
        CacheProvider $cacheProvider = null
    ) {
        $this->pageId = $pageId;
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;
        $this->cacheKey = $this->getFeedPlatform() . $this->pageId;
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->cacheKey . $count;

        if (null !== $this->cacheProvider &&
            $this->cacheProvider->contains($countKey)) {
            return $this->cacheProvider->fetch($countKey);
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://graph.facebook.com/' . $this->pageId . '/posts', [
            'query' => [
                'access_token' => $this->accessToken,
                'limit' => $count,
            ],
        ]);
        $body = json_decode($response->getBody());

        if (null !== $this->cacheProvider) {
            $this->cacheProvider->save(
                $countKey,
                $body->data,
                7200
            );
        }

        return $body->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        $date = new \DateTime();
        $date->setTimestamp(strtotime($item->created_time));
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'facebook_page';
    }
}

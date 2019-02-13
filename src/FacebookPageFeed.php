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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;

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
    protected $fields;
    /**
     * @var \DateTime|null
     */
    protected $since = null;
    /**
     * @var \DateTime|null
     */
    protected $until = null;

    protected static $timeKey = 'created_time';

    /**
     *
     * @param string $pageId
     * @param string $accessToken Your App Token
     * @param CacheProvider|null $cacheProvider
     * @param array $fields
     * @throws CredentialsException
     */
    public function __construct(
        $pageId,
        $accessToken,
        CacheProvider $cacheProvider = null,
        $fields = []
    ) {
        $this->pageId = $pageId;
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;
        $this->cacheKey = $this->getFeedPlatform() . $this->pageId;

        $this->fields = ['from', 'link', 'picture', 'full_picture', 'message', 'story', 'type', 'created_time', 'source', 'status_type'];
        $this->fields = array_unique(array_merge($this->fields, $fields));

        if (null === $this->accessToken ||
            false === $this->accessToken ||
            empty($this->accessToken)) {
            throw new CredentialsException("FacebookPageFeed needs a valid App access token.", 1);
        }
    }

    protected function getFeed($count = 5)
    {
        try {
            $countKey = $this->cacheKey . $count;

            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            $client = new Client();
            $params = [
                'query' => [
                    'access_token' => $this->accessToken,
                    'limit' => $count,
                    'fields' => implode(',', $this->fields),
                ],
            ];
            /*
             * Filter by date range
             */
            if (null !== $this->since &&
                $this->since instanceof \Datetime) {
                $params['query']['since'] = $this->since->getTimestamp();
            }
            if (null !== $this->until &&
                $this->until instanceof \Datetime) {
                $params['query']['until'] = $this->until->getTimestamp();
            }

            $response = $client->get('https://graph.facebook.com/' . $this->pageId . '/posts', $params);
            $body = json_decode($response->getBody());

            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body->data,
                    $this->ttl
                );
            }

            return $body->data;
        } catch (ClientException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
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
    public function getCanonicalMessage($item)
    {
        return isset($item->message) ? $item->message : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'facebook_page';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        return null !== $feed && is_array($feed) && !isset($feed['error']);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        return $feed['error'];
    }

    /**
     * Gets the value of since.
     *
     * @return \Datetime
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * Sets the value of since.
     *
     * @param \Datetime $since the since
     *
     * @return self
     */
    public function setSince(\Datetime $since)
    {
        $this->since = $since;

        return $this;
    }

    /**
     * Gets the value of until.
     *
     * @return \Datetime
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * Sets the value of until.
     *
     * @param \Datetime $until the until
     *
     * @return self
     */
    public function setUntil(\Datetime $until)
    {
        $this->until = $until;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item)
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        if (isset($item->from)) {
            $feedItem->setAuthor($item->from->name);
        }
        if (isset($item->link)) {
            $feedItem->setLink($item->link);
        }

        if (isset($item->full_picture)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->full_picture);
            $feedItem->addImage($feedItemImage);
        }

        return $feedItem;
    }
}

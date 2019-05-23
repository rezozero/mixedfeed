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
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Canonical\FeedItem;
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
        parent::__construct($cacheProvider);
        $this->pageId = $pageId;
        $this->accessToken = $accessToken;
        $this->fields = ['from', 'link', 'picture', 'full_picture', 'message', 'story', 'type', 'created_time', 'source', 'status_type'];
        $this->fields = array_unique(array_merge($this->fields, $fields));

        if (null === $this->accessToken ||
            false === $this->accessToken ||
            empty($this->accessToken)) {
            throw new CredentialsException("FacebookPageFeed needs a valid App access token.", 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->pageId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests($count = 5): \Generator
    {
        $params = [
            'access_token' => $this->accessToken,
            'limit' => $count,
            'fields' => implode(',', $this->fields),
        ];
        /*
         * Filter by date range
         */
        if (null !== $this->since &&
            $this->since instanceof \Datetime) {
            $params['since'] = $this->since->getTimestamp();
        }
        if (null !== $this->until &&
            $this->until instanceof \Datetime) {
            $params['until'] = $this->until->getTimestamp();
        }
        $value = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://graph.facebook.com/' . $this->pageId . '/posts?'.$value
        );
    }

    protected function getFeed($count = 5)
    {
        $rawFeed = $this->getRawFeed($count);
        if (is_array($rawFeed) && isset($rawFeed['error'])) {
            return $rawFeed;
        }
        return $rawFeed->data;
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
    protected function createFeedItemFromObject($item): FeedItem
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

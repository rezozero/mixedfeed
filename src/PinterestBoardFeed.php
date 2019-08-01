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
 * @file PinterestBoardFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Pinterest public board pins feed.
 *
 * https://developers.pinterest.com/tools/access_token/
 */
class PinterestBoardFeed extends AbstractFeedProvider
{
    protected $boardId;
    protected $accessToken;
    protected static $timeKey = 'created_at';

    /**
     *
     * @param string $boardId
     * @param string $accessToken Your App Token
     * @param CacheProvider|null $cacheProvider
     * @throws CredentialsException
     */
    public function __construct(
        $boardId,
        $accessToken,
        CacheProvider $cacheProvider = null
    ) {
        parent::__construct($cacheProvider);
        $this->boardId = $boardId;
        $this->accessToken = $accessToken;

        if (null === $this->accessToken ||
            false === $this->accessToken ||
            empty($this->accessToken)) {
            throw new CredentialsException("PinterestBoardFeed needs a valid access token.", 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->boardId;
    }

    /**
     * @inheritDoc
     */
    public function getRequests($count = 5): \Generator
    {
        $value = http_build_query([
            'access_token' => $this->accessToken,
            'limit' => $count,
            'fields' => 'id,color,created_at,creator,media,image[original],note,link,url',
        ], null, '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://api.pinterest.com/v1/boards/' . $this->boardId . '/pins?'.$value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        if (count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), implode(', ', $this->errors));
        }
        return isset($feed->data) && is_iterable($feed->data);
    }

    /**
     * @param int $count
     * @return mixed
     */
    protected function getFeed($count = 5)
    {
        $rawFeed = $this->getRawFeed($count);
        if ($this->isValid($rawFeed)) {
            return $rawFeed->data;
        }
        return [];
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
        return $item->note;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'pinterest_pin';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->creator->first_name . ' ' . $item->creator->last_name);
        $feedItem->setLink($item->url);

        if (isset($item->image)) {
            $feedItemImage = new Image();
            $feedItemImage->setUrl($item->image->original->url);
            $feedItemImage->setWidth($item->image->original->width);
            $feedItemImage->setHeight($item->image->original->height);
            $feedItem->addImage($feedItemImage);
        }
        return $feedItem;
    }
}

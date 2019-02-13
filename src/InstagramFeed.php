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
 * @file InstagramFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;

/**
 * Get an Instagram user feed.
 */
class InstagramFeed extends AbstractFeedProvider
{
    protected $userId;
    protected $accessToken;
    protected $cacheProvider;
    protected $cacheKey;
    protected static $timeKey = 'created_time';

    public function __construct($userId, $accessToken, CacheProvider $cacheProvider = null)
    {
        $this->userId = $userId;
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;
        $this->cacheKey = $this->getFeedPlatform() . $this->userId;

        if (null === $this->accessToken ||
            false === $this->accessToken ||
            empty($this->accessToken)) {
            throw new CredentialsException("InstagramFeed needs a valid access token.", 1);
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

            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.instagram.com/v1/users/' . $this->userId . '/media/recent/', [
                'query' => [
                    'access_token' => $this->accessToken,
                    'count' => $count,
                ],
            ]);
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
        $date->setTimestamp($item->created_time);
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (null !== $item->caption) {
            return $item->caption->text;
        }

        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'instagram';
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
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item)
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->user->full_name);
        $feedItem->setLink($item->link);
        $feedItemImage = new Image();
        $feedItemImage->setUrl($item->images->standard_resolution->url);
        $feedItemImage->setWidth($item->images->standard_resolution->width);
        $feedItemImage->setHeight($item->images->standard_resolution->height);
        $feedItem->addImage($feedItemImage);
        return $feedItem;
    }
}

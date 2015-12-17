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
 * @file GithubReleasesFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Exception\CredentialsException;

/**
 * Get a github repository releases feed.
 */
class GithubReleasesFeed extends AbstractFeedProvider
{
    protected $repository;
    protected $accessToken;
    protected $cacheProvider;
    protected $cacheKey;
    protected $page;

    protected static $timeKey = 'created_at';

    /**
     *
     * @param string             $repository
     * @param string             $accessToken
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        $repository,
        $accessToken,
        CacheProvider $cacheProvider = null,
        $page = 1
    ) {
        $this->repository = $repository;
        $this->accessToken = $accessToken;
        $this->cacheProvider = $cacheProvider;
        $this->page = $page;
        $this->cacheKey = $this->getFeedPlatform() . $this->repository . $this->page;

        if (null === $repository ||
            false === $repository ||
            empty($repository)) {
            throw new CredentialsException("GithubReleasesFeed needs a valid repository name.", 1);
        }

        if (0 === preg_match('#([a-zA-Z\-\_0-9\.]+)/([a-zA-Z\-\_0-9\.]+)#', $repository)) {
            throw new CredentialsException("GithubReleasesFeed needs a valid repository name “user/project”.", 1);
        }

        if (null === $accessToken ||
            false === $accessToken ||
            empty($accessToken)) {
            throw new CredentialsException("GithubReleasesFeed needs a valid access token.", 1);
        }
    }

    protected function getFeed($count = 5)
    {
        $countKey = $this->cacheKey . $count;

        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.github.com/repos/' . $this->repository . '/releases', [
                'query' => [
                    'access_token' => $this->accessToken,
                    'per_page' => $count,
                    'token_type' => 'bearer',
                    'page' => $this->page,
                ],
            ]);
            $body = json_decode($response->getBody());

            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body,
                    $this->ttl
                );
            }
            return $body;
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
        $date->setTimestamp(strtotime($item->created_at));
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        return $item->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'github_release';
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
        $errors = "";

        if (null !== $feed && null !== $feed['error'] && !empty($feed['error'])) {
            $errors .= $feed['error'];
        }

        return $errors;
    }
}

<?php

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
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
     * @param int                $page
     *
     * @throws CredentialsException
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

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item)
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id);
        $feedItem->setAuthor($item->author->login);
        $feedItem->setLink($item->html_url);
        $feedItem->setTitle($item->name);
        $feedItem->setMessage($item->body);
        return $feedItem;
    }
}

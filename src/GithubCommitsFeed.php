<?php

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\CredentialsException;
use stdClass;

/**
 * Get a github repository commits feed.
 */
class GithubCommitsFeed extends AbstractFeedProvider
{
    protected string $repository;
    protected string $accessToken;
    protected int $page;

    /**
     * @throws CredentialsException
     */
    public function __construct(
        string $repository,
        string $accessToken,
        ?CacheItemPoolInterface $cacheProvider = null,
        int $page = 1
    ) {
        parent::__construct($cacheProvider);
        $this->repository = $repository;
        $this->accessToken = $accessToken;
        $this->page = $page;

        if (empty($repository)) {
            throw new CredentialsException('GithubCommitsFeed needs a valid repository name.', 1);
        }

        if (0 === \preg_match('#([a-zA-Z\-\_0-9\.]+)/([a-zA-Z\-\_0-9\.]+)#', $repository)) {
            throw new CredentialsException('GithubCommitsFeed needs a valid repository name “user/project”.', 1);
        }

        if (empty($accessToken)) {
            throw new CredentialsException('GithubCommitsFeed needs a valid access token.', 1);
        }
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform().$this->repository.$this->page;
    }

    /**
     * @inheritDoc
     */
    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'access_token' => $this->accessToken,
            'per_page'     => $count,
            'token_type'   => 'bearer',
            'page'         => $this->page,
        ], '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://api.github.com/repos/'.$this->repository.'/commits?'.$value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item): ?DateTime
    {
        return new DateTime('@'.\strtotime($item->commit->author->date));
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage(stdClass $item): string
    {
        return $item->commit->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'github_commit';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->sha);
        $feedItem->setAuthor($item->commit->author->name);
        $feedItem->setLink($item->html_url);

        return $feedItem;
    }
}

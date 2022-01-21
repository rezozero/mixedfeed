<?php

namespace RZ\MixedFeed;

use Generator;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\AbstractFeedProvider\AbstractYoutubeVideoFeed;
use RZ\MixedFeed\Canonical\FeedItem;
use stdClass;

class YoutubePlaylistItemFeed extends AbstractYoutubeVideoFeed
{
    /**
     * @var string
     */
    protected $playlistId;

    /**
     * @throws Exception\CredentialsException
     */
    public function __construct(string $playlistId, string $apiKey, ?CacheItemPoolInterface $cacheProvider = null)
    {
        parent::__construct($apiKey, $cacheProvider);

        $this->playlistId = $playlistId;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . \serialize($this->playlistId);
    }

    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'part'       => 'snippet,contentDetails',
            'key'        => $this->apiKey,
            'playlistId' => $this->playlistId,
            'maxResults' => $count,
        ]);
        yield new Request(
            'GET',
            'https://www.googleapis.com/youtube/v3/playlistItems?' . $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform(): string
    {
        return 'youtube_playlist_items';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject(stdClass $item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->snippet->resourceId->videoId);
        $feedItem->setLink('https://www.youtube.com/watch?v=' . $item->snippet->resourceId->videoId);

        return $feedItem;
    }
}

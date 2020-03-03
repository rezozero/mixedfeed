<?php
declare(strict_types=1);

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\AbstractFeedProvider\AbstractYoutubeVideoFeed;
use RZ\MixedFeed\Canonical\FeedItem;

class YoutubePlaylistItemFeed extends AbstractYoutubeVideoFeed
{
    /**
     * @var string
     */
    protected $playlistId;

    /**
     * YoutubePlaylistItemFeed constructor.
     *
     * @param string             $playlistId
     * @param string             $apiKey
     * @param CacheProvider|null $cacheProvider
     *
     * @throws Exception\CredentialsException
     */
    public function __construct(string $playlistId, string $apiKey, CacheProvider $cacheProvider = null)
    {
        parent::__construct($apiKey, $cacheProvider);

        $this->playlistId = $playlistId;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . serialize($this->playlistId);
    }

    /**
     * @param int $count
     *
     * @return \Generator
     */
    public function getRequests($count = 5): \Generator
    {
        $value = http_build_query([
            'part' => 'snippet,contentDetails',
            'key' => $this->apiKey,
            'playlistId' => $this->playlistId,
            'maxResults' => $count,
        ]);
        yield new Request(
            'GET',
            'https://www.googleapis.com/youtube/v3/playlistItems?'.$value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'youtube_playlist_items';
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->snippet->resourceId->videoId);
        $feedItem->setLink('https://www.youtube.com/watch?v=' . $item->snippet->resourceId->videoId);

        return $feedItem;
    }
}

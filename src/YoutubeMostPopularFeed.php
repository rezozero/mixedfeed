<?php

namespace RZ\MixedFeed;

use Generator;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\AbstractFeedProvider\AbstractYoutubeVideoFeed;

class YoutubeMostPopularFeed extends AbstractYoutubeVideoFeed
{
    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . \serialize($this->apiKey);
    }

    public function getRequests(int $count = 5): Generator
    {
        $value = \http_build_query([
            'part'       => 'snippet,contentDetails',
            'key'        => $this->apiKey,
            'chart'      => 'mostPopular',
            'maxResults' => $count,
        ]);

        yield new Request('GET', 'https://www.googleapis.com/youtube/v3/videos?' . $value);
    }

    public function getFeedPlatform(): string
    {
        return 'youtube_video';
    }
}

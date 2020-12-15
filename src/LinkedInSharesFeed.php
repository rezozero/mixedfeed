<?php
declare(strict_types=1);

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Psr7\Request;

/**
 * @see https://docs.microsoft.com/fr-fr/linkedin/marketing/integrations/community-management/shares/share-api#sample-response
 * @package RZ\MixedFeed
 */
class LinkedInSharesFeed extends AbstractFeedProvider
{
    /**
     * @var string
     */
    protected $owner;
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @param string $owner urn:li:organization:{xxxxxx}
     * @param string $accessToken
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(string $owner, string $accessToken, CacheProvider $cacheProvider = null)
    {
        parent::__construct($cacheProvider);
        $this->owner = $owner;
        $this->accessToken = $accessToken;
    }

    protected function getCacheKey(): string
    {
        return $this->getFeedPlatform() . $this->owner;
    }

    /**
     * @inheritDoc
     */
    public function getRequests($count = 5): \Generator
    {
        // TODO: Implement getRequests() method.
        // GET https://api.linkedin.com/v2/shares?q=owners&owners={URN}&sortBy=LAST_MODIFIED&sharesPerOwner=100
        $query = http_build_query([
            'q' => 'owners',
            'owners' => $this->owner,
            'sortBy' => 'LAST_MODIFIED',
            'sharesPerOwner' => $count,
        ], '', '&', PHP_QUERY_RFC3986);
        yield new Request(
            'GET',
            'https://api.linkedin.com/v2/shares?'.$query,
            [
                'Authorization' => 'Bearer ' . trim($this->accessToken)
            ]
        );
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getFeedPlatform()
    {
        return 'linkedin_shares';
    }

    /**
     * @inheritDoc
     */
    public function getDateTime($item)
    {
        return (new \DateTime())->setTimestamp($item->lastModified['time']);
    }

    /**
     * @inheritDoc
     */
    public function getCanonicalMessage($item)
    {
        return $item->content['title'];
    }
}
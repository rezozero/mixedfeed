<?php

namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;

class MediumFeed extends AbstractFeedProvider
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $cacheKey;
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * MediumFeed constructor.
     *
     * @param string        $username
     * @param CacheProvider $cacheProvider
     */
    public function __construct($username, CacheProvider $cacheProvider = null)
    {
        $this->username = $username;
        if (substr($username, 0, 1) !== '@') {
            $this->username = '@'.$username;
        }
        $this->cacheProvider = $cacheProvider;
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
            $response = $client->get('https://medium.com/' . $this->username . '/latest', [
                'query' => [
                    'format' => 'json',
                    'limit' => $count,
                    'collectionId' => null,
                    'source' => 'latest',
                ],
            ]);
            $raw = $response->getBody();
            $raw = str_replace('])}while(1);</x>', '', $raw);
            $body = json_decode($raw);
            $body = $this->getTypedFeed($body);

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
     * @param $body
     *
     * @return array
     */
    protected function getTypedFeed($body)
    {
        $feed = [];
        foreach ($body->payload->streamItems as $item) {
            if ($item->itemType === 'postPreview') {
                $id = $item->postPreview->postId;
                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item->createdAt);

                if (isset($body->payload->references->Post->$id)) {
                    $feed[] = $body->payload->references->Post->$id;
                }
            }
        }

        return $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'medium';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        $createdAt = new \DateTime();
        $createdAt->setTimestamp($item->latestPublishedAt/1000);
        return $createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (isset($item->title)) {
            return $item->title;
        }

        return "";
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
}

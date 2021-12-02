<?php

namespace RZ\MixedFeed;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Combine feed providers and sort them by date and time, descending or ascending.
 */
class MixedFeed
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    /**
     * @var FeedProviderInterface[]
     */
    protected array $providers;

    protected string $sortDirection;

    /**
     * Create a mixed feed composed of heterogeneous feed providers.
     *
     * @param FeedProviderInterface[] $providers
     * @param string                  $sortDirection
     */
    public function __construct(array $providers = [], $sortDirection = MixedFeed::DESC)
    {
        foreach ($providers as $provider) {
            if (!($provider instanceof FeedProviderInterface)) {
                throw new \RuntimeException('Provider must implements FeedProviderInterface interface.', 1);
            }
        }

        $this->providers = $providers;
        $this->sortDirection = $sortDirection;
    }

    /**
     * @return FeedItem[]
     */
    public function getCanonicalItems(int $count = 5): array
    {
        $list = [];
        if (\count($this->providers) > 0) {
            $perProviderCount = (int) \floor($count / \count($this->providers));

            /** @var FeedProviderInterface $provider */
            foreach ($this->providers as $provider) {
                try {
                    /** @var FeedItem[] $list */
                    $list = \array_merge($list, $provider->getCanonicalItems($perProviderCount));
                } catch (FeedProviderErrorException $e) {
                    $errorItem = new FeedItem();
                    $errorItem->setMessage($e->getMessage());
                    $errorItem->setPlatform($provider->getFeedPlatform().' [errored]');
                    $errorItem->setDateTime(new DateTime());
                    $list = \array_merge($list, [
                        $errorItem,
                    ]);
                }
            }
        }

        return $this->sortFeedItems($list);
    }

    /**
     * @param FeedItem[] $feedItems
     *
     * @return FeedItem[] $feedItems
     */
    protected function sortFeedItems(array $feedItems): array
    {
        \usort($feedItems, function (FeedItem $a, FeedItem $b) {
            $aDT = $a->getDateTime();
            $bDT = $b->getDateTime();

            if ($aDT == $bDT) {
                return 0;
            }
            // ASC sorting
            if ($this->sortDirection === static::ASC) {
                return ($aDT > $bDT) ? 1 : -1;
            }
            // DESC sorting
            return ($aDT > $bDT) ? -1 : 1;
        });

        return $feedItems;
    }

    /**
     * @return FeedItem[]
     */
    public function getAsyncCanonicalItems(int $count = 5): array
    {
        if (0 === \count($this->providers)) {
            throw new \RuntimeException('No provider were registered');
        }
        $perProviderCount = (int) \floor($count / \count($this->providers));
        $list = [];
        $requests = [];
        /** @var FeedProviderInterface $provider */
        foreach ($this->providers as $providerIdx => $provider) {
            if ($provider->supportsRequestPool() && !$provider->isCacheHit($perProviderCount)) {
                foreach ($provider->getRequests($perProviderCount) as $i => $request) {
                    $index = $providerIdx.'.'.$i;
                    $requests[$index] = $request;
                }
            }
        }

        $client = new Client();
        $pool = new Pool($client, $requests, [
            'concurrency' => 6,
            'fulfilled'   => function (Response $response, $index) {
                list($providerIdx, $i) = \explode('.', $index);
                $provider = $this->providers[$providerIdx];
                if (
                    200 === $response->getStatusCode()
                    && $provider instanceof AbstractFeedProvider
                    && $response instanceof Response
                ) {
                    $provider->setRawFeed($response->getBody()->getContents());
                } else {
                    $provider->addError($response->getReasonPhrase());
                }
            },
            'rejected' => function (RequestException $reason, $index) {
                list($providerIdx, $i) = \explode('.', $index);
                $provider = $this->providers[$providerIdx];
                if (
                    $provider instanceof AbstractFeedProvider
                ) {
                    $provider->addError($reason->getMessage());
                }
            },
        ]);

        // Initiate the transfers and create a promise
        $pool->promise()->wait();

        /** @var FeedProviderInterface $provider */
        foreach ($this->providers as $providerIdx => $provider) {
            /*
             * For providers which already have a cached response
             */
            try {
                /** @var FeedItem[] $list */
                $list = \array_merge($list, $provider->getCanonicalItems($perProviderCount));
            } catch (FeedProviderErrorException $e) {
                $errorItem = new FeedItem();
                $errorItem->setMessage($e->getMessage());
                $errorItem->setPlatform($provider->getFeedPlatform().' [errored]');
                $errorItem->setDateTime(new DateTime());
                $list = \array_merge($list, [
                    $errorItem,
                ]);
            }
        }

        return $this->sortFeedItems($list);
    }
}

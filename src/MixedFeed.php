<?php
/**
 * Copyright © 2018, Ambroise Maupate
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
 * @file MixedFeed.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use RZ\MixedFeed\MockObject\ErroredFeedItem;

/**
 * Combine feed providers and sort them by date and time, descending or ascending.
 */
class MixedFeed extends AbstractFeedProvider
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    /**
     * @var FeedProviderInterface[]
     */
    protected $providers;

    /**
     * @var string
     */
    protected $sortDirection;

    protected function getCacheKey(): string
    {
        return '';
    }

    /**
     * Create a mixed feed composed of heterogeneous feed providers.
     *
     * @param FeedProviderInterface[] $providers
     * @param string $sortDirection
     */
    public function __construct(array $providers = [], $sortDirection = MixedFeed::DESC)
    {
        parent::__construct(null);
        foreach ($providers as $provider) {
            if (!($provider instanceof FeedProviderInterface)) {
                throw new \RuntimeException("Provider must implements FeedProviderInterface interface.", 1);
            }
        }

        $this->providers = $providers;
        $this->sortDirection = $sortDirection;
    }

    /**
     * @deprecated
     */
    public function getItems($count = 5)
    {
        $list = [];
        if (count($this->providers) > 0) {
            $perProviderCount = floor($count / count($this->providers));

            /** @var FeedProviderInterface $provider */
            foreach ($this->providers as $provider) {
                try {
                    $list = array_merge($list, $provider->getItems($perProviderCount));
                } catch (FeedProviderErrorException $e) {
                    $list = array_merge($list, [
                        new ErroredFeedItem($e->getMessage(), $provider->getFeedPlatform()),
                    ]);
                }
            }
        }

        return $this->sortFeedObjects($list);
    }

    /**
     * @inheritDoc
     */
    public function getCanonicalItems($count = 5)
    {
        $list = [];
        if (count($this->providers) > 0) {
            $perProviderCount = floor($count / count($this->providers));

            /** @var FeedProviderInterface $provider */
            foreach ($this->providers as $provider) {
                try {
                    $list = array_merge($list, $provider->getCanonicalItems($perProviderCount));
                } catch (FeedProviderErrorException $e) {
                    $errorItem = new FeedItem();
                    $errorItem->setMessage($e->getMessage());
                    $errorItem->setPlatform($provider->getFeedPlatform() . ' [errored]');
                    $errorItem->setDateTime(new \DateTime());
                    $list = array_merge($list, [
                        $errorItem
                    ]);
                }
            }
        }
        return $this->sortFeedItems($list);
    }

    /**
     * @param FeedItem[] $feedItems
     *
     * @return array
     */
    protected function sortFeedItems(array $feedItems): array
    {
        usort($feedItems, function (FeedItem $a, FeedItem $b) {
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
     * @param \stdClass[] $items
     *
     * @return array
     */
    protected function sortFeedObjects(array $items)
    {
        usort($items, function (\stdClass $a, \stdClass $b) {
            $aDT = $a->normalizedDate;
            $bDT = $b->normalizedDate;

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

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'mixed';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        return new \DateTime('now');
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function getFeed($count = 5): array
    {
        trigger_error('getFeed method must not be called in MixedFeed.', E_USER_ERROR);
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAsyncCanonicalItems($count = 5): array
    {
        if (count($this->providers) === 0) {
            throw new \RuntimeException('No provider were registered');
        }
        $perProviderCount = floor($count / count($this->providers));
        $list = [];
        $requests = [];
        /** @var FeedProviderInterface $provider */
        foreach ($this->providers as $providerIdx => $provider) {
            if ($provider->supportsRequestPool() && !$provider->isCacheHit($perProviderCount)) {
                foreach ($provider->getRequests($perProviderCount) as $i => $request) {
                    $index = $providerIdx . '.' . $i;
                    $requests[$index] = $request;
                }
            }
        }

        $client = new Client();
        $pool = new Pool($client, $requests, [
            'concurrency' => 6,
            'fulfilled' => function ($response, $index) use (&$list, $perProviderCount) {
                list($providerIdx, $i) = explode('.', $index);
                $provider = $this->providers[$providerIdx];
                if ($provider instanceof AbstractFeedProvider &&
                    $response instanceof Response &&
                    $response->getStatusCode() === 200) {
                    $provider->setRawFeed($response->getBody()->getContents());
                } else {
                    $provider->addError($response->getReasonPhrase());
                }
            },
            'rejected' => function ($reason, $index) {
                list($providerIdx, $i) = explode('.', $index);
                $provider = $this->providers[$providerIdx];
                if ($provider instanceof AbstractFeedProvider &&
                    method_exists($reason, 'getMessage')) {
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
                $list = array_merge($list, $provider->getCanonicalItems($perProviderCount));
            } catch (FeedProviderErrorException $e) {
                $errorItem = new FeedItem();
                $errorItem->setMessage($e->getMessage());
                $errorItem->setPlatform($provider->getFeedPlatform() . ' [errored]');
                $errorItem->setDateTime(new \DateTime());
                $list = array_merge($list, [
                    $errorItem
                ]);
            }
        }

        return $this->sortFeedItems($list);
    }

    /**
     * @param int $count
     *
     * @return \Generator
     */
    public function getRequests($count = 5): \Generator
    {
        if (count($this->providers) === 0) {
            throw new \RuntimeException('No provider were registered');
        }

        $perProviderCount = floor($count / count($this->providers));

        /** @var FeedProviderInterface $provider */
        foreach ($this->providers as $provider) {
            yield iterator_to_array($provider->getRequests($perProviderCount));
        }
    }
}

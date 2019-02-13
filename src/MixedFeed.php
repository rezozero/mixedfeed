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

    /**
     * Create a mixed feed composed of heterogeneous feed providers.
     *
     * @param FeedProviderInterface[] $providers
     * @param string $sortDirection
     */
    public function __construct(array $providers = [], $sortDirection = MixedFeed::DESC)
    {
        foreach ($providers as $provider) {
            if (!($provider instanceof FeedProviderInterface)) {
                throw new \RuntimeException("Provider must implements FeedProviderInterface interface.", 1);
            }
        }

        $this->providers = $providers;
        $this->sortDirection = $sortDirection;
    }

    /**
     * {@inheritdoc}
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

            usort($list, function (\stdClass $a, \stdClass $b) {
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
        }

        return $list;
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

            usort($list, function (FeedItem $a, FeedItem $b) {
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
        }

        return $list;
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
    protected function getFeed($count = 5)
    {
        trigger_error('getFeed method must not be called in MixedFeed.', E_USER_ERROR);
    }
}

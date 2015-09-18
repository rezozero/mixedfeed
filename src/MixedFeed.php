<?php
/**
 * Copyright © 2015, Ambroise Maupate
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

use RZ\MixedFeed\AbstractFeedProvider;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use RZ\MixedFeed\MockObject\ErroredFeedItem;

/**
 * Combine feed providers and sort them antechronological.
 */
class MixedFeed extends AbstractFeedProvider
{
    protected $providers;

    /**
     * Create a mixed feed composed of hetergeneous feed
     * providers.
     *
     * @param array $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            if (!($provider instanceof FeedProviderInterface)) {
                throw new \RuntimeException("Provider must implements FeedProviderInterface interface.", 1);
            }
        }

        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($count = 5)
    {
        $perProviderCount = floor($count / count($this->providers));
        $list = [];

        foreach ($this->providers as $provider) {
            try {
                $list = array_merge($list, $provider->getItems($perProviderCount));
            } catch (FeedProviderErrorException $e) {
                $list = array_merge($list, [
                    new ErroredFeedItem($e->getMessage(), $provider->getFeedPlatform()),
                ]);
            }
        }

        usort($list, function ($a, $b) {
            $aDT = $a->normalizedDate;
            $bDT = $b->normalizedDate;

            if ($aDT == $bDT) {
                return 0;
            }
            // DESC sorting
            return ($aDT > $bDT) ? -1 : 1;
        });

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
}

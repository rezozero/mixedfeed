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
 * @file FeedProviderInterface.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use RZ\MixedFeed\Exception\FeedProviderErrorException;

interface FeedProviderInterface
{
    /**
     * @param int $count
     *
     * @return bool
     */
    public function isCacheHit($count = 5): bool;
    /**
     * @param int $count
     *
     * @return \Generator
     */
    public function getRequests($count = 5): \Generator;

    /**
     * Get the social platform name.
     *
     * @return string
     */
    public function getFeedPlatform();
    /**
     *
     * Get item method must return the direct
     * feed array and must inject two parameters in each item:
     *
     * * feedItemPlatform (string)
     * * normalizedDate (\DateTime)
     *
     * @param  integer $count
     * @return array
     * @throws FeedProviderErrorException
     * @deprecated Use getCanonicalItems method
     */
    public function getItems($count = 5);

    /**
     * Get item method must return a normalized array of FeedItem.
     *
     * @param int $count
     *
     * @return mixed
     * @throws FeedProviderErrorException
     */
    public function getCanonicalItems($count = 5);

    /**
     * Get a \DateTime object from a social feed item.
     *
     * @param \stdClass $item
     * @return \DateTime
     */
    public function getDateTime($item);

    /**
     * Check if the feed provider has succeded to
     * contact API.
     *
     * @param $feed
     *
     * @return boolean
     */
    public function isValid($feed);

    /**
     * @param string $reason
     *
     * @return $this
     */
    public function addError(string $reason): FeedProviderInterface;

    /**
     * Get a canonical message from current feed item.
     *
     * @param \stdClass $item
     * @return string
     */
    public function getCanonicalMessage($item);

    /**
     * @return bool
     */
    public function supportsRequestPool(): bool;
}

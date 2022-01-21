<?php

/**
 * Copyright © 2015, Ambroise Maupate.
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
 *
 * @author Ambroise Maupate
 */

namespace RZ\MixedFeed;

use DateTime;
use Generator;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\FeedProviderErrorException;
use stdClass;

interface FeedProviderInterface
{
    public function isCacheHit(int $count = 5): bool;

    /** @return Generator<Request> */
    public function getRequests(int $count = 5): Generator;

    /**
     * Get the social platform name.
     */
    public function getFeedPlatform(): string;

    /**
     * Get item method must return a normalized array of FeedItem.
     *
     * @return FeedItem[]
     *
     * @throws FeedProviderErrorException
     */
    public function getCanonicalItems(int $count = 5): array;

    /**
     * Get a DateTime object from a social feed item.
     *
     * @param \stdClass $item
     */
    public function getDateTime($item): ?DateTime;

    /**
     * Check if the feed provider has succeded to
     * contact API.
     *
     * @param mixed $feed
     */
    public function isValid($feed): bool;

    /**
     * @return $this
     */
    public function addError(string $reason): FeedProviderInterface;

    /**
     * Get a canonical message from current feed item.
     */
    public function getCanonicalMessage(stdClass $item): string;

    public function supportsRequestPool(): bool;
}

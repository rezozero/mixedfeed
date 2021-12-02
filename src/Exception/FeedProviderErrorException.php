<?php

namespace RZ\MixedFeed\Exception;

use Exception;
use Throwable;

class FeedProviderErrorException extends Exception
{
    public function __construct(string $feedPlatform, string $errors, ?Throwable $previous = null)
    {
        parent::__construct('Error contacting '.$feedPlatform.' feed provider: '.$errors, 1, $previous);
    }
}

<?php
namespace RZ\MixedFeed\Exception;

use Throwable;

class FeedProviderErrorException extends \Exception
{
    public function __construct($feedPlatform, $errors, Throwable $previous = null)
    {
        parent::__construct("Error contacting " . $feedPlatform . " feed provider: " . $errors, 1, $previous);
    }
}

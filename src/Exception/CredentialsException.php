<?php

namespace RZ\MixedFeed\Exception;

use Exception;
use Throwable;

class CredentialsException extends Exception
{
    public function __construct(
        string $message = 'Insufficient authentication data found.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

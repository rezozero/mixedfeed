<?php
namespace RZ\MixedFeed\Exception;

use Throwable;

class CredentialsException extends \Exception
{
    public function __construct($message = "Insufficient authentication data found.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

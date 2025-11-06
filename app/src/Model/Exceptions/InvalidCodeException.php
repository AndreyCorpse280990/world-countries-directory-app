<?php

namespace App\Model\Exceptions;

use Exception;
use Throwable;

class InvalidCodeException extends Exception
{
    public function __construct($invalidCode, $message, Throwable $previous = null)
    {
        $exceptionMessage = "country code '" . $invalidCode . "' is invalid: " . $message;
        parent::__construct(
            message: $exceptionMessage,
            code: ErrorCodes::INVALID_CODE_ERROR,
            previous: $previous,
        );
    }
    
}
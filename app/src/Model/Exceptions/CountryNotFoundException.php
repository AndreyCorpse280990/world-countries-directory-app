<?php

namespace App\Model\Exceptions;

use Exception;
use Throwable;

class CountryNotFoundException extends Exception
{
    public function __construct($notFoundCode, Throwable $previous = null)
    {
        $exceptionMessage = "country '" . $notFoundCode . "' not found";
        parent::__construct(
            message: $exceptionMessage,
            code: ErrorCodes::NOT_FOUND_ERROR,
            previous: $previous,
        );
    }
    
}
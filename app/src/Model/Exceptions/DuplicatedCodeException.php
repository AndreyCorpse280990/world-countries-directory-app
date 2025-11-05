<?php

namespace App\Model\Exceptions;

use Exception;
use Throwable;

class DuplicatedCodeException extends Exception
{
    public function __construct(string $duplicatedCode, Throwable $previous = null)
    {
        $exceptionMessage = "country code '" . $duplicatedCode . "' is duplicated";
        parent::__construct(
            message: $exceptionMessage,
            code: ErrorCodes::DUPLICATED_CODE_ERROR,
            previous: $previous,
        );
    }
}
<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    public string $field;
    public string $errorCode;

    public function __construct(
        string $field,
        string $errorCode,
        string $message = 'Invalid business rule'
    ) {
        parent::__construct($message);

        $this->field = $field;
        $this->errorCode = $errorCode;
    }
}

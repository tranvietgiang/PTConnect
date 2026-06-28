<?php

namespace App\Domains\Auth\Exceptions;

use RuntimeException;

class TokenExpiredException extends RuntimeException
{
    public function __construct(string $message = 'Token has expired.')
    {
        parent::__construct($message);
    }
}

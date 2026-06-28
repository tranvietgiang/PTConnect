<?php

namespace App\Domains\Auth\Exceptions;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}

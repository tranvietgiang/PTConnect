<?php

namespace App\Domains\Auth\Exceptions;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Thông tin đăng nhập không đúng.');
    }
}

<?php

declare(strict_types=1);

namespace midorikocak\nano\Exceptions;

use Exception;

use function http_response_code;

class UnauthorizedException extends Exception
{
    public function __construct()
    {
        parent::__construct('401 Unathorized', 401, null);
        http_response_code(401);
    }
}

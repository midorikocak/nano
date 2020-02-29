<?php

declare(strict_types=1);

namespace midorikocak\nano\Exceptions;

use Exception;

use function http_response_code;

class NotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('404 Not found.', 404, null);
        http_response_code(404);
    }
}

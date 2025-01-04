<?php

namespace TiMacDonald\Website;

use RuntimeException;

class HttpException extends RuntimeException
{
    private function __construct(
        readonly public int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self(404, 'Not Found');
    }

    public static function methodNotAllowed(): self
    {
        return new self(405, 'Method Not Allowed');
    }
}

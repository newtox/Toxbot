<?php

namespace App\Services\Streams;

use RuntimeException;

class PlatformException extends RuntimeException
{
    public function __construct(string $key, public readonly array $replace = [])
    {
        parent::__construct($key);
    }
}

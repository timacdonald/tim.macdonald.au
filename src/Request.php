<?php

namespace TiMacDonald\Website;

readonly class Request
{
    public string $base;

    public string $path;

    public function __construct(
        string $base,
        string $path,
    ) {
        $this->base = rtrim($base, '/');
        $this->path = '/'.ltrim($path, '/');
    }

    public function url(): string
    {
        return $this->base.($this->path === '/' ? '' : $this->path);
    }
}


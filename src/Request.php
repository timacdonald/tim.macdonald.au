<?php

namespace TiMacDonald\Website;

readonly class Request
{
    public string $base;

    public string $path;

    public string $method;

    public function __construct(
        string $base,
        string $method,
        string $path,
    ) {
        $this->base = rtrim($base, '/');
        $this->method = mb_strtolower($method);
        $this->path = '/'.rtrim(ltrim($path, '/'), '/');
    }

    public function url(): string
    {
        return $this->base.($this->path === '/' ? '' : $this->path);
    }
}

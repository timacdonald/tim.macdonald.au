<?php

namespace TiMacDonald\Website;

readonly class Url
{
    private string $base;

    public function __construct(
        string $base,
        private string $assetVersion,
    ) {
        $this->base = rtrim($base, '/');
    }

    public function to(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->base.($path === '' ? '' : '/'.$path);
    }

    public function asset(string $path): string
    {
        return $this->to($path).'?v='.$this->assetVersion;
    }
}

